<?php

namespace App\Services;

use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Builds the home-page cards from the HUB map (map.json, published by HUB's build-map.py).
 *
 * Every rostered project with an address gets a card: a Mini-PC card for its LAN
 * `http://192.168.0.164:<port>` URL and a Live card, "<name> (public site)", for a public
 * https URL. A Mini-PC card opens the project's tailnet (`*.ts.net`) address when the roster
 * lists one, with the LAN address kept as its Home link (alt_url). Cards are keyed by (roster_id, roster_slot). Existing hand-made cards are
 * adopted by host+port on first sight instead of being duplicated, and an adopted card
 * keeps its own title and URL until the roster itself changes them. Sort order and image
 * are never touched. A card whose project leaves the map (or is archived) is hidden, not
 * deleted. Cards with no roster_id are never touched.
 */
class HubMapSync
{
    public const LAN_HOST = '192.168.0.164';

    /** @var array<string, list<string>> */
    private array $report = [];

    /** @var array<string, int> */
    private array $counts = [
        'matched' => 0,
        'created' => 0,
        'updated' => 0,
        'unchanged' => 0,
        'hidden' => 0,
        'unhidden' => 0,
        'skipped' => 0,
    ];

    /**
     * @param  array<string, mixed>  $map  decoded map.json
     * @param  string|null  $self  roster id of this site (no card for itself)
     * @return array{counts: array<string, int>, lines: array<string, list<string>>}
     */
    public function sync(array $map, bool $dryRun = false, ?string $self = null): array
    {
        $items = $map['items'] ?? null;

        if (! is_array($items) || ! collect($items)->contains(fn ($i) => ($i['kind'] ?? null) === 'project')) {
            throw new RuntimeException('The map has no project items; refusing to sync (it would hide every card).');
        }

        $targets = $this->targets($items, $self);

        DB::beginTransaction();

        try {
            $this->apply($targets);
        } finally {
            $dryRun ? DB::rollBack() : DB::commit();
        }

        return ['counts' => $this->counts, 'lines' => $this->report];
    }

    /**
     * The cards the map asks for, one per address; a second item on an address that is
     * already taken (HUB and its site, a tab of a site) is skipped.
     *
     * @param  array<int, mixed>  $items
     * @return Collection<int, array{id: string, slot: string, name: string, title: string, url: string, key: string, tailnet: string|null}>
     */
    private function targets(array $items, ?string $self): Collection
    {
        $targets = collect();
        $owners = [];

        foreach ($items as $item) {
            if (! is_array($item) || ($item['kind'] ?? null) !== 'project' || ($item['status'] ?? null) === 'archived') {
                continue;
            }

            $id = (string) ($item['id'] ?? '');
            $name = trim((string) ($item['name'] ?? ''));
            $urls = array_values(array_filter((array) ($item['lives']['urls'] ?? []), 'is_string'));

            if ($id === '' || $name === '' || $urls === []) {
                continue;
            }

            if ($id === $self) {
                $this->note('skipped', "{$id}: this site itself");

                continue;
            }

            $lan = collect($urls)->first(fn ($u) => $this->isLan($u));
            $public = collect($urls)->first(fn ($u) => $this->isPublic($u));
            $tailnet = collect($urls)->first(fn ($u) => $this->isTailnet($u));

            if ($lan === null && $public === null) {
                $this->note('skipped', "{$id}: no LAN or public address (".implode(', ', $urls).')');

                continue;
            }

            foreach (['lan' => $lan, 'public' => $public] as $slot => $url) {
                if ($url === null) {
                    continue;
                }

                $key = self::addressKey($url);

                if (isset($owners[$key])) {
                    $this->note('skipped', "{$id} ({$slot}): {$key} already belongs to {$owners[$key]}");

                    continue;
                }

                $owners[$key] = $id;
                $targets->push([
                    'id' => $id,
                    'slot' => $slot,
                    'name' => $name,
                    'title' => self::title($name, $slot),
                    'url' => $url,
                    'key' => $key,
                    'tailnet' => $slot === 'lan' ? $tailnet : null,
                ]);
            }
        }

        return $targets;
    }

    /**
     * @param  Collection<int, array{id: string, slot: string, name: string, title: string, url: string, key: string, tailnet: string|null}>  $targets
     */
    private function apply(Collection $targets): void
    {
        $cards = Website::orderBy('id')->get();
        $wanted = [];

        foreach ($targets as $t) {
            $wanted["{$t['id']}|{$t['slot']}"] = true;

            $card = $cards->first(fn (Website $c) => $c->roster_id === $t['id'] && $c->roster_slot === $t['slot']);

            if ($card !== null) {
                $this->refresh($card, $t);

                continue;
            }

            if ($this->adopt($cards, $t) === null) {
                $card = Website::create([
                    'title' => $t['title'],
                    'url' => $t['tailnet'] ?? $t['url'],
                    'alt_url' => $t['tailnet'] !== null ? $t['url'] : null,
                    'sort_order' => ((int) $cards->max('sort_order')) + 1,
                    'roster_id' => $t['id'],
                    'roster_slot' => $t['slot'],
                    'roster_name' => $t['name'],
                    'roster_url' => $t['url'],
                ]);
                $cards->push($card);
                $this->note('created', "#{$card->id} {$card->title} -> {$card->url}");
            }
        }

        foreach ($cards as $card) {
            if ($card->roster_id !== null && ! isset($wanted["{$card->roster_id}|{$card->roster_slot}"]) && ! $card->hidden) {
                $card->update(['hidden' => true]);
                $this->note('hidden', "#{$card->id} {$card->title} ({$card->roster_id} is gone from the map or archived)");
            }
        }
    }

    /**
     * First sight of a roster item: claim the hand-made card on the same host+port.
     * The card keeps its own title and URL; the roster's are only remembered.
     *
     * @param  Collection<int, Website>  $cards
     * @param  array{id: string, slot: string, name: string, title: string, url: string, key: string, tailnet: string|null}  $t
     */
    private function adopt(Collection $cards, array $t): ?Website
    {
        $card = $cards->first(fn (Website $c) => $c->roster_id === null
            && (self::addressKey($c->url) === $t['key'] || ($c->alt_url && self::addressKey($c->alt_url) === $t['key'])));

        if ($card === null) {
            return null;
        }

        $card->update([
            'roster_id' => $t['id'],
            'roster_slot' => $t['slot'],
            'roster_name' => $t['name'],
            'roster_url' => $t['url'],
            'hidden' => false,
        ]);
        $this->note('matched', "{$t['id']} ({$t['slot']}, {$t['key']}) -> #{$card->id} {$card->title}");

        return $card;
    }

    /**
     * A card already tied to the roster: follow the roster's name and URL only when the
     * roster changed them since the last sync, and bring the card back if it was hidden.
     *
     * @param  array{id: string, slot: string, name: string, title: string, url: string, key: string, tailnet: string|null}  $t
     */
    private function refresh(Website $card, array $t): void
    {
        $changes = [];

        if ($card->roster_name !== $t['name']) {
            $changes['title'] = $t['title'];
            $changes['roster_name'] = $t['name'];
        }

        if ($card->roster_url !== $t['url']) {
            // Rewrite whichever address held the roster's old one (adopted cards often keep
            // the tailnet URL as primary and the LAN one as the alternative).
            $old = $card->roster_url ? self::addressKey($card->roster_url) : null;
            $field = $old !== null && $card->alt_url && self::addressKey($card->alt_url) === $old
                && self::addressKey($card->url) !== $old ? 'alt_url' : 'url';
            $changes[$field] = $t['url'];
            $changes['roster_url'] = $t['url'];
        }

        $swap = $this->tailnetFirst($card, $t, $changes);
        $changes = [...$changes, ...$swap];

        if ($card->hidden) {
            $changes['hidden'] = false;
            $this->note('unhidden', "#{$card->id} {$card->title}");
        }

        if ($changes === []) {
            $this->counts['unchanged']++;

            return;
        }

        $card->update($changes);

        if (isset($changes['roster_name']) || isset($changes['roster_url']) || $swap !== []) {
            $this->note('updated', "#{$card->id} {$card->title} -> {$card->url}");
        }
    }

    /**
     * A Mini-PC card opens its tailnet address with the LAN one as Home: give a LAN-only
     * card the roster's tailnet address, and swap a card that has them the wrong way round.
     *
     * @param  array{id: string, slot: string, name: string, title: string, url: string, key: string, tailnet: string|null}  $t
     * @param  array<string, mixed>  $changes  what refresh() already decided
     * @return array<string, mixed>
     */
    private function tailnetFirst(Website $card, array $t, array $changes): array
    {
        if ($t['slot'] !== 'lan') {
            return [];
        }

        $url = $changes['url'] ?? $card->url;
        $alt = $changes['alt_url'] ?? $card->alt_url;

        if ($alt === null && $t['tailnet'] !== null && $this->isLan($url)) {
            return ['url' => $t['tailnet'], 'alt_url' => $url];
        }

        if ($alt !== null && $this->isTailnet($alt) && ! $this->isTailnet($url)) {
            return ['url' => $alt, 'alt_url' => $url];
        }

        return [];
    }

    private function isTailnet(string $url): bool
    {
        return str_ends_with(strtolower((string) parse_url($url, PHP_URL_HOST)), '.ts.net');
    }

    private function isLan(string $url): bool
    {
        if (! preg_match('#^http://'.preg_quote(self::LAN_HOST, '#').':(\d+)(?:[/?\#]|$)#i', $url)) {
            return false;
        }

        // A data feed (farm-world's latest.json) is not a page to open.
        $path = (string) parse_url($url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $ext === '' || in_array($ext, ['html', 'htm', 'php'], true);
    }

    private function isPublic(string $url): bool
    {
        if (! preg_match('#^https://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== ''
            && ! str_ends_with($host, '.ts.net')
            && $host !== 'localhost'
            && filter_var($host, FILTER_VALIDATE_IP) === false
            && str_contains($host, '.');
    }

    public static function addressKey(string $url): string
    {
        $parts = parse_url(trim($url)) ?: [];
        $scheme = strtolower($parts['scheme'] ?? 'http');
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return "{$host}:{$port}";
    }

    public static function title(string $name, string $slot): string
    {
        return $slot === 'public' ? "{$name} (public site)" : $name;
    }

    private function note(string $kind, string $line): void
    {
        $this->counts[$kind]++;
        $this->report[$kind][] = $line;
    }
}

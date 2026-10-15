<?php

use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function hubMap(): array
{
    return json_decode(file_get_contents(base_path('tests/Fixtures/hub-map.json')), true);
}

/** Write a (possibly edited) map to a temp file and run the sync against it. */
function syncMap(?array $map = null, array $options = [])
{
    $path = tempnam(sys_get_temp_dir(), 'hubmap');
    file_put_contents($path, json_encode($map ?? hubMap()));

    return test()->artisan('nav:sync-map', ['--source' => $path, ...$options]);
}

function handCards(): void
{
    // Hand-made cards as they exist today: tailnet URL first, LAN as the alternative.
    Website::create(['title' => 'Cooking Pantry', 'url' => 'https://minipc.jackal-hippocampus.ts.net:461/', 'alt_url' => 'http://192.168.0.164:100/', 'sort_order' => 21, 'image_path' => 'websites/pantry.png']);
    Website::create(['title' => 'Cooking Pantry (public site)', 'url' => 'https://kitchen.thecorgisquad.com', 'sort_order' => 31]);
    Website::create(['title' => 'Budget', 'url' => 'https://minipc.jackal-hippocampus.ts.net:447', 'alt_url' => 'http://192.168.0.164:84', 'sort_order' => 5]);
    Website::create(['title' => 'Password Vault', 'url' => 'https://minipc.jackal-hippocampus.ts.net:449', 'alt_url' => 'https://192.168.0.164:88', 'sort_order' => 8]);
    Website::create(['title' => 'HUB', 'url' => 'https://minipc.jackal-hippocampus.ts.net:471/', 'alt_url' => 'http://192.168.0.164:120/', 'sort_order' => 7]);
    Website::create(['title' => 'Dead Quiet', 'url' => 'http://192.168.0.164:96/dead-quiet/index.html', 'sort_order' => 19]);
    Website::create(['title' => 'Jellyfin', 'url' => 'https://minipc.jackal-hippocampus.ts.net:458', 'alt_url' => 'http://192.168.0.164:8096', 'sort_order' => 17]);
}

test('existing hand-made cards are adopted by host and port, not duplicated', function () {
    handCards();

    syncMap()->assertSuccessful()
        ->expectsOutputToContain('matched 5, created 2');

    expect(Website::count())->toBe(9);

    $pantry = Website::where('title', 'Cooking Pantry')->sole();
    expect($pantry->roster_id)->toBe('kitchen')
        ->and($pantry->roster_slot)->toBe('lan')
        ->and($pantry->url)->toBe('https://minipc.jackal-hippocampus.ts.net:461/')
        ->and($pantry->sort_order)->toBe(21)
        ->and($pantry->image_path)->toBe('websites/pantry.png');

    expect(Website::where('title', 'Cooking Pantry (public site)')->sole())
        ->roster_id->toBe('kitchen')
        ->roster_slot->toBe('public');

    // https://192.168.0.164:88 matches the roster's http://192.168.0.164:88 by host+port.
    expect(Website::where('title', 'Password Vault')->sole()->roster_id)->toBe('vault');

    // Two roster items on :120 — the first one owns the card.
    expect(Website::where('title', 'HUB')->sole()->roster_id)->toBe('hub-site');

    // Hand-made cards with no roster address are left alone.
    expect(Website::whereIn('title', ['Dead Quiet', 'Jellyfin'])->pluck('roster_id')->filter())->toBeEmpty();
});

test('missing cards are created for the lan and public addresses', function () {
    syncMap()->assertSuccessful();

    $lan = Website::where('roster_id', 'newsite')->where('roster_slot', 'lan')->sole();
    expect($lan->title)->toBe('New Site')
        ->and($lan->url)->toBe('http://192.168.0.164:130')
        ->and($lan->location)->toBe('minipc');

    $public = Website::where('roster_id', 'newsite')->where('roster_slot', 'public')->sole();
    expect($public->title)->toBe('New Site (public site)')
        ->and($public->url)->toBe('https://new.thecorgisquad.com')
        ->and($public->location)->toBe('live');

    expect(Website::where('title', 'Kitchen (public site)')->exists())->toBeTrue();
    expect($public->sort_order)->toBeGreaterThan($lan->sort_order);
});

test('a new mini-pc card opens the tailnet address with the lan one as home', function () {
    $map = hubMap();
    foreach ($map['items'] as &$item) {
        if ($item['id'] === 'newsite') {
            $item['lives']['urls'][] = 'https://minipc.jackal-hippocampus.ts.net:530';
        }
    }
    unset($item);

    syncMap($map)->assertSuccessful();

    expect(Website::where('roster_id', 'newsite')->where('roster_slot', 'lan')->sole())
        ->url->toBe('https://minipc.jackal-hippocampus.ts.net:530')
        ->alt_url->toBe('http://192.168.0.164:130')
        ->location->toBe('minipc');

    // The public card never takes the tailnet address.
    expect(Website::where('roster_id', 'newsite')->where('roster_slot', 'public')->sole())
        ->url->toBe('https://new.thecorgisquad.com')
        ->alt_url->toBeNull();
});

test('a lan-only card moves to the tailnet address once the roster lists one', function () {
    syncMap()->assertSuccessful();
    expect(Website::where('roster_id', 'budget')->sole()->alt_url)->toBeNull();

    $map = hubMap();
    foreach ($map['items'] as &$item) {
        if ($item['id'] === 'budget') {
            $item['lives']['urls'][] = 'https://minipc.jackal-hippocampus.ts.net:447';
        }
    }
    unset($item);

    syncMap($map)->assertSuccessful()->expectsOutputToContain('updated 1');

    expect(Website::where('roster_id', 'budget')->sole())
        ->url->toBe('https://minipc.jackal-hippocampus.ts.net:447')
        ->alt_url->toBe('http://192.168.0.164:84');

    syncMap($map)->assertSuccessful()->expectsOutputToContain('updated 0');
});

test('a card with its addresses the wrong way round is swapped', function () {
    syncMap()->assertSuccessful();
    Website::where('roster_id', 'budget')->update([
        'url' => 'http://192.168.0.164:84',
        'alt_url' => 'https://minipc.jackal-hippocampus.ts.net:447',
    ]);

    syncMap()->assertSuccessful();

    expect(Website::where('roster_id', 'budget')->sole())
        ->url->toBe('https://minipc.jackal-hippocampus.ts.net:447')
        ->alt_url->toBe('http://192.168.0.164:84');
});

test('skips tailnet-only, feeds, portless, archived, services, self and empty items', function () {
    syncMap()->assertSuccessful();

    expect(Website::pluck('roster_id')->unique()->sort()->values()->all())
        ->toBe(['budget', 'hub-site', 'kitchen', 'newsite', 'vault']);

    // PasswordVault's https://192.168.0.164:449 is LAN, not a public site.
    expect(Website::where('roster_id', 'vault')->count())->toBe(1);
});

test('a second run changes nothing', function () {
    handCards();
    syncMap()->assertSuccessful();
    $before = Website::orderBy('id')->get()->toArray();

    syncMap()->assertSuccessful()
        ->expectsOutputToContain('matched 0, created 0, updated 0, unchanged 7, hidden 0');

    expect(Website::orderBy('id')->get()->toArray())->toBe($before);
});

test('a roster rename or new port updates name and url but never sort or image', function () {
    handCards();
    syncMap()->assertSuccessful();

    Website::where('roster_id', 'kitchen')->update(['sort_order' => 3]);

    $map = hubMap();
    $map['items'][0]['name'] = 'Cooking Pantry';
    $map['items'][0]['lives']['urls'] = ['https://pantry.thecorgisquad.com', 'http://192.168.0.164:101'];
    syncMap($map)->assertSuccessful()->expectsOutputToContain('updated 2');

    $lan = Website::where('roster_id', 'kitchen')->where('roster_slot', 'lan')->sole();
    expect($lan->title)->toBe('Cooking Pantry')
        ->and($lan->url)->toBe('https://minipc.jackal-hippocampus.ts.net:461/') // tailnet primary kept
        ->and($lan->alt_url)->toBe('http://192.168.0.164:101')                  // the LAN address moved
        ->and($lan->sort_order)->toBe(3)
        ->and($lan->image_path)->toBe('websites/pantry.png');

    expect(Website::where('roster_id', 'kitchen')->where('roster_slot', 'public')->sole())
        ->title->toBe('Cooking Pantry (public site)')
        ->url->toBe('https://pantry.thecorgisquad.com');
});

test('a hand edit on an adopted card survives while the roster stays the same', function () {
    handCards();
    syncMap()->assertSuccessful();

    Website::where('title', 'Budget')->update(['title' => 'Money']);
    syncMap()->assertSuccessful();

    expect(Website::where('roster_id', 'budget')->sole()->title)->toBe('Money');
});

test('cards whose project leaves the map or is archived are hidden, and come back', function () {
    handCards();
    syncMap()->assertSuccessful();

    $map = hubMap();
    $map['items'] = array_values(array_filter($map['items'], fn ($i) => $i['id'] !== 'newsite'));
    foreach ($map['items'] as &$item) {
        if ($item['id'] === 'budget') {
            $item['status'] = 'archived';
        }
    }
    unset($item);

    syncMap($map)->assertSuccessful()->expectsOutputToContain('hidden 3');

    expect(Website::count())->toBe(9)
        ->and(Website::where('hidden', true)->pluck('roster_id')->sort()->values()->all())
        ->toBe(['budget', 'newsite', 'newsite']);

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->has('sites', 6));

    syncMap()->assertSuccessful()->expectsOutputToContain('unhidden 3');
    expect(Website::where('hidden', true)->count())->toBe(0);
});

test('a dry run writes nothing', function () {
    handCards();

    syncMap(null, ['--dry-run' => true])->assertSuccessful()
        ->expectsOutputToContain('[dry run] matched 5, created 2');

    expect(Website::count())->toBe(7)
        ->and(Website::whereNotNull('roster_id')->count())->toBe(0);
});

test('the map can be read from a url', function () {
    Http::fake(['192.168.0.164:120/map.json' => Http::response(hubMap())]);

    $this->artisan('nav:sync-map', ['--source' => 'http://192.168.0.164:120/map.json'])
        ->assertSuccessful();

    expect(Website::where('roster_id', 'newsite')->count())->toBe(2);
});

test('a broken or empty map changes nothing', function () {
    handCards();
    syncMap()->assertSuccessful();

    syncMap(['items' => []])->assertFailed();
    $this->artisan('nav:sync-map', ['--source' => base_path('tests/Fixtures/missing.json')])->assertFailed();

    expect(Website::where('hidden', true)->count())->toBe(0);
});

test('the sync runs hourly', function () {
    $this->artisan('schedule:list')->assertSuccessful()->expectsOutputToContain('nav:sync-map');
});

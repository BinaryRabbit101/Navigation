<?php

namespace App\Console\Commands;

use App\Services\HubMapSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SyncHubMap extends Command
{
    protected $signature = 'nav:sync-map
        {--dry-run : Show what would change without writing anything}
        {--source= : map.json URL or file path (default: services.hub_map.source)}';

    protected $description = 'Build the home-page cards from the HUB map (map.json)';

    public function handle(HubMapSync $sync): int
    {
        $source = (string) ($this->option('source') ?: config('services.hub_map.source'));
        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $sync->sync($this->load($source), $dryRun, config('services.hub_map.self'));
        } catch (Throwable $e) {
            $this->error("nav:sync-map: {$e->getMessage()}");

            return self::FAILURE;
        }

        foreach ($result['lines'] as $kind => $lines) {
            $this->line(ucfirst($kind).':');

            foreach ($lines as $line) {
                $this->line("  {$line}");
            }
        }

        $summary = collect($result['counts'])->map(fn ($n, $k) => "{$k} {$n}")->implode(', ');
        $this->info(($dryRun ? '[dry run] ' : '')."{$summary} (from {$source})");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $source): array
    {
        if ($source === '') {
            throw new RuntimeException('no map source configured');
        }

        if (preg_match('#^https?://#i', $source)) {
            $map = Http::timeout(20)->get($source)->throw()->json();
        } else {
            if (! is_readable($source)) {
                throw new RuntimeException("cannot read {$source}");
            }

            $map = json_decode((string) file_get_contents($source), true);
        }

        if (! is_array($map)) {
            throw new RuntimeException("{$source} is not a JSON map");
        }

        return $map;
    }
}

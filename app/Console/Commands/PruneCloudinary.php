<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\CarListing;
use App\Models\SiteSetting;
use App\Services\CloudinaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Finds files in Cloudinary that nothing in the database points to any more
 * (abandoned forms, uploads from before automatic cleanup existed) and deletes them.
 *
 *   php artisan cloudinary:prune            → dry run, only lists them
 *   php artisan cloudinary:prune --force    → actually deletes them
 */
class PruneCloudinary extends Command
{
    protected $signature = 'cloudinary:prune {--force : Really delete (default is a dry run)} {--hours=24 : Never touch files newer than this}';

    protected $description = 'Delete Cloudinary files that no listing, article or setting uses';

    public function handle(CloudinaryService $cloudinary): int
    {
        if (! $cloudinary->configured()) {
            $this->error('Cloudinary is not configured.');

            return self::FAILURE;
        }

        $used = $cloudinary->assetsIn(
            CarListing::pluck('images')->all(),
            Article::get(['cover_image', 'content'])->map->only(['cover_image', 'content'])->all(),
            SiteSetting::pluck('value')->all(),
        );

        $cutoff = now()->subHours((int) $this->option('hours'));
        $orphans = [];

        foreach (['image', 'video'] as $type) {
            $cursor = null;
            do {
                $response = Http::withBasicAuth(config('cloudinary.api_key'), config('cloudinary.api_secret'))
                    ->timeout(30)
                    ->get('https://api.cloudinary.com/v1_1/'.config('cloudinary.cloud_name')."/resources/{$type}/upload", array_filter([
                        'prefix' => $cloudinary->root().'/',
                        'max_results' => 500,
                        'next_cursor' => $cursor,
                    ]));

                if ($response->failed()) {
                    $this->error("Cloudinary list ({$type}) failed: ".$response->body());

                    return self::FAILURE;
                }

                foreach ($response->json('resources', []) as $r) {
                    $key = "{$type}:{$r['public_id']}";
                    if (! isset($used[$key]) && now()->parse($r['created_at'])->lt($cutoff)) {
                        $orphans[$key] = ['public_id' => $r['public_id'], 'resource_type' => $type];
                    }
                }

                $cursor = $response->json('next_cursor');
            } while ($cursor);
        }

        $this->info(count($used).' file(s) in use, '.count($orphans).' unused.');

        foreach ($orphans as $key => $asset) {
            if (! $this->option('force')) {
                $this->line("  would delete  {$key}");

                continue;
            }
            $this->line(($cloudinary->destroy($asset['public_id'], $asset['resource_type']) ? '  deleted  ' : '  FAILED   ').$key);
        }

        if ($orphans && ! $this->option('force')) {
            $this->comment('Dry run. Re-run with --force to delete these.');
        }

        return self::SUCCESS;
    }
}

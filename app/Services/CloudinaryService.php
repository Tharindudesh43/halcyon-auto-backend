<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    private const URL_PATTERN = '#https://res\.cloudinary\.com/[^\s"\'<>()\[\]\\\\]+#i';

    private const ASSET_PATTERN = '#^https://res\.cloudinary\.com/([^/]+)/(image|video)/upload/(.+)$#i';

    /* ------------------------------------------------------------------ *
     |  Direct (browser → Cloudinary) signed uploads
     * ------------------------------------------------------------------ */

    public function configured(): bool
    {
        return filled(config('cloudinary.cloud_name'))
            && filled(config('cloudinary.api_key'))
            && filled(config('cloudinary.api_secret'));
    }

    /**
     * Everything the browser needs to upload ONE file straight to Cloudinary.
     * The folder, allowed formats and size cap are part of the signature, so the
     * browser cannot change them.
     *
     * @param  string  $kind  image | gif | video
     */
    public function signUpload(string $folder, string $kind): array
    {
        $resource = $kind === 'video' ? 'video' : 'image';

        $params = [
            'allowed_formats' => $resource === 'video' ? 'mp4,webm,mov' : 'jpg,jpeg,png,webp,gif',
            'folder' => $this->root().'/'.$folder,
            'timestamp' => time(),
        ];

        // Still photos: keep the original sharp but never store more than 2400 px.
        // (GIFs and videos are stored untouched so they stay animated / playable.)
        if ($kind === 'image') {
            $params['transformation'] = 'c_limit,h_2400,w_2400';
        }

        $params['signature'] = $this->sign($params);
        $params['api_key'] = config('cloudinary.api_key');

        return [
            'upload_url' => 'https://api.cloudinary.com/v1_1/'.config('cloudinary.cloud_name')."/{$resource}/upload",
            'fields' => $params,
        ];
    }

    /* ------------------------------------------------------------------ *
     |  URL  →  asset
     * ------------------------------------------------------------------ */

    /**
     * @return array{public_id: string, resource_type: string}|null  null = not one of OUR assets
     */
    public function locate(?string $url): ?array
    {
        if (! is_string($url) || ! preg_match(self::ASSET_PATTERN, trim($url), $m)) {
            return null;
        }

        [, $cloud, $resource, $path] = $m;

        if (strcasecmp($cloud, (string) config('cloudinary.cloud_name')) !== 0) {
            return null;
        }

        $segments = explode('/', explode('?', $path)[0]);

        // Drop transformation segments and the version:  [f_auto,w_800/]v123/…
        $version = null;
        foreach ($segments as $i => $segment) {
            if (preg_match('/^v\d+$/', $segment)) {
                $version = $i;
                break;
            }
        }
        if ($version !== null) {
            $segments = array_slice($segments, $version + 1);
        } else {
            while ($segments && preg_match('/^[a-z]{1,4}_[^\/]*(,[a-z]{1,4}_[^\/]*)*$/', $segments[0])) {
                array_shift($segments);
            }
        }

        if (! $segments) {
            return null;
        }

        $segments = array_map('rawurldecode', $segments);
        $last = array_pop($segments);
        $segments[] = preg_replace('/\.[A-Za-z0-9]+$/', '', $last);
        $publicId = implode('/', $segments);

        // Safety net: never touch anything outside our own root folder.
        if (! str_starts_with($publicId, $this->root().'/')) {
            return null;
        }

        return ['public_id' => $publicId, 'resource_type' => strtolower($resource)];
    }

    /** Every Cloudinary URL inside a string (e.g. markdown), array (e.g. a settings group) or nested mix. */
    public function urlsIn(mixed $value): array
    {
        if (is_string($value)) {
            return preg_match_all(self::URL_PATTERN, $value, $m) ? $m[0] : [];
        }

        if (is_array($value)) {
            $found = [];
            array_walk_recursive($value, function ($v) use (&$found) {
                if (is_string($v)) {
                    array_push($found, ...$this->urlsIn($v));
                }
            });

            return $found;
        }

        return [];
    }

    /** @return array<string, array{public_id: string, resource_type: string}> keyed "type:public_id" */
    public function assetsIn(mixed ...$values): array
    {
        $assets = [];
        foreach ($values as $value) {
            foreach ($this->urlsIn($value) as $url) {
                if ($asset = $this->locate($url)) {
                    $assets["{$asset['resource_type']}:{$asset['public_id']}"] = $asset;
                }
            }
        }

        return $assets;
    }

    /* ------------------------------------------------------------------ *
     |  Deleting
     * ------------------------------------------------------------------ */

    /** Delete everything in $old that is no longer present in $new. Runs after the response is sent. */
    public function purgeRemoved(mixed $old, mixed $new): void
    {
        $this->purgeAssets(array_diff_key($this->assetsIn($old), $this->assetsIn($new)));
    }

    /** Delete every Cloudinary asset referenced by $value. Runs after the response is sent. */
    public function purge(mixed ...$values): void
    {
        $this->purgeAssets($this->assetsIn(...$values));
    }

    /** @param  string[]  $urls */
    public function purgeUrls(array $urls): void
    {
        $this->purgeAssets($this->assetsIn($urls));
    }

    private function purgeAssets(array $assets): void
    {
        if (! $assets || ! $this->configured()) {
            return;
        }

        defer(function () use ($assets) {
            foreach ($assets as $asset) {
                $this->destroy($asset['public_id'], $asset['resource_type']);
            }
        });
    }

    public function destroy(string $publicId, string $resourceType = 'image'): bool
    {
        $params = [
            'invalidate' => 'true',
            'public_id' => $publicId,
            'timestamp' => time(),
        ];
        $params['signature'] = $this->sign($params);
        $params['api_key'] = config('cloudinary.api_key');

        try {
            $result = Http::asForm()->timeout(20)->retry(2, 500, throw: false)
                ->post('https://api.cloudinary.com/v1_1/'.config('cloudinary.cloud_name')."/{$resourceType}/destroy", $params)
                ->json('result');
        } catch (\Throwable $e) {
            Log::warning('Cloudinary destroy failed', ['public_id' => $publicId, 'error' => $e->getMessage()]);

            return false;
        }

        // "not found" = already gone, which is what we wanted.
        if (! in_array($result, ['ok', 'not found'], true)) {
            Log::warning('Cloudinary destroy rejected', ['public_id' => $publicId, 'result' => $result]);

            return false;
        }

        return true;
    }

    /* ------------------------------------------------------------------ */

    public function root(): string
    {
        return trim((string) config('cloudinary.root_folder'), '/');
    }

    private function sign(array $params): string
    {
        unset($params['file'], $params['cloud_name'], $params['resource_type'], $params['api_key']);
        ksort($params);

        $toSign = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');

        return sha1($toSign.config('cloudinary.api_secret'));
    }
}

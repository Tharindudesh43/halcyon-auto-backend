<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov'],
        ]);

        $file = $request->file('file');
        $mime = (string) $file->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');

        $maxBytes = $isVideo ? 40 * 1024 * 1024 : ($mime === 'image/gif' ? 10 * 1024 * 1024 : 5 * 1024 * 1024);
        if ($file->getSize() > $maxBytes) {
            $mb = (int) ($maxBytes / 1024 / 1024);

            return response()->json(['message' => "That file is too large — the limit is {$mb} MB."], 422);
        }

        $cloudName = config('services.snippet.cloudinary.cloud_name');
        $apiKey = config('services.snippet.cloudinary.api_key');
        $apiSecret = config('services.snippet.cloudinary.api_secret');

        $timestamp = time();
        $paramsToSign = ['folder' => 'onlyold_lk/listings', 'timestamp' => $timestamp];
        $signature = $this->sign($paramsToSign, $apiSecret);
        $resource = $isVideo ? 'video' : 'image';

        $response = Http::timeout(180)
            ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/{$resource}/upload", [
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'folder' => 'onlyold_lk/listings',
            ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Upload failed', 'detail' => $response->json()], 502);
        }

        return response()->json([
            'url' => $response->json('secure_url'),
            'public_id' => $response->json('public_id'),
            'type' => $resource,
        ]);
    }


    public function destroy(Request $request)
    {
        $request->validate(['public_id' => ['required', 'string']]);

        $cloudName = config('services.snippet.cloudinary.cloud_name');
        $apiKey = config('services.snippet.cloudinary.api_key');
        $apiSecret = config('services.snippet.cloudinary.api_secret');

        $timestamp = time();
        $paramsToSign = ['public_id' => $request->public_id, 'timestamp' => $timestamp];
        $signature = $this->sign($paramsToSign, $apiSecret);

        $response = Http::asForm()->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy", [
            'public_id' => $request->public_id,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]);

        return response()->json($response->json());
    }

    private function sign(array $params, string $apiSecret): string
    {
        ksort($params);
        $paramString = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');

        return sha1($paramString.$apiSecret);
    }
}

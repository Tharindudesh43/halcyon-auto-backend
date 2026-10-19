<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UploadController extends Controller
{
    /**
     * Step 1 of an upload: the browser asks for a signature, then sends the file
     * straight to Cloudinary. The file never passes through Next.js or PHP, so PHP's
     * upload_max_filesize / post_max_size / timeouts can no longer break big GIFs and videos.
     */
    public function sign(Request $request, CloudinaryService $cloudinary)
    {
        $data = $request->validate([
            'folder' => ['required', Rule::in(config('cloudinary.folders'))],
            'kind' => ['required', Rule::in(['image', 'gif', 'video'])],
        ]);

        if (! $cloudinary->configured()) {
            return response()->json(['message' => 'Image storage is not configured on the server.'], 503);
        }

        return response()->json($cloudinary->signUpload($data['folder'], $data['kind']));
    }

    /**
     * Delete files by URL. Used to roll back uploads when saving a form fails.
     * Only assets of OUR cloud under OUR root folder are ever deleted (see CloudinaryService::locate).
     */
    public function destroy(Request $request, CloudinaryService $cloudinary)
    {
        $data = $request->validate([
            'urls' => ['required', 'array', 'min:1', 'max:20'],
            'urls.*' => ['string', 'max:2048'],
        ]);

        $cloudinary->purgeUrls($data['urls']);

        return response()->json(['message' => 'Deleting'], 202);
    }
}

<?php

return [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),

    // Everything lives under  {root_folder}/{folder}/...   e.g. halcyon_auto/hero/abc123
    // Only assets under this root can ever be deleted by the API.
    'root_folder' => env('CLOUDINARY_ROOT_FOLDER', 'halcyon_auto'),

    // Folders the admin UI is allowed to upload into.
    'folders' => ['listings', 'hero', 'about', 'popup', 'articles'],
];

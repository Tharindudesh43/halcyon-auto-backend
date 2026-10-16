<?php
// Add these two arrays into your existing config/services.php,
// inside the main return [ ... ] array. Don't replace the whole file —
// just merge these two blocks in alongside whatever is already there.

return [

'cloudinary' => [
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
],

'resend' => [
    'key' => env('RESEND_API_KEY'),
    'from' => env('CONTACT_FROM_EMAIL'),
    'to' => env('CONTACT_TO_EMAIL'),
],
];

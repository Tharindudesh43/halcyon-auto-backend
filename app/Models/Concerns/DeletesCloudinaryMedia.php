<?php

namespace App\Models\Concerns;

use App\Services\CloudinaryService;

/**
 * Keeps Cloudinary in sync with the database:
 *  - record deleted            → all its images/videos are deleted from Cloudinary
 *  - image removed or replaced → the old file is deleted from Cloudinary
 *
 * The model lists the attributes that may hold Cloudinary URLs (a string, an array,
 * or markdown text) in $cloudinaryAttributes.
 */
trait DeletesCloudinaryMedia
{
    public static function bootDeletesCloudinaryMedia(): void
    {
        static::updated(function ($model) {
            // Compare ALL media attributes together, so an image that merely moved
            // (e.g. from cover_image into the article text) is not deleted.
            app(CloudinaryService::class)->purgeRemoved(
                array_map(fn ($a) => $model->getOriginal($a), $model->cloudinaryAttributes),
                array_map(fn ($a) => $model->getAttribute($a), $model->cloudinaryAttributes),
            );
        });

        static::deleted(function ($model) {
            app(CloudinaryService::class)->purge(
                array_map(fn ($a) => $model->getAttribute($a), $model->cloudinaryAttributes),
            );
        });
    }
}

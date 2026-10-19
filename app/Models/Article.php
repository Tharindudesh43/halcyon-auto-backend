<?php

namespace App\Models;

use App\Models\Concerns\DeletesCloudinaryMedia;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use DeletesCloudinaryMedia;

    /** Cover image + any pictures inserted into the markdown text. */
    public array $cloudinaryAttributes = ['cover_image', 'content'];

    public const CATEGORIES = [
        'Buying guides',
        'Maintenance & care',
        'Spare parts',
        'Car news',
        'Auto technology',
        'Finance & insurance',
    ];

    protected $fillable = [
        'title', 'slug', 'category', 'excerpt', 'content',
        'cover_image', 'author', 'is_published', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
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

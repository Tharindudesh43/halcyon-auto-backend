<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CarListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'brand', 'model', 'year', 'price', 'discount_percent', 'down_payment', 'mileage', 'cc',
        'power_source', 'transmission', 'location', 'description',
        'whatsapp', 'images', 'status', 'user_id', 'user_name', 'user_email',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'year' => 'integer',
        'discount_percent' => 'integer',
        'down_payment' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CarListing $car) {
            if (filled($car->slug)) {
                return;
            }

            $base = Str::slug("{$car->brand} {$car->model} {$car->year}") ?: Str::slug($car->title) ?: 'car';
            $slug = $base;
            for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $car->slug = $slug;
        });
    }
}

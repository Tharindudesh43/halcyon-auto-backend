<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends Model
{
    public const TYPES = ['whatsapp', 'call', 'test_drive'];

    protected $fillable = [
        'car_listing_id', 'car_title', 'type', 'name', 'phone', 'email',
        'preferred_date', 'message', 'source_path', 'handled_at',
    ];

    protected $casts = [
        'preferred_date' => 'date:Y-m-d',
        'handled_at' => 'datetime',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(CarListing::class, 'car_listing_id');
    }
}

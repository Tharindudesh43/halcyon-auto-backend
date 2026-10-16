<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerOpening extends Model
{
    protected $fillable = [
        'title', 'department', 'location', 'type', 'salary',
        'closing_date', 'description', 'requirements', 'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'closing_date' => 'date:Y-m-d',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianLocation extends Model
{
    protected $fillable = [
        'technician_profile_id',
        'lat',
        'lng',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'recorded_at' => 'datetime',
        ];
    }
}

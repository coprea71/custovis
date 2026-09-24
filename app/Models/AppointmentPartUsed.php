<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentPartUsed extends Model
{
    protected $table = 'appointment_parts_used';

    protected $fillable = [
        'appointment_id',
        'description',
        'quantity',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
        ];
    }
}

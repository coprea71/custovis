<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianShift extends Model
{
    public const WEEKDAYS = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

    protected $fillable = [
        'technician_profile_id',
        'weekday',
        'starts_at',
        'ends_at',
    ];
}

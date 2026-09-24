<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianAbsence extends Model
{
    public const REASONS = ['vacation' => 'Urlaub', 'sick' => 'Krankheit', 'other' => 'Sonstiges'];

    protected $fillable = [
        'technician_profile_id',
        'starts_on',
        'ends_on',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentSignature extends Model
{
    public const CONTEXTS = ['checklist', 'delivery'];

    protected $fillable = [
        'appointment_id',
        'context',
        'signer_name',
        'disk',
        'path',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }
}

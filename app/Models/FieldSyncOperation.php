<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldSyncOperation extends Model
{
    public const APPLIED = 'applied';

    public const CONFLICT = 'conflict';

    public const REJECTED = 'rejected';

    protected $fillable = [
        'technician_profile_id',
        'operation_uuid',
        'type',
        'result',
        'message',
    ];
}

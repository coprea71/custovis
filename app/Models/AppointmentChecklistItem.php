<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id',
        'label',
        'position',
        'checked',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'checked' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AppointmentChecklist, $this>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(AppointmentChecklist::class, 'checklist_id');
    }
}

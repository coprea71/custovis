<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * appointment_id = null: admin template; otherwise the appointment's copy.
 */
class AppointmentChecklist extends Model
{
    protected $fillable = [
        'appointment_id',
        'name',
    ];

    /**
     * @return HasMany<AppointmentChecklistItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AppointmentChecklistItem::class, 'checklist_id')->orderBy('position');
    }

    /**
     * @param  Builder<AppointmentChecklist>  $query
     */
    public function scopeTemplates(Builder $query): void
    {
        $query->whereNull('appointment_id');
    }
}

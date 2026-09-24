<?php

namespace App\Models;

use App\States\Appointment\AppointmentState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\ModelStates\HasStates;

class ServiceAppointment extends Model
{
    use HasStates;

    public const KIND_SERVICE = 'service';

    public const KIND_DELIVERY = 'delivery';

    public const KINDS = [self::KIND_SERVICE, self::KIND_DELIVERY];

    protected $fillable = [
        'ticket_id',
        'technician_profile_id',
        'kind',
        'state',
        'scheduled_start',
        'scheduled_end',
        'address',
        'lat',
        'lng',
        'required_skill_ids',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'state' => AppointmentState::class,
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'lat' => 'float',
            'lng' => 'float',
            'required_skill_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<TechnicianProfile, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_profile_id');
    }

    /**
     * @return HasMany<AppointmentChecklist, $this>
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(AppointmentChecklist::class, 'appointment_id');
    }

    /**
     * @return HasMany<AppointmentSignature, $this>
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(AppointmentSignature::class, 'appointment_id');
    }

    /**
     * @return HasMany<AppointmentPartUsed, $this>
     */
    public function partsUsed(): HasMany
    {
        return $this->hasMany(AppointmentPartUsed::class, 'appointment_id');
    }

    /**
     * @return HasOne<AppointmentDelivery, $this>
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(AppointmentDelivery::class, 'appointment_id');
    }

    public function stateKey(): string
    {
        return AppointmentState::keyOf($this->state::class);
    }
}

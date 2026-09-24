<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TechnicianProfile extends Model
{
    use Auditable;

    /**
     * Mirrors the column defaults so freshly created models behave like loaded ones.
     */
    protected $attributes = [
        'active' => true,
        'location_tracking_consent' => false,
    ];

    protected array $auditFields = [
        'user_id',
        'active',
        'location_tracking_consent',
    ];

    protected array $auditEvents = [
        'updated',
        'deleted',
    ];

    protected $fillable = [
        'user_id',
        'home_address',
        'home_lat',
        'home_lng',
        'active',
        'location_tracking_consent',
    ];

    protected function casts(): array
    {
        return [
            'home_lat' => 'float',
            'home_lng' => 'float',
            'active' => 'boolean',
            'location_tracking_consent' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'technician_skill')->withPivot('level')->withTimestamps();
    }

    /**
     * @return HasMany<TechnicianShift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(TechnicianShift::class);
    }

    /**
     * @return HasMany<TechnicianAbsence, $this>
     */
    public function absences(): HasMany
    {
        return $this->hasMany(TechnicianAbsence::class);
    }

    /**
     * @return HasMany<ServiceAppointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(ServiceAppointment::class);
    }

    /**
     * @return HasMany<TechnicianLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(TechnicianLocation::class);
    }

    /**
     * @return HasOne<TechnicianLocation, $this>
     */
    public function latestLocation(): HasOne
    {
        return $this->hasOne(TechnicianLocation::class)->latestOfMany('recorded_at');
    }
}

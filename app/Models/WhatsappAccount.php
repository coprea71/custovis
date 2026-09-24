<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappAccount extends Model
{
    use Auditable, HasFactory;

    protected array $auditFields = [
        'team_id',
        'display_name',
        'phone_number_id',
        'business_account_id',
        'active',
    ];

    protected array $auditSecretFields = [
        'access_token',
        'webhook_verify_token',
        'app_secret',
    ];

    protected array $auditEvents = [
        'updated',
        'deleted',
    ];

    // Credentials must never leak through serialisation (Livewire/API payloads).
    protected $hidden = [
        'access_token',
        'webhook_verify_token',
        'app_secret',
    ];

    protected $fillable = [
        'team_id',
        'display_name',
        'phone_number_id',
        'business_account_id',
        'access_token',
        'webhook_verify_token',
        'app_secret',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'webhook_verify_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<WhatsappTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(WhatsappTemplate::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}

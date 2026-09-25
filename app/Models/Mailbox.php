<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mailbox extends Model
{
    use Auditable, HasFactory;

    protected array $auditFields = [
        'team_id',
        'name',
        'email_address',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'active',
    ];

    protected array $auditSecretFields = [
        'imap_password',
        'smtp_password',
    ];

    // Credentials must never leak through serialisation (Livewire/API payloads).
    protected $hidden = [
        'imap_password',
        'smtp_password',
    ];

    protected $fillable = [
        'team_id',
        'name',
        'email_address',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'active',
        'last_fetched_at',
        'last_fetch_error',
        'last_fetch_error_at',
    ];

    protected function casts(): array
    {
        return [
            'imap_password' => 'encrypted',
            'smtp_password' => 'encrypted',
            'active' => 'boolean',
            'last_fetched_at' => 'datetime',
            'last_fetch_error_at' => 'datetime',
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
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithFetchError(Builder $query): void
    {
        $query->where('active', true)->whereNotNull('last_fetch_error');
    }
}

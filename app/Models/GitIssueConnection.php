<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitIssueConnection extends Model
{
    use Auditable, HasFactory;

    public const PROVIDER_GITHUB = 'github';

    public const PROVIDER_GITLAB = 'gitlab';

    public const SYNC_WEBHOOK = 'webhook';

    public const SYNC_POLL = 'poll';

    protected array $auditFields = [
        'team_id',
        'provider',
        'repository',
        'sync_mode',
    ];

    protected array $auditSecretFields = [
        'access_token',
        'webhook_secret',
    ];

    protected array $auditEvents = [
        'updated',
        'deleted',
    ];

    // Credentials must never leak through serialisation (Livewire/API payloads).
    protected $hidden = [
        'access_token',
        'webhook_secret',
    ];

    protected $fillable = [
        'team_id',
        'provider',
        'repository',
        'access_token',
        'webhook_secret',
        'sync_mode',
        'last_synced_at',
        'created_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'last_synced_at' => 'datetime',
            'revoked_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function externalRefPrefix(): string
    {
        return "{$this->provider}:{$this->repository}";
    }
}

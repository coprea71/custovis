<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitIssueConnection extends Model
{
    use HasFactory;

    public const PROVIDER_GITHUB = 'github';

    public const PROVIDER_GITLAB = 'gitlab';

    public const SYNC_WEBHOOK = 'webhook';

    public const SYNC_POLL = 'poll';

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

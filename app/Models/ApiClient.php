<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

class ApiClient extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'created_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
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

    /**
     * KB categories this key may search via MCP (13.md, fail closed when empty).
     *
     * @return BelongsToMany<KnowledgeBaseCategory, $this>
     */
    public function kbCategories(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBaseCategory::class, 'api_client_kb_categories')->withTimestamps();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
        $this->tokens()->delete();
    }
}

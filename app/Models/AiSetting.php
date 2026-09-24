<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSetting extends Model
{
    use Auditable;

    public const USE_CASES = ['summarize', 'suggest_reply', 'classify', 'embed'];

    public const PROVIDERS = ['openai', 'anthropic', 'ollama', 'custom'];

    protected array $auditFields = [
        'team_id',
        'use_case',
        'provider',
        'endpoint_url',
        'model',
        'redact_pii',
    ];

    protected array $auditSecretFields = [
        'api_key',
    ];

    // Credentials must never leak through serialisation (Livewire/API payloads).
    protected $hidden = [
        'api_key',
    ];

    protected $fillable = [
        'team_id',
        'use_case',
        'provider',
        'api_key',
        'endpoint_url',
        'model',
        'redact_pii',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'redact_pii' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}

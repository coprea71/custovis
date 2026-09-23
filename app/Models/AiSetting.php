<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSetting extends Model
{
    public const USE_CASES = ['summarize', 'suggest_reply', 'classify', 'embed'];

    public const PROVIDERS = ['openai', 'anthropic', 'ollama', 'custom'];

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

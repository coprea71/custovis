<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpConnection extends Model
{
    public const TYPE_ODOO = 'odoo';

    public const TYPE_SHOPWARE = 'shopware';

    public const TYPES = [self::TYPE_ODOO, self::TYPE_SHOPWARE];

    protected $fillable = [
        'team_id',
        'name',
        'type',
        'base_url',
        'auth_payload',
        'field_mapping',
        'is_active',
        'created_by',
    ];

    // Credentials must never leak through serialisation (API/Livewire payloads).
    protected $hidden = ['auth_payload'];

    protected function casts(): array
    {
        return [
            'auth_payload' => 'encrypted:array',
            'field_mapping' => 'array',
            'is_active' => 'boolean',
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
     * Remote field names the admin explicitly released for display.
     *
     * @return list<string>
     */
    public function mappedFields(): array
    {
        return array_keys($this->field_mapping ?? []);
    }

    public function credential(string $key): string
    {
        return (string) ($this->auth_payload[$key] ?? '');
    }

    public function endpoint(string $path): string
    {
        return rtrim($this->base_url, '/').'/'.ltrim($path, '/');
    }
}

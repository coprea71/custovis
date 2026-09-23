<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmdbCiRelation extends Model
{
    protected $fillable = [
        'source_ci_id',
        'target_ci_id',
        'relation_type',
    ];

    /**
     * @return BelongsTo<CmdbConfigurationItem, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(CmdbConfigurationItem::class, 'source_ci_id');
    }

    /**
     * @return BelongsTo<CmdbConfigurationItem, $this>
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(CmdbConfigurationItem::class, 'target_ci_id');
    }
}

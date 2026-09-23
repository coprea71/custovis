<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEmbedding extends Model
{
    protected $fillable = [
        'ticket_id',
        'vector',
        'provider',
    ];

    protected function casts(): array
    {
        return [
            'vector' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}

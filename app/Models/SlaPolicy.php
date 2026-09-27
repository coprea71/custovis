<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaPolicy extends Model
{
    protected $fillable = [
        'team_id',
        'customer_id',
        'name',
        'priority',
        'response_time_minutes',
        'resolution_time_minutes',
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Customer SLA first, then the team's policy of the same priority.
     */
    public static function applicableTo(Ticket $ticket): ?self
    {
        $byPriority = self::query()->where('priority', $ticket->priority);

        return ($ticket->customer_id ? (clone $byPriority)->where('customer_id', $ticket->customer_id)->first() : null)
            ?? $byPriority->where('team_id', $ticket->team_id)->whereNull('customer_id')->first();
    }
}

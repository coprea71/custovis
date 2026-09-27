<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'work_date', 'minutes', 'started_at', 'description', 'billable'];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'started_at' => 'datetime',
            'billable' => 'boolean',
            'minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRunning(): bool
    {
        return $this->minutes === null;
    }

    /**
     * Invoiced time is the basis of an issued invoice and must stay unchanged (GoBD).
     */
    public function isLocked(): bool
    {
        return $this->invoice_item_id !== null;
    }

    /**
     * @param  Builder<TimeEntry>  $query
     */
    public function scopeOpenForBilling(Builder $query): void
    {
        $query->where('billable', true)->whereNotNull('minutes')->whereNull('invoice_item_id');
    }
}

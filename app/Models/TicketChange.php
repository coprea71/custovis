<?php

namespace App\Models;

use App\States\Change\ChangeState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

class TicketChange extends Model
{
    use HasStates;

    protected $primaryKey = 'ticket_id';

    public $incrementing = false;

    protected $fillable = [
        'ticket_id',
        'state',
        'change_type',
        'risk_level',
        'planned_start',
        'planned_end',
    ];

    protected function casts(): array
    {
        return [
            'state' => ChangeState::class,
            'planned_start' => 'datetime',
            'planned_end' => 'datetime',
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
     * @return HasMany<CabApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(CabApproval::class, 'ticket_id', 'ticket_id');
    }
}

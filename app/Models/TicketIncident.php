<?php

namespace App\Models;

use App\States\Incident\IncidentState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

class TicketIncident extends Model
{
    use HasStates;

    protected $primaryKey = 'ticket_id';

    public $incrementing = false;

    protected $fillable = [
        'ticket_id',
        'state',
        'impact',
        'urgency',
    ];

    protected function casts(): array
    {
        return [
            'state' => IncidentState::class,
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

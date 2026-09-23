<?php

namespace App\Models;

use App\States\Problem\ProblemState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

class TicketProblem extends Model
{
    use HasStates;

    protected $primaryKey = 'ticket_id';

    public $incrementing = false;

    protected $fillable = [
        'ticket_id',
        'state',
        'root_cause',
    ];

    protected function casts(): array
    {
        return [
            'state' => ProblemState::class,
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

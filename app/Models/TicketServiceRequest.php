<?php

namespace App\Models;

use App\States\ServiceRequest\ServiceRequestState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

class TicketServiceRequest extends Model
{
    use HasStates;

    protected $primaryKey = 'ticket_id';

    public $incrementing = false;

    protected $fillable = [
        'ticket_id',
        'state',
        'service_catalog_item_id',
    ];

    protected function casts(): array
    {
        return [
            'state' => ServiceRequestState::class,
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
     * @return BelongsTo<ServiceCatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(ServiceCatalogItem::class, 'service_catalog_item_id');
    }
}

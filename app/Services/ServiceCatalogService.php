<?php

namespace App\Services;

use App\Models\ServiceCatalogItem;
use App\Models\Ticket;
use App\Models\TicketServiceRequest;

class ServiceCatalogService
{
    public function createRequest(ServiceCatalogItem $item, ?string $requesterEmail = null, ?string $requesterName = null): Ticket
    {
        $ticket = Ticket::query()->create([
            'team_id' => $item->team_id,
            'type' => 'service_request',
            'source' => 'service_catalog',
            'subject' => $item->name,
            'requester_email' => $requesterEmail,
            'requester_name' => $requesterName,
        ]);

        TicketServiceRequest::query()->create([
            'ticket_id' => $ticket->id,
            'service_catalog_item_id' => $item->id,
        ]);

        return $ticket;
    }
}

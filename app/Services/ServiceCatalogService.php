<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ServiceCatalogItem;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketServiceRequest;
use Illuminate\Support\Facades\DB;

class ServiceCatalogService
{
    public function createRequest(
        ServiceCatalogItem $item,
        ?string $requesterEmail = null,
        ?string $requesterName = null,
        ?Customer $customer = null,
        ?string $description = null,
    ): Ticket {
        return DB::transaction(function () use ($item, $requesterEmail, $requesterName, $customer, $description) {
            $ticket = Ticket::query()->create([
                'team_id' => $item->team_id,
                'type' => 'service_request',
                'source' => 'service_catalog',
                'subject' => $item->name,
                'customer_id' => $customer?->id,
                'requester_email' => $requesterEmail,
                'requester_name' => $requesterName,
            ]);

            TicketServiceRequest::query()->create([
                'ticket_id' => $ticket->id,
                'service_catalog_item_id' => $item->id,
            ]);

            if ($description !== null) {
                $ticket->messages()->create([
                    'visibility' => TicketMessage::VISIBILITY_PUBLIC,
                    'direction' => 'incoming',
                    'author_customer_id' => $customer?->id,
                    'body_text' => $description,
                    'body_html' => nl2br(e($description)),
                ]);
            }

            return $ticket;
        });
    }
}

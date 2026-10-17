<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExternalTicketRequest;
use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\TicketAutoMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketApiController extends Controller
{
    public function store(StoreExternalTicketRequest $request, TicketAutoMailService $autoMails): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->user();

        $ticket = Ticket::query()->create([
            'team_id' => $client->team_id,
            'mailbox_id' => $this->sendingMailboxId($client),
            'type' => 'support_ticket',
            'source' => 'api',
            'subject' => $request->string('subject'),
            'priority' => $request->input('priority', 'normal'),
            'requester_email' => $request->string('requester_email'),
            'requester_name' => $request->input('requester_name'),
        ]);

        $ticket->messages()->create([
            'visibility' => 'public',
            'direction' => 'incoming',
            'external_author_name' => $request->input('requester_name'),
            'external_author_email' => $request->string('requester_email'),
            'body_text' => $request->string('body'),
            'message_id' => TicketMessage::newMessageId(),
        ]);

        $autoMails->acknowledge($ticket);

        return response()->json(['data' => $this->payload($ticket)], 201);
    }

    /**
     * A key only sees its own team's tickets; foreign ones are reported as
     * missing, so ticket numbers of other teams cannot be probed.
     */
    public function show(Request $request, int $ticket): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->user();
        $found = Ticket::query()->where('team_id', $client->team_id)->find($ticket);

        abort_if($found === null, 404, 'Ticket nicht gefunden.');

        return response()->json(['data' => $this->payload($found)]);
    }

    /**
     * Only an active mailbox of the key's own team may send, even if the key
     * still points to a mailbox that was deactivated or moved.
     */
    private function sendingMailboxId(ApiClient $client): ?int
    {
        $mailbox = $client->mailbox;

        return $mailbox && $mailbox->active && $mailbox->team_id === $client->team_id ? $mailbox->id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'status_label' => Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'updated_at' => $ticket->updated_at?->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
        ];
    }
}

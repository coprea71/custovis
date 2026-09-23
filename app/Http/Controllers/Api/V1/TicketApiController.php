<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExternalTicketRequest;
use App\Models\ApiClient;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketApiController extends Controller
{
    public function __invoke(StoreExternalTicketRequest $request): JsonResponse
    {
        /** @var ApiClient $client */
        $client = $request->user();

        $ticket = Ticket::query()->create([
            'team_id' => $client->team_id,
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
            'message_id' => 'api-'.$ticket->id.'-'.uniqid('', true).'@'.parse_url(config('app.url'), PHP_URL_HOST),
        ]);

        return response()->json([
            'data' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
            ],
        ], 201);
    }
}

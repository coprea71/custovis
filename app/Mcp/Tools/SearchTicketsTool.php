<?php

namespace App\Mcp\Tools;

use App\Models\ApiClient;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Sucht offene Tickets zur Rufnummer eines Anrufers (für Rückrufe). Cursor-paginiert.')]
class SearchTicketsTool extends TeamScopedTool
{
    protected string $name = 'search_tickets';

    private const PAGE_SIZE = 10;

    public function schema(JsonSchema $schema): array
    {
        return [
            'caller_phone' => $schema->string()->description('Rufnummer des Anrufers')->required(),
            'cursor' => $schema->string()->description('next_cursor aus der vorherigen Antwort'),
        ];
    }

    protected function perform(Request $request, ApiClient $client): array
    {
        $data = $request->validate([
            'caller_phone' => ['required', 'string', CreateTicketTool::PHONE_RULE],
            'cursor' => ['nullable', 'string', 'max:500'],
        ]);

        $page = Ticket::query()
            ->where('team_id', $client->team_id)
            ->where('status', '!=', 'closed')
            ->where('requester_phone', $data['caller_phone'])
            ->orderByDesc('id')
            ->cursorPaginate(self::PAGE_SIZE, ['id', 'subject', 'status', 'priority', 'created_at'], 'cursor', $data['cursor'] ?? null);

        return [
            'tickets' => collect($page->items())->map(fn (Ticket $ticket) => [
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at->toIso8601String(),
            ])->all(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ];
    }
}

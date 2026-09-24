<?php

namespace App\Mcp\Tools;

use App\Mcp\ToolException;
use App\Models\ApiClient;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Liefert Status und Priorität eines Tickets des eigenen Teams.')]
class GetTicketStatusTool extends TeamScopedTool
{
    protected string $name = 'get_ticket_status';

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('Ticketnummer')->required(),
        ];
    }

    protected function perform(Request $request, ApiClient $client): array
    {
        $data = $request->validate(['ticket_id' => ['required', 'integer', 'min:1']]);

        $ticket = self::findForClient($client, (int) $data['ticket_id']);

        return [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'updated_at' => $ticket->updated_at->toIso8601String(),
        ];
    }

    /**
     * Foreign-team tickets are reported exactly like missing ones, so a key
     * cannot probe which ticket ids exist in other teams.
     */
    public static function findForClient(ApiClient $client, int $ticketId): Ticket
    {
        return Ticket::query()->where('team_id', $client->team_id)->find($ticketId)
            ?? throw ToolException::notFound("Ticket {$ticketId} wurde nicht gefunden.");
    }
}

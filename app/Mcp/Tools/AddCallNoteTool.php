<?php

namespace App\Mcp\Tools;

use App\Models\ApiClient;
use App\Models\TicketMessage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Hängt eine Anruf-Zusammenfassung oder ein Transkript als interne Notiz an ein bestehendes Ticket an.')]
class AddCallNoteTool extends TeamScopedTool
{
    protected string $name = 'add_call_note';

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('Ticketnummer')->required(),
            'note' => $schema->string()->description('Zusammenfassung oder Transkript des Anrufs')->required(),
        ];
    }

    protected function perform(Request $request, ApiClient $client): array
    {
        $data = $request->validate([
            'ticket_id' => ['required', 'integer', 'min:1'],
            'note' => ['required', 'string', 'max:50000'],
        ]);

        $ticket = GetTicketStatusTool::findForClient($client, (int) $data['ticket_id']);

        $message = $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
            'direction' => 'incoming',
            'external_author_name' => "Telefonassistent ({$client->name})",
            'body_text' => $data['note'],
            'body_html' => nl2br(e($data['note'])),
        ]);

        return ['ticket_id' => $ticket->id, 'note_id' => $message->id];
    }
}

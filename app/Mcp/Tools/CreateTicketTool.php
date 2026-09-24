<?php

namespace App\Mcp\Tools;

use App\Models\ApiClient;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Legt ein Ticket für einen Anrufer an. Bei Wiederholung mit demselben idempotency_key (z. B. Call-ID) wird kein zweites Ticket erzeugt.')]
class CreateTicketTool extends TeamScopedTool
{
    protected string $name = 'create_ticket';

    public const PHONE_RULE = 'regex:/^\+?[0-9 ()\/-]{5,30}$/';

    public function schema(JsonSchema $schema): array
    {
        return [
            'caller_phone' => $schema->string()->description('Rufnummer des Anrufers, z. B. +49 30 1234567')->required(),
            'summary' => $schema->string()->description('Zusammenfassung des Anliegens')->required(),
            'idempotency_key' => $schema->string()->description('Eindeutige ID des Anrufs, verhindert Duplikate bei Retry')->required(),
            'caller_name' => $schema->string()->description('Name des Anrufers, falls bekannt'),
            'priority' => $schema->string()->enum(Ticket::PRIORITIES)->description('Priorität, Standard: normal'),
        ];
    }

    protected function perform(Request $request, ApiClient $client): array
    {
        $data = $request->validate([
            'caller_phone' => ['required', 'string', self::PHONE_RULE],
            'summary' => ['required', 'string', 'max:10000'],
            'idempotency_key' => ['required', 'string', 'max:100'],
            'caller_name' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(Ticket::PRIORITIES)],
        ]);

        // Scoped to the key, so two clients can never collide on (or probe) each other's call ids.
        $externalRef = "mcp:{$client->id}:{$data['idempotency_key']}";
        $existing = $this->existing($externalRef);

        if ($existing) {
            return ['ticket_id' => $existing->id, 'status' => $existing->status, 'duplicate' => true];
        }

        try {
            $ticket = $this->createTicket($client, $data, $externalRef);
        } catch (UniqueConstraintViolationException) {
            $ticket = $this->existing($externalRef); // parallel retry won the race

            return ['ticket_id' => $ticket->id, 'status' => $ticket->status, 'duplicate' => true];
        }

        return ['ticket_id' => $ticket->id, 'status' => $ticket->status, 'duplicate' => false];
    }

    private function existing(string $externalRef): ?Ticket
    {
        return Ticket::query()->where('source', 'phone')->where('external_ref', $externalRef)->first();
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function createTicket(ApiClient $client, array $data, string $externalRef): Ticket
    {
        return DB::transaction(function () use ($client, $data, $externalRef) {
            $ticket = Ticket::query()->create([
                'team_id' => $client->team_id,
                'type' => 'support_ticket',
                'source' => 'phone',
                'external_ref' => $externalRef,
                'subject' => Str::limit($data['summary'], 120),
                'priority' => $data['priority'] ?? 'normal',
                'requester_phone' => $data['caller_phone'],
                'requester_name' => $data['caller_name'] ?? null,
            ]);

            $ticket->messages()->create([
                'visibility' => TicketMessage::VISIBILITY_PUBLIC,
                'direction' => 'incoming',
                'external_author_name' => $data['caller_name'] ?? $data['caller_phone'],
                'body_text' => $data['summary'],
                'body_html' => nl2br(e($data['summary'])),
            ]);

            return $ticket;
        });
    }
}

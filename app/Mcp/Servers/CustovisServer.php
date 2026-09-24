<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddCallNoteTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\GetTicketStatusTool;
use App\Mcp\Tools\SearchKnowledgeBaseTool;
use App\Mcp\Tools\SearchTicketsTool;
use Laravel\Mcp\Server;

/**
 * Global MCP endpoint for AI phone assistants (13.md). One endpoint for the
 * whole installation; every call is scoped to the team of the API key.
 */
class CustovisServer extends Server
{
    protected string $name = 'Custovis Service Suite';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
        Ticketsystem-Anbindung für Telefonassistenten. Lege pro Anruf höchstens
        ein Ticket an (idempotency_key = Call-ID), suche vorher offene Tickets
        zur Rufnummer und beantworte Standardfragen über search_knowledge_base.
        Fehler mit "client_error" -> beim Anrufer nachfragen, "not_found" ->
        Ticketnummer prüfen, "server_error" -> an einen Menschen übergeben.
    MARKDOWN;

    protected array $capabilities = [
        self::CAPABILITY_TOOLS => ['listChanged' => false],
    ];

    protected array $tools = [
        CreateTicketTool::class,
        SearchTicketsTool::class,
        GetTicketStatusTool::class,
        AddCallNoteTool::class,
        SearchKnowledgeBaseTool::class,
    ];
}

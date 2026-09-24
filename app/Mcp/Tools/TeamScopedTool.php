<?php

namespace App\Mcp\Tools;

use App\Mcp\ToolException;
use App\Models\ApiClient;
use App\Models\AuditLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

/**
 * Base for all Custovis MCP tools: re-checks authorization on every call
 * (not only at connect), derives the team solely from the API key, maps
 * failures to structured error classes and writes one audit entry per call.
 */
abstract class TeamScopedTool extends Tool
{
    /**
     * @return array<string, mixed> structured result for the voice agent
     */
    abstract protected function perform(Request $request, ApiClient $client): array;

    public function handle(Request $request): Response
    {
        $client = $this->authorizedClient();

        try {
            $result = $this->perform($request, $client);
            $this->audit($client, true);

            return Response::json($result);
        } catch (ValidationException $e) {
            return $this->fail($client, ToolException::CLIENT_ERROR, collect($e->errors())->flatten()->implode(' '));
        } catch (ToolException $e) {
            return $this->fail($client, $e->errorClass, $e->getMessage());
        } catch (Throwable $e) {
            Log::error('MCP tool failed', ['tool' => $this->name(), 'exception' => $e::class]);
            report($e);

            return $this->fail($client, ToolException::SERVER_ERROR, 'Interner Fehler, bitte an einen Mitarbeiter übergeben.');
        }
    }

    /**
     * Read from the sanctum guard directly: ApiClient is a token owner, not
     * an Authenticatable, so Laravel\Mcp\Request::user() cannot return it.
     */
    private function authorizedClient(): ApiClient
    {
        $client = Auth::guard('sanctum')->user();

        if (! $client instanceof ApiClient || $client->isRevoked() || ! $client->tokenCan('mcp.tools.use')) {
            throw new AuthorizationException('API-Key ist nicht für MCP freigeschaltet.');
        }

        return $client;
    }

    private function fail(ApiClient $client, string $errorClass, string $message): Response
    {
        $this->audit($client, false, $errorClass);

        return Response::error("{$errorClass}: {$message}");
    }

    private function audit(ApiClient $client, bool $success, ?string $errorClass = null): void
    {
        AuditLog::record('mcp.tool_called', null, $client->team, $client, array_filter([
            'tool' => $this->name(),
            'success' => $success,
            'error_class' => $errorClass,
        ], fn ($value) => $value !== null));
    }
}

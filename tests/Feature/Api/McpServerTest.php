<?php

namespace Tests\Feature\Api;

use App\Livewire\Agent\Team\ApiKeyManager;
use App\Models\AuditLog;
use App\Models\KnowledgeBaseCategory;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\KnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $teamAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->teamAdmin = User::factory()->create();
        $this->team->users()->attach($this->teamAdmin, ['role_in_team' => 'team_admin']);
    }

    public function test_key_needs_mcp_ability_and_rest_only_key_is_rejected(): void
    {
        $restOnly = $this->createKey(rest: true, mcp: false);
        $mcpOnly = $this->createKey(rest: false, mcp: true);

        $this->rpc($restOnly, 'tools/list')->assertForbidden();
        $this->rpc($mcpOnly, 'tools/list')->assertOk()->assertJsonFragment(['name' => 'create_ticket']);
        $this->postJson('/api/v1/tickets', [], ['Authorization' => "Bearer {$mcpOnly}"])->assertForbidden();
        $this->rpc(null, 'tools/list')->assertUnauthorized();
    }

    public function test_create_ticket_is_idempotent_per_call_id(): void
    {
        $key = $this->createKey();
        $arguments = ['caller_phone' => '+49 30 123456', 'summary' => 'Drucker defekt', 'idempotency_key' => 'call-42', 'priority' => 'high'];

        $first = $this->callTool($key, 'create_ticket', $arguments);
        $retry = $this->callTool($key, 'create_ticket', $arguments);

        $this->assertFalse($first['duplicate']);
        $this->assertTrue($retry['duplicate']);
        $this->assertSame($first['ticket_id'], $retry['ticket_id']);
        $this->assertSame(1, Ticket::query()->count());
        $this->assertDatabaseHas('tickets', ['source' => 'phone', 'team_id' => $this->team->id, 'requester_phone' => '+49 30 123456', 'priority' => 'high']);
    }

    public function test_tools_only_see_tickets_of_the_keys_team(): void
    {
        $key = $this->createKey();
        $foreignTeam = Team::query()->create(['name' => 'Fremd', 'slug' => 'fremd']);
        $own = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'phone', 'subject' => 'Eigenes', 'requester_phone' => '+4930111']);
        $foreign = Ticket::query()->create(['team_id' => $foreignTeam->id, 'source' => 'phone', 'subject' => 'Fremdes', 'requester_phone' => '+4930111']);

        $found = $this->callTool($key, 'search_tickets', ['caller_phone' => '+4930111']);
        $this->assertSame([$own->id], array_column($found['tickets'], 'ticket_id'));

        $this->assertSame('Eigenes', $this->callTool($key, 'get_ticket_status', ['ticket_id' => $own->id])['subject']);
        $this->callToolRaw($key, 'get_ticket_status', ['ticket_id' => $foreign->id])
            ->assertJsonPath('result.isError', true)
            ->assertJsonPath('result.content.0.text', "not_found: Ticket {$foreign->id} wurde nicht gefunden.");
        $this->callToolRaw($key, 'add_call_note', ['ticket_id' => $foreign->id, 'note' => 'x'])->assertJsonPath('result.isError', true);
        $this->assertSame(0, $foreign->messages()->count());
    }

    public function test_call_note_is_stored_as_internal_note_and_every_call_is_audited(): void
    {
        $key = $this->createKey();
        $ticket = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'phone', 'subject' => 'Rückruf']);

        $this->callTool($key, 'add_call_note', ['ticket_id' => $ticket->id, 'note' => 'Kunde bestätigt Termin']);
        $this->callToolRaw($key, 'create_ticket', ['caller_phone' => 'kein-telefon', 'summary' => 'x', 'idempotency_key' => 'a'])
            ->assertJsonPath('result.isError', true);

        $this->assertDatabaseHas('ticket_messages', ['ticket_id' => $ticket->id, 'visibility' => 'internal_note', 'body_text' => 'Kunde bestätigt Termin']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'mcp.tool_called', 'team_id' => $this->team->id]);
        $this->assertSame(2, AuditLog::query()->where('action', 'mcp.tool_called')->count());
    }

    public function test_knowledge_base_search_is_limited_to_whitelisted_categories(): void
    {
        $phoneFaq = KnowledgeBaseCategory::query()->create(['name' => 'Telefon-FAQ']);
        $phoneSub = KnowledgeBaseCategory::query()->create(['name' => 'Öffnungszeiten', 'parent_id' => $phoneFaq->id]);
        $other = KnowledgeBaseCategory::query()->create(['name' => 'Technik']);
        $this->article($phoneSub, 'Öffnungszeiten Hotline', 'public');
        $this->article($other, 'Hotline-Server Troubleshooting', 'public');
        $this->article($phoneFaq, 'Hotline intern', 'internal');

        $scoped = $this->createKey(kbCategoryIds: [$phoneFaq->id]);
        $unscoped = $this->createKey();

        $this->assertSame(['Öffnungszeiten Hotline'], array_column($this->callTool($scoped, 'search_knowledge_base', ['query' => 'Hotline'])['articles'], 'title'));
        $this->assertSame([], $this->callTool($unscoped, 'search_knowledge_base', ['query' => 'Hotline'])['articles']);
    }

    public function test_rate_limit_is_shared_across_tools(): void
    {
        $key = $this->createKey();

        for ($i = 0; $i < 60; $i++) {
            $this->rpc($key, 'tools/list')->assertOk();
        }

        $this->callToolRaw($key, 'get_ticket_status', ['ticket_id' => 1])->assertStatus(429);
    }

    public function test_health_endpoint_reports_components(): void
    {
        $this->getJson('/mcp/health')->assertOk()->assertExactJson(['status' => 'ok', 'database' => 'ok', 'auth' => 'ok']);
    }

    private function createKey(bool $rest = false, bool $mcp = true, array $kbCategoryIds = []): string
    {
        return Livewire::actingAs($this->teamAdmin)
            ->test(ApiKeyManager::class, ['team' => $this->team])
            ->set('newClientName', 'telli '.uniqid())
            ->set('allowRest', $rest)
            ->set('allowMcp', $mcp)
            ->set('kbCategoryIds', $kbCategoryIds)
            ->call('createClient')
            ->get('plainTextToken');
    }

    private function rpc(?string $key, string $method, array $params = []): TestResponse
    {
        app('auth')->forgetGuards(); // every request must authenticate on its own bearer token

        return $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => uniqid(), 'method' => $method, 'params' => (object) $params], array_filter([
            'Authorization' => $key ? "Bearer {$key}" : null,
            'Accept' => 'application/json, text/event-stream',
        ]));
    }

    private function callToolRaw(string $key, string $tool, array $arguments): TestResponse
    {
        return $this->rpc($key, 'tools/call', ['name' => $tool, 'arguments' => $arguments]);
    }

    private function callTool(string $key, string $tool, array $arguments): array
    {
        $response = $this->callToolRaw($key, $tool, $arguments)->assertOk()->assertJsonPath('result.isError', false);

        return json_decode($response->json('result.content.0.text'), true);
    }

    private function article(KnowledgeBaseCategory $category, string $title, string $visibility): void
    {
        app(KnowledgeBaseService::class)->publish(null, ['category_id' => $category->id, 'title' => $title, 'body' => 'Inhalt', 'visibility' => $visibility], $this->teamAdmin);
    }
}

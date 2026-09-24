<?php

namespace Tests\Feature\Erp;

use App\Models\ErpConnection;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;

trait CreatesErpFixtures
{
    protected function makeTeam(string $slug = 'support'): Team
    {
        return Team::query()->create(['name' => ucfirst($slug), 'slug' => $slug]);
    }

    protected function makeConnection(Team $team, string $type = ErpConnection::TYPE_ODOO, array $overrides = []): ErpConnection
    {
        $credentials = $type === ErpConnection::TYPE_ODOO
            ? ['database' => 'prod', 'login' => 'integration', 'api_key' => 'odoo-secret-key']
            : ['client_id' => 'SWIA123', 'client_secret' => 'shopware-secret'];

        return ErpConnection::query()->create(array_merge([
            'team_id' => $team->id,
            'name' => ucfirst($type).' Test',
            'type' => $type,
            'base_url' => $type === ErpConnection::TYPE_ODOO ? 'https://odoo.example.com' : 'https://shop.example.com',
            'auth_payload' => $credentials,
            'field_mapping' => ['name' => 'Name', 'phone' => 'Telefon'],
            'is_active' => true,
        ], $overrides));
    }

    protected function makeTicket(Team $team, ?string $email = 'kunde@example.com'): Ticket
    {
        return Ticket::query()->create([
            'team_id' => $team->id,
            'type' => 'support_ticket',
            'source' => 'api',
            'subject' => 'ERP-Testticket',
            'requester_email' => $email,
            'requester_name' => 'Kunde',
        ]);
    }

    protected function makeMember(Team $team, string $role = 'member'): User
    {
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => $role]);

        return $user;
    }

    protected function fakeOdoo(array $records): void
    {
        Http::fake([
            'odoo.example.com/jsonrpc' => Http::sequence()
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => 7])
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => $records])
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => 7])
                ->push(['jsonrpc' => '2.0', 'id' => 1, 'result' => $records]),
        ]);
    }
}

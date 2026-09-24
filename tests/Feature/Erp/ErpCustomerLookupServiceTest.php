<?php

namespace Tests\Feature\Erp;

use App\Models\AuditLog;
use App\Models\ErpConnection;
use App\Services\Erp\ErpCustomerLookupService;
use App\Services\Erp\ErpLookupException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ErpCustomerLookupServiceTest extends TestCase
{
    use CreatesErpFixtures;
    use RefreshDatabase;

    private function service(): ErpCustomerLookupService
    {
        return app(ErpCustomerLookupService::class);
    }

    public function test_odoo_adapter_maps_released_fields_via_json_rpc(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);
        $this->fakeOdoo([['id' => 42, 'name' => 'Erika Muster', 'phone' => false, 'country_id' => [1, 'Deutschland']]]);

        $fields = $this->service()->lookup($connection, 'Kunde@Example.com', $this->makeMember($team));

        $this->assertSame(['Name' => 'Erika Muster', 'Telefon' => null], $fields);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://odoo.example.com/jsonrpc'
            && $request['params']['method'] === 'execute_kw'
            && $request['params']['args'][3] === 'res.partner'
            && $request['params']['args'][2] === 'odoo-secret-key'
            && $request['params']['args'][6]['fields'] === ['name', 'phone', 'id']);
    }

    public function test_shopware_adapter_fetches_token_and_searches_customer(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team, ErpConnection::TYPE_SHOPWARE, [
            'field_mapping' => ['firstName' => 'Vorname', 'customerNumber' => 'Kundennummer'],
        ]);
        Http::fake([
            'shop.example.com/api/oauth/token' => Http::response(['access_token' => 'tok-1', 'expires_in' => 600]),
            'shop.example.com/api/search/customer' => Http::response(['total' => 1, 'data' => [
                ['id' => 'abc', 'firstName' => 'Max', 'customerNumber' => '10001'],
            ]]),
        ]);

        $fields = $this->service()->lookup($connection, '10001', $this->makeMember($team));

        $this->assertSame(['Vorname' => 'Max', 'Kundennummer' => '10001'], $fields);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/api/oauth/token')
            && $request['grant_type'] === 'client_credentials'
            && $request['client_id'] === 'SWIA123');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/api/search/customer')
            && $request->hasHeader('Authorization', 'Bearer tok-1')
            && $request['includes']['customer'] === ['firstName', 'customerNumber', 'id']);
    }

    public function test_unknown_customer_returns_null(): void
    {
        $team = $this->makeTeam();
        $this->fakeOdoo([]);

        $this->assertNull($this->service()->lookup($this->makeConnection($team), 'niemand@example.com', $this->makeMember($team)));
    }

    public function test_second_lookup_within_ttl_is_served_from_cache(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);
        $user = $this->makeMember($team);
        $this->fakeOdoo([['id' => 1, 'name' => 'Erika']]);

        $this->service()->lookup($connection, 'kunde@example.com', $user);
        $this->service()->lookup($connection, 'kunde@example.com', $user);

        Http::assertSentCount(2);
    }

    public function test_lookup_after_ttl_hits_the_erp_again(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);
        $user = $this->makeMember($team);
        $this->fakeOdoo([['id' => 1, 'name' => 'Erika']]);

        $this->service()->lookup($connection, 'kunde@example.com', $user);
        $this->travel(ErpCustomerLookupService::CACHE_TTL_SECONDS + 1)->seconds();
        $this->service()->lookup($connection, 'kunde@example.com', $user);

        Http::assertSentCount(4);
    }

    public function test_every_lookup_is_audited_without_secrets_or_customer_data(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);
        $user = $this->makeMember($team);
        $this->fakeOdoo([['id' => 1, 'name' => 'Erika']]);

        $this->service()->lookup($connection, 'kunde@example.com', $user);
        $this->service()->lookup($connection, 'kunde@example.com', $user);

        $logs = AuditLog::query()->where('action', 'erp.customer.lookup')->get();
        $this->assertCount(2, $logs);
        $this->assertSame($team->id, $logs[0]->team_id);
        $this->assertSame($user->id, $logs[0]->user_id);
        $this->assertSame($connection->id, $logs[0]->auditable_id);
        $this->assertSame('kunde@example.com', $logs[0]->meta['customer_reference']);
        $this->assertSame(['live', 'cache'], $logs->pluck('meta.source')->all());
        $this->assertStringNotContainsString('odoo-secret-key', $logs->toJson());
        $this->assertStringNotContainsString('Erika', $logs->toJson());
    }

    public function test_erp_error_raises_lookup_exception(): void
    {
        $team = $this->makeTeam();
        Http::fake(['odoo.example.com/*' => Http::response(['jsonrpc' => '2.0', 'error' => ['message' => 'Access Denied']])]);

        $this->expectException(ErpLookupException::class);

        $this->service()->lookup($this->makeConnection($team), 'kunde@example.com', $this->makeMember($team));
    }

    public function test_circuit_breaker_opens_after_repeated_failures(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);
        $user = $this->makeMember($team);
        Http::fake(['odoo.example.com/*' => Http::failedConnection()]);

        for ($i = 0; $i < ErpCustomerLookupService::FAILURE_THRESHOLD + 2; $i++) {
            try {
                $this->service()->lookup($connection, 'kunde@example.com', $user);
            } catch (ErpLookupException) {
            }
        }

        $this->assertTrue($this->service()->isDown($connection));
        Http::assertSentCount(ErpCustomerLookupService::FAILURE_THRESHOLD);

        $this->travel(ErpCustomerLookupService::DOWN_SECONDS + 1)->seconds();
        $this->assertFalse($this->service()->isDown($connection));
    }

    public function test_auth_payload_is_encrypted_and_hidden(): void
    {
        $connection = $this->makeConnection($this->makeTeam());

        $raw = (string) DB::table('erp_connections')->where('id', $connection->id)->value('auth_payload');

        $this->assertStringNotContainsString('odoo-secret-key', $raw);
        $this->assertArrayNotHasKey('auth_payload', $connection->toArray());
    }
}

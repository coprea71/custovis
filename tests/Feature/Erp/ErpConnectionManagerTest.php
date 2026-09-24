<?php

namespace Tests\Feature\Erp;

use App\Livewire\Agent\Team\ErpConnectionManager;
use App\Models\AuditLog;
use App\Models\ErpConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ErpConnectionManagerTest extends TestCase
{
    use CreatesErpFixtures;
    use RefreshDatabase;

    public function test_team_admin_creates_odoo_connection_with_encrypted_credentials(): void
    {
        $team = $this->makeTeam();
        $admin = $this->makeMember($team, 'team_admin');

        Livewire::actingAs($admin)
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->set('name', 'Odoo Produktion')
            ->set('type', 'odoo')
            ->set('baseUrl', 'https://erp.example.com')
            ->set('database', 'prod')
            ->set('login', 'integration@example.com')
            ->set('secret', 'top-secret-key')
            ->set('fieldMappingText', "name=Name\nphone=Telefon")
            ->call('save')
            ->assertHasNoErrors();

        $connection = ErpConnection::query()->sole();
        $this->assertSame('top-secret-key', $connection->credential('api_key'));
        $this->assertSame(['name' => 'Name', 'phone' => 'Telefon'], $connection->field_mapping);
        $this->assertStringNotContainsString('top-secret-key', AuditLog::query()->where('action', 'erp_connection.created')->sole()->toJson());
    }

    public function test_invalid_input_is_rejected(): void
    {
        $team = $this->makeTeam();
        $admin = $this->makeMember($team, 'team_admin');

        Livewire::actingAs($admin)
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->set('name', 'X')
            ->set('type', 'sap')
            ->set('baseUrl', 'https://user:pass@erp.example.com')
            ->set('secret', 'k')
            ->call('save')
            ->assertHasErrors(['type', 'baseUrl']);

        Livewire::actingAs($admin)
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->set('name', 'X')
            ->set('type', 'shopware')
            ->set('baseUrl', 'https://shop.example.com')
            ->set('clientId', 'id')
            ->set('secret', 'k')
            ->set('fieldMappingText', 'name; DROP TABLE=Name')
            ->call('save')
            ->assertHasErrors(['fieldMappingText']);

        $this->assertSame(0, ErpConnection::query()->count());
    }

    public function test_http_url_is_rejected_outside_local_and_testing(): void
    {
        $team = $this->makeTeam();
        $admin = $this->makeMember($team, 'team_admin');
        $this->app['env'] = 'production';

        Livewire::actingAs($admin)
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->set('baseUrl', 'http://erp.example.com')
            ->call('save')
            ->assertHasErrors(['baseUrl']);
    }

    public function test_admin_rotates_credentials_without_recreating_connection(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);
        $admin = $this->makeMember($team, 'team_admin');

        Livewire::actingAs($admin)
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->call('edit', $connection->id)
            ->set('database', 'prod')
            ->set('login', 'integration')
            ->set('secret', 'rotated-key')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('rotated-key', $connection->fresh()->credential('api_key'));
        $this->assertSame(1, ErpConnection::query()->count());
        $this->assertTrue(AuditLog::query()->where('action', 'erp_connection.updated')->sole()->meta['credentials_rotated']);
    }

    public function test_changing_base_url_requires_fresh_credentials(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);
        $admin = $this->makeMember($team, 'team_admin');

        Livewire::actingAs($admin)
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->call('edit', $connection->id)
            ->set('baseUrl', 'https://evil.example.net')
            ->call('save')
            ->assertHasErrors(['secret']);

        $this->assertSame('https://odoo.example.com', $connection->fresh()->base_url);
    }

    public function test_admin_can_deactivate_connection(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);

        Livewire::actingAs($this->makeMember($team, 'team_admin'))
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->call('toggleActive', $connection->id);

        $this->assertFalse($connection->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'erp_connection.deactivated', 'auditable_id' => $connection->id]);
    }

    public function test_connection_of_other_team_cannot_be_touched(): void
    {
        $team = $this->makeTeam();
        $foreign = $this->makeConnection($this->makeTeam('vertrieb'));

        Livewire::actingAs($this->makeMember($team, 'team_admin'))
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->call('toggleActive', $foreign->id)
            ->assertNotFound();

        $this->assertTrue($foreign->fresh()->is_active);
    }

    public function test_plain_member_is_forbidden_and_system_admin_is_read_only(): void
    {
        $team = $this->makeTeam();
        $connection = $this->makeConnection($team);

        Livewire::actingAs($this->makeMember($team))
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->assertForbidden();

        $auditor = $this->makeMember($this->makeTeam('it'));
        $auditor->givePermissionTo(Permission::findOrCreate('team.erp.manage', 'web'));

        Livewire::actingAs($auditor)
            ->test(ErpConnectionManager::class, ['team' => $team])
            ->assertSee($connection->name)
            ->assertDontSee('odoo-secret-key')
            ->call('toggleActive', $connection->id)
            ->assertForbidden();
    }
}

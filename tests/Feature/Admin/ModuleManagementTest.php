<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ModuleManager;
use App\Models\Customer;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system_admin');
        $this->agent = User::factory()->create();
        $this->agent->assignRole('agent');
    }

    public function test_existing_modules_are_enabled_by_the_update_migration(): void
    {
        $this->assertSame(0, Module::query()->where('enabled', false)->count());
        $this->assertTrue(Module::query()->where('slug', 'whatsapp')->exists());
        $this->actingAs($this->agent)->get('/agent/kb')->assertOk();
    }

    public function test_disabled_module_disappears_from_navigation_and_returns_404(): void
    {
        $kb = Module::query()->where('slug', 'knowledge-base')->firstOrFail();

        Livewire::actingAs($this->admin)->test(ModuleManager::class)->call('toggle', $kb->id);

        $this->assertFalse($kb->fresh()->enabled);
        $this->actingAs($this->agent)->get('/agent')->assertOk()->assertDontSee(route('agent.kb.index'));
        $this->actingAs($this->agent)->get('/agent/kb')->assertNotFound();
        $this->actingAs(Customer::factory()->create(), 'customer')->get('/portal/kb')->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['action' => 'module.disabled', 'user_id' => $this->admin->id]);
    }

    public function test_module_restricted_to_role_is_only_available_to_that_role(): void
    {
        $chat = Module::query()->where('slug', 'team-chat')->firstOrFail();
        $pilot = Role::create(['name' => 'chat_pilot', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)->test(ModuleManager::class)
            ->call('edit', $chat->id)->set('roleIds', [$pilot->id])->call('saveAssignments');

        $this->actingAs($this->agent)->get('/agent/chat')->assertNotFound();
        $this->agent->assignRole($pilot);
        $this->actingAs($this->agent->fresh())->get('/agent/chat')->assertOk();
    }

    public function test_module_management_requires_permission(): void
    {
        Livewire::actingAs($this->agent)->test(ModuleManager::class)->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/modules')->assertOk()->assertSee('Wissensdatenbank für Agenten');
    }
}

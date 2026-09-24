<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\RoleManager;
use App\Livewire\Admin\TeamManager;
use App\Livewire\Admin\UserManager;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system_admin');
    }

    public function test_admin_creates_agent_who_joins_team_and_sees_its_tickets(): void
    {
        Notification::fake();
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        Ticket::query()->create(['team_id' => $team->id, 'source' => 'api', 'subject' => 'Team-Ticket']);

        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->set('form', ['name' => 'Neu Agent', 'email' => 'neu@example.com', 'roles' => ['agent']])
            ->call('save')->assertHasNoErrors();
        $agent = User::query()->where('email', 'neu@example.com')->firstOrFail();
        Notification::assertSentTo($agent, ResetPassword::class);

        Livewire::actingAs($this->admin)->test(TeamManager::class)
            ->call('select', $team->id)
            ->set('newMemberId', $agent->id)->set('newMemberRole', 'member')
            ->call('addMember');

        $this->assertTrue($agent->hasRole('agent'));
        $this->actingAs($agent)->get('/agent')->assertOk()->assertSee('Team-Ticket');
    }

    public function test_deactivated_user_cannot_log_in_and_running_session_ends(): void
    {
        $agent = User::factory()->create(['email' => 'agent@example.com', 'password' => 'richtiges-passwort']);

        Livewire::actingAs($this->admin)->test(UserManager::class)->call('toggleActive', $agent->id)->assertHasNoErrors();
        $this->assertFalse($agent->fresh()->active);

        $this->actingAs($agent->fresh())->get('/agent')->assertRedirect('/login');
        $this->assertGuest('web');
        $this->post('/login', ['email' => 'agent@example.com', 'password' => 'richtiges-passwort']);
        $this->assertGuest('web');
    }

    public function test_last_active_system_admin_cannot_be_locked_out(): void
    {
        Livewire::actingAs($this->admin)->test(UserManager::class)
            ->call('toggleActive', $this->admin->id)->assertHasErrors('user')
            ->call('edit', $this->admin->id)->set('form.roles', ['agent'])->call('save')->assertHasErrors('user');

        $this->assertTrue($this->admin->fresh()->active);
        $this->assertTrue($this->admin->fresh()->hasRole('system_admin'));
    }

    public function test_role_matrix_toggles_permissions_but_protects_system_admin(): void
    {
        $agentRole = Role::findByName('agent', 'web');
        $systemAdmin = Role::findByName('system_admin', 'web');

        Livewire::actingAs($this->admin)->test(RoleManager::class)
            ->call('toggle', $agentRole->id, 'dispatch.manage')
            ->call('toggle', $systemAdmin->id, 'dispatch.manage')->assertForbidden();

        $this->assertTrue($agentRole->fresh()->hasPermissionTo('dispatch.manage'));
        $this->assertTrue($systemAdmin->fresh()->hasPermissionTo('dispatch.manage'));
    }

    public function test_admin_pages_require_their_permissions(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        Livewire::actingAs($agent)->test(UserManager::class)->assertForbidden();
        Livewire::actingAs($agent)->test(TeamManager::class)->assertForbidden();
        Livewire::actingAs($agent)->test(RoleManager::class)->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/users')->assertOk()->assertSee('Rollen');
    }
}

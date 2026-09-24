<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_home_page_is_indexable_and_links_all_areas(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee(route('portal.login'))
            ->assertSee(route('login'));
    }

    public function test_signed_in_users_are_sent_to_their_area(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/agent');
        auth('web')->logout();
        $this->actingAs(Customer::factory()->create(), 'customer')->get('/')->assertRedirect(route('portal.tickets.index'));
    }

    public function test_agent_can_log_out_and_only_admins_see_the_admin_link(): void
    {
        $agent = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');

        $this->actingAs($agent)->get('/agent')->assertOk()->assertSee('Abmelden')->assertDontSee('>Administration<', false);
        $this->actingAs($admin)->get('/agent')->assertSee('>Administration<', false);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Agenten-Bereich')->assertSee('Mailboxen');

        $this->actingAs($agent)->post('/logout')->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_admin_menu_only_lists_permitted_pages(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.management.view');

        $this->actingAs($user)->get('/admin/dashboard')->assertOk()->assertDontSee('Mailboxen')->assertDontSee('Datenschutz');
    }

    public function test_team_settings_hub_is_limited_to_team_admins(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $teamAdmin = User::factory()->create();
        $member = User::factory()->create();
        $team->users()->attach($teamAdmin, ['role_in_team' => 'team_admin']);
        $team->users()->attach($member, ['role_in_team' => 'member']);

        $this->actingAs($teamAdmin)->get("/agent/team/{$team->id}/settings")->assertOk()->assertSee('API-Keys')->assertSee('WhatsApp');
        $this->actingAs($teamAdmin)->get('/agent')->assertSee('Einstellungen');
        $this->actingAs($member)->get("/agent/team/{$team->id}/settings")->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\HelpCenter;
use App\Models\Module;
use App\Models\Team;
use App\Models\User;
use App\Support\HelpTopics;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HelpCenterTest extends TestCase
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

    public function test_every_topic_has_a_step_by_step_guide(): void
    {
        foreach (HelpTopics::slugs() as $slug) {
            $this->assertFileExists(resource_path("help/{$slug}.md"));
            $this->assertStringContainsString('<ol>', HelpTopics::html($slug), "Topic [{$slug}] has no numbered steps.");
        }
    }

    public function test_admin_sees_and_opens_every_topic(): void
    {
        $this->actingAs($this->admin)->get(route('agent.help.index'))->assertOk()->assertSee('Mailboxen');

        foreach (HelpTopics::slugs() as $slug) {
            $this->actingAs($this->admin)->get(route('agent.help.show', $slug))->assertOk();
        }
    }

    public function test_agent_only_sees_topics_for_areas_they_can_use(): void
    {
        $this->actingAs($this->agent)->get(route('agent.help.index'))
            ->assertOk()
            ->assertSee('Tickets bearbeiten')
            ->assertSee('Team-Chat')
            ->assertDontSee('Mailboxen')
            ->assertDontSee('Rollen &amp; Berechtigungen', false);

        $this->actingAs($this->agent)->get(route('agent.help.show', 'mailboxen'))->assertNotFound();
        $this->actingAs($this->agent)->get(route('agent.help.show', 'gibt-es-nicht'))->assertNotFound();
    }

    public function test_team_admin_sees_team_setting_topics(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $team->users()->attach($this->agent, ['role_in_team' => 'team_admin']);

        $this->actingAs($this->agent)->get(route('agent.help.show', 'textbausteine'))->assertOk()->assertSee('Textbaustein anlegen');
    }

    public function test_disabled_module_hides_its_topic(): void
    {
        Module::query()->where('slug', 'team-chat')->update(['enabled' => false]);

        $this->actingAs($this->agent)->get(route('agent.help.index'))->assertDontSee(route('agent.help.show', 'team-chat'));
        $this->actingAs($this->agent)->get(route('agent.help.show', 'team-chat'))->assertNotFound();
    }

    public function test_search_filters_topics(): void
    {
        Livewire::actingAs($this->agent)->test(HelpCenter::class)
            ->set('search', 'chat')
            ->assertSee('Team-Chat')
            ->assertDontSee('Tickets bearbeiten')
            ->set('search', 'xyz-nichts')
            ->assertSee('Kein passendes Hilfethema gefunden.');
    }

    public function test_account_menu_links_to_help(): void
    {
        $this->actingAs($this->agent)->get('/agent')->assertSee(route('agent.help.index'));
    }
}

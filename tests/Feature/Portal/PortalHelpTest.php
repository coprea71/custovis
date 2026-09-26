<?php

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Module;
use App\Support\HelpTopics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_every_portal_topic_has_a_step_by_step_guide(): void
    {
        foreach (HelpTopics::portalSlugs() as $slug) {
            $this->assertFileExists(resource_path("help/portal/{$slug}.md"));
            $this->assertStringContainsString('<ol>', HelpTopics::html("portal/{$slug}"), "Portal topic [{$slug}] has no numbered steps.");
        }
    }

    public function test_customer_opens_every_topic_from_the_navigation(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')->get(route('portal.tickets.index'))->assertSee(route('portal.help.index'));
        $this->actingAs($customer, 'customer')->get(route('portal.help.index'))->assertOk()->assertSee('Meine Anfragen verfolgen');

        foreach (HelpTopics::portalSlugs() as $slug) {
            $this->actingAs($customer, 'customer')->get(route('portal.help.show', $slug))->assertOk();
        }
    }

    public function test_guests_can_read_the_login_help_without_customer_navigation(): void
    {
        $this->get(route('portal.login'))->assertSee(route('portal.help.show', 'anmelden'));

        $this->get(route('portal.help.show', 'anmelden'))
            ->assertOk()
            ->assertSee('Passwort vergessen')
            ->assertSee(route('portal.login'))
            ->assertDontSee(route('portal.logout'))
            ->assertDontSee(route('portal.kb.index'));
    }

    public function test_topic_of_disabled_module_is_hidden(): void
    {
        Module::query()->where('slug', 'service-catalog')->update(['enabled' => false]);

        $this->get(route('portal.help.index'))->assertDontSee(route('portal.help.show', 'neue-anfrage'));
        $this->get(route('portal.help.show', 'neue-anfrage'))->assertNotFound();
        $this->get(route('portal.help.show', 'gibt-es-nicht'))->assertNotFound();
    }
}

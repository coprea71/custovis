<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        config([
            'app.url' => 'https://support.fahrklar.net',
            'app.hosts' => ['support.eichner-net.de'],
        ]);
    }

    public function test_links_and_passkeys_follow_the_additional_domain(): void
    {
        $this->get('https://support.eichner-net.de/')->assertOk()
            ->assertSee('https://support.eichner-net.de/login', false)
            ->assertSee('<link rel="canonical" href="https://support.fahrklar.net/">', false);

        $this->assertSame('support.eichner-net.de', config('passkeys.relying_party_id'));
        $this->assertSame(['https://support.eichner-net.de'], config('passkeys.allowed_origins'));
    }

    public function test_passkeys_are_not_rebound_to_unknown_hosts(): void
    {
        $this->get('https://evil.example/');

        $this->assertNotSame('evil.example', config('passkeys.relying_party_id'));
    }

    public function test_only_configured_hosts_are_accepted_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://support.fahrklar.net/')->assertOk();
        $this->get('https://support.eichner-net.de/')->assertOk();
        $this->get('https://evil.example/')->assertStatus(400);
    }
}

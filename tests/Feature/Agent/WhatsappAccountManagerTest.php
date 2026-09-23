<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Team\WhatsappAccountManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsappAccountManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_admin_can_create_a_whatsapp_account(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'team_admin']);

        Livewire::actingAs($user)
            ->test(WhatsappAccountManager::class, ['team' => $team])
            ->set('display_name', 'Support WA')
            ->set('phone_number_id', '1234567890')
            ->set('business_account_id', 'ba-1')
            ->set('access_token', 'token')
            ->set('webhook_verify_token', 'verify-me')
            ->set('app_secret', 'secret')
            ->call('createAccount');

        $this->assertDatabaseHas('whatsapp_accounts', [
            'team_id' => $team->id,
            'phone_number_id' => '1234567890',
        ]);
    }

    public function test_plain_member_cannot_manage_whatsapp_accounts(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'member']);

        Livewire::actingAs($user)
            ->test(WhatsappAccountManager::class, ['team' => $team])
            ->assertForbidden();
    }
}

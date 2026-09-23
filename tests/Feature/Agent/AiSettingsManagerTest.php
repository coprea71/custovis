<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Team\AiSettingsManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiSettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_admin_can_configure_a_use_case_and_budget(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'team_admin']);

        Livewire::actingAs($user)
            ->test(AiSettingsManager::class, ['team' => $team])
            ->set('use_case', 'summarize')
            ->set('provider', 'ollama')
            ->set('endpoint_url', 'http://localhost:11434')
            ->set('redact_pii', true)
            ->call('saveSetting')
            ->set('monthly_limit_euros', '25')
            ->call('saveBudget');

        $this->assertDatabaseHas('ai_settings', [
            'team_id' => $team->id,
            'use_case' => 'summarize',
            'provider' => 'ollama',
            'redact_pii' => true,
        ]);
        $this->assertDatabaseHas('ai_budgets', [
            'team_id' => $team->id,
            'monthly_limit_cents' => 2500,
        ]);
    }

    public function test_plain_member_cannot_manage_ai_settings(): void
    {
        $team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $user = User::factory()->create();
        $team->users()->attach($user, ['role_in_team' => 'member']);

        Livewire::actingAs($user)
            ->test(AiSettingsManager::class, ['team' => $team])
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\ApiClient;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(Team $team): array
    {
        $creator = User::factory()->create();
        $client = ApiClient::query()->create([
            'team_id' => $team->id,
            'name' => 'Test Client',
            'created_by' => $creator->id,
        ]);

        $token = $client->createToken('test', ['tickets.create'])->plainTextToken;

        return [$client, $token];
    }

    public function test_valid_token_creates_ticket_in_correct_team(): void
    {
        $teamA = Team::query()->create(['name' => 'A', 'slug' => 'a']);
        $teamB = Team::query()->create(['name' => 'B', 'slug' => 'b']);
        [, $token] = $this->makeClient($teamA);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tickets', [
                'subject' => 'Neues Ticket',
                'body' => 'Bitte um Hilfe.',
                'requester_email' => 'kunde@example.com',
                'team_id' => $teamB->id, // must be ignored
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Neues Ticket',
            'team_id' => $teamA->id,
            'source' => 'api',
        ]);
        $this->assertDatabaseMissing('tickets', ['team_id' => $teamB->id]);
    }

    public function test_revoked_token_is_rejected(): void
    {
        $team = Team::query()->create(['name' => 'A', 'slug' => 'a']);
        [$client, $token] = $this->makeClient($team);
        $client->revoke();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tickets', [
                'subject' => 'x', 'body' => 'y', 'requester_email' => 'a@example.com',
            ])
            ->assertUnauthorized();
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->postJson('/api/v1/tickets', [
            'subject' => 'x', 'body' => 'y', 'requester_email' => 'a@example.com',
        ])->assertUnauthorized();
    }

    public function test_rate_limit_returns_429(): void
    {
        RateLimiter::clear('api-tickets');

        $team = Team::query()->create(['name' => 'A', 'slug' => 'a']);
        [, $token] = $this->makeClient($team);

        for ($i = 0; $i < 60; $i++) {
            $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/v1/tickets', [
                    'subject' => "Ticket {$i}", 'body' => 'y', 'requester_email' => 'a@example.com',
                ])->assertCreated();
        }

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/tickets', [
                'subject' => 'over limit', 'body' => 'y', 'requester_email' => 'a@example.com',
            ])->assertStatus(429);
    }
}

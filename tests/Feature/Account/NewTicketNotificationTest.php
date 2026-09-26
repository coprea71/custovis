<?php

namespace Tests\Feature\Account;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewTicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
    }

    public function test_only_active_opted_in_team_members_are_notified(): void
    {
        Notification::fake();
        $optedIn = $this->member(['notify_new_tickets' => true]);
        $optedOut = $this->member(['notify_new_tickets' => false]);
        $inactive = $this->member(['notify_new_tickets' => true, 'active' => false]);
        $otherTeam = User::factory()->create(['notify_new_tickets' => true]);

        $ticket = $this->createTicket();

        Notification::assertSentTo($optedIn, NewTicketNotification::class, fn ($n) => $n->ticket->is($ticket));
        Notification::assertNotSentTo([$optedOut, $inactive, $otherTeam], NewTicketNotification::class);
    }

    public function test_agent_creating_the_ticket_is_not_notified(): void
    {
        Notification::fake();
        $creator = $this->member(['notify_new_tickets' => true]);

        $this->actingAs($creator);
        $this->createTicket();

        Notification::assertNotSentTo($creator, NewTicketNotification::class);
    }

    public function test_mail_links_to_ticket_without_requester_data(): void
    {
        $user = $this->member(['notify_new_tickets' => true]);
        $mail = (new NewTicketNotification($this->createTicket()))->toMail($user);

        $this->assertStringContainsString('Druckerproblem', $mail->subject);
        $this->assertStringNotContainsString('kunde@example.com', implode(' ', $mail->introLines));
        $this->assertStringContainsString('/agent/tickets/', $mail->actionUrl);
    }

    public function test_user_can_toggle_own_setting(): void
    {
        $user = $this->member();

        $this->actingAs($user)->put(route('account.notifications'), ['notify_new_tickets' => '1'])
            ->assertRedirect(route('account.security'));
        $this->assertTrue($user->fresh()->notify_new_tickets);

        $this->actingAs($user)->put(route('account.notifications'), ['notify_new_tickets' => '0']);
        $this->assertFalse($user->fresh()->notify_new_tickets);
    }

    public function test_guest_cannot_change_settings(): void
    {
        $this->put(route('account.notifications'), ['notify_new_tickets' => '1'])
            ->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('agent');
        $this->team->users()->attach($user, ['role_in_team' => 'member']);

        return $user;
    }

    private function createTicket(): Ticket
    {
        return Ticket::query()->create([
            'team_id' => $this->team->id,
            'subject' => 'Druckerproblem',
            'requester_email' => 'kunde@example.com',
            'source' => 'portal',
        ]);
    }
}

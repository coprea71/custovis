<?php

namespace Tests\Feature\Mail;

use App\Jobs\FetchMailboxJob;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class StaleMailboxFetchTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_ticket_list_fetches_stale_mailbox_only_once_per_interval(): void
    {
        Bus::fake();
        $mailbox = Mailbox::factory()->create(['last_fetched_at' => now()->subMinutes(6)]);
        $user = $this->memberOf($mailbox);

        Livewire::actingAs($user)->test(TicketWorkspace::class);
        Livewire::actingAs($user)->test(TicketWorkspace::class);

        Bus::assertDispatchedAfterResponseTimes(FetchMailboxJob::class, 1);
    }

    public function test_recently_fetched_or_foreign_mailboxes_are_not_fetched(): void
    {
        Bus::fake();
        $recent = Mailbox::factory()->create(['last_fetched_at' => now()->subMinutes(4)]);
        Mailbox::factory()->create(['last_fetched_at' => null]);

        Livewire::actingAs($this->memberOf($recent))->test(TicketWorkspace::class);

        Bus::assertNotDispatchedAfterResponse(FetchMailboxJob::class);
    }

    private function memberOf(Mailbox $mailbox): User
    {
        $user = User::factory()->create();
        $mailbox->team->users()->attach($user);

        return $user;
    }
}

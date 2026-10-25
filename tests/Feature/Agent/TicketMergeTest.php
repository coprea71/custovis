<?php

namespace Tests\Feature\Agent;

use App\DataTransferObjects\IncomingMailAttachmentData;
use App\DataTransferObjects\IncomingMailMessageData;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\MailToTicketService;
use App\Services\TicketMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TicketMergeTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private Mailbox $mailbox;

    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Queue::fake();
        Storage::fake(AttachmentService::DISK);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->mailbox = Mailbox::factory()->create(['team_id' => $this->team->id, 'email_address' => 'support@team.example']);
        $this->agent = User::factory()->create(['name' => 'Agent Anna']);
        $this->team->users()->attach($this->agent);
    }

    public function test_merge_moves_content_and_closes_source_without_customer_mail(): void
    {
        $target = $this->mailTicket('Drucker defekt', 'm1@x');
        $source = $this->mailTicket('Drucker geht immer noch nicht', 'm2@x', withAttachment: true);
        $source->update(['tags' => ['drucker'], 'priority' => 'high']);
        $source->timeEntries()->create(['user_id' => $this->agent->id, 'work_date' => now(), 'minutes' => 15]);
        $attachment = $source->messages()->where('message_id', 'm2@x')->sole()->attachments()->sole();
        $automaticBefore = TicketMessage::query()->where('automatic', true)->count();

        app(TicketMergeService::class)->merge($target, collect([$source]), $this->agent);

        $source->refresh();
        $target->refresh();
        $this->assertSame('closed', $source->status);
        $this->assertSame($target->id, $source->merged_into_id);
        $this->assertTrue($source->auto_mails_muted);
        $this->assertTrue($target->messages()->where('message_id', 'm2@x')->exists());
        $this->assertSame(1, $target->timeEntries()->count());
        $this->assertSame(['drucker'], $target->tags);
        $this->assertSame('high', $target->priority);
        $this->assertStringStartsWith("ticket-attachments/{$target->id}/", $attachment->fresh()->path);
        Storage::disk(AttachmentService::DISK)->assertExists($attachment->fresh()->path);
        Storage::disk(AttachmentService::DISK)->assertMissing($attachment->path);
        $this->assertSame($automaticBefore, TicketMessage::query()->where('automatic', true)->count());
        $this->assertStringContainsString("in Ticket #{$target->id} zusammengeführt", $source->messages()->sole()->body_text);
        $this->assertTrue($target->messages()->where('body_text', 'like', "%#{$source->id}%Agent Anna hierher zusammengeführt%")->exists());
    }

    public function test_reply_quoting_the_old_ticket_number_lands_in_the_target(): void
    {
        $target = $this->mailTicket('Drucker defekt', 'm1@x');
        $source = $this->mailTicket('Drucker kaputt', 'm2@x');
        app(TicketMergeService::class)->merge($target, collect([$source]), $this->agent);

        $reply = $this->import("Re: [Ticket #{$source->id}] Drucker kaputt", 'm3@x');

        $this->assertSame($target->id, $reply->ticket_id);
    }

    public function test_tickets_of_different_teams_cannot_be_merged(): void
    {
        $other = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);
        $target = $this->mailTicket('Drucker defekt', 'm1@x');
        $foreign = Ticket::query()->create(['team_id' => $other->id, 'source' => 'api', 'subject' => 'Fremd']);

        $this->expectException(ValidationException::class);
        app(TicketMergeService::class)->merge($target, collect([$foreign]), $this->agent);
    }

    public function test_agent_merges_selected_tickets_from_the_list_into_the_oldest(): void
    {
        $oldest = $this->mailTicket('Drucker defekt', 'm1@x');
        $newer = $this->mailTicket('Drucker kaputt', 'm2@x');

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('toggleMergeMode')
            ->set('mergeSelection', [(string) $newer->id, (string) $oldest->id])
            ->assertSet('mergeTargetId', $oldest->id)
            ->call('mergeTickets')
            ->assertHasNoErrors()
            ->assertSet('ticketId', $oldest->id)
            ->assertSet('mergeMode', false)
            ->assertSee("1 Ticket(s) in #{$oldest->id} zusammengeführt.");

        $this->assertSame($oldest->id, $newer->fresh()->merged_into_id);
    }

    public function test_agent_cannot_merge_a_ticket_of_a_foreign_team(): void
    {
        $own = $this->mailTicket('Drucker defekt', 'm1@x');
        $other = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);
        $foreign = Ticket::query()->create(['team_id' => $other->id, 'source' => 'api', 'subject' => 'Fremd']);

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('toggleMergeMode')
            ->set('mergeSelection', [$own->id, $foreign->id])
            ->call('mergeTickets')
            ->assertNotFound();

        $this->assertNull($foreign->fresh()->merged_into_id);
    }

    public function test_merge_needs_at_least_two_tickets(): void
    {
        $ticket = $this->mailTicket('Drucker defekt', 'm1@x');

        Livewire::actingAs($this->agent)->test(TicketWorkspace::class)
            ->call('toggleMergeMode')
            ->set('mergeSelection', [$ticket->id])
            ->call('mergeTickets')
            ->assertHasErrors('merge');
    }

    private function mailTicket(string $subject, string $messageId, bool $withAttachment = false): Ticket
    {
        return $this->import($subject, $messageId, $withAttachment)->ticket;
    }

    private function import(string $subject, string $messageId, bool $withAttachment = false): TicketMessage
    {
        return app(MailToTicketService::class)->import($this->mailbox, new IncomingMailMessageData(
            messageId: $messageId,
            inReplyTo: null,
            references: [],
            subject: $subject,
            fromEmail: 'kunde@example.com',
            fromName: 'Max Kunde',
            bodyHtml: null,
            bodyText: 'Hilfe!',
            attachments: $withAttachment
                ? [new IncomingMailAttachmentData('foto.jpg', 'image/jpeg', 'jpeg-bytes')]
                : [],
        ));
    }
}

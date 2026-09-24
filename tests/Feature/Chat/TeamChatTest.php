<?php

namespace Tests\Feature\Chat;

use App\Events\ChatMessageSent;
use App\Livewire\Agent\Chat\ChatWorkspace;
use App\Models\ChatMessage;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TeamChatTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $anna;

    private User $ben;

    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->anna = $this->agent('Anna', $this->team);
        $this->ben = $this->agent('Ben', $this->team);
        $this->outsider = $this->agent('Olaf');
    }

    public function test_team_channel_message_is_broadcast_and_counted_as_unread_for_teammate(): void
    {
        Event::fake([ChatMessageSent::class]);
        $channel = app(ChatAccess::class)->teamChannel($this->team);

        Livewire::actingAs($this->anna)->test(ChatWorkspace::class)
            ->call('openChannel', $channel->id)
            ->set('body', 'Hallo Team')
            ->call('send');

        Event::assertDispatched(ChatMessageSent::class, fn ($event) => $event->broadcastOn()->name === "private-chat.channel.{$channel->id}");
        $this->assertSame(1, app(ChatService::class)->unreadCount($this->ben, $channel));
        $this->assertSame(0, app(ChatService::class)->unreadCount($this->anna, $channel));

        Livewire::actingAs($this->ben)->test(ChatWorkspace::class)->call('openChannel', $channel->id)->assertSee('Hallo Team');
        $this->assertSame(0, app(ChatService::class)->unreadCount($this->ben, $channel));
    }

    public function test_outsider_cannot_read_foreign_team_channel(): void
    {
        $channel = app(ChatAccess::class)->teamChannel($this->team);

        Livewire::actingAs($this->outsider)->test(ChatWorkspace::class)
            ->call('openChannel', $channel->id)
            ->assertForbidden();
    }

    public function test_direct_message_is_only_visible_to_its_two_participants(): void
    {
        Livewire::actingAs($this->anna)->test(ChatWorkspace::class)
            ->set('newDirectUserId', $this->outsider->id)
            ->call('startDirect')
            ->set('body', 'Nur für dich')
            ->call('send');

        $thread = ChatMessage::query()->firstOrFail()->directThread;
        $access = app(ChatAccess::class);

        $this->assertTrue($access->canView($this->outsider, $thread));
        $this->assertFalse($access->canView($this->ben, $thread));
        $this->assertSame(1, app(ChatService::class)->totalUnread($this->outsider));
    }

    public function test_ticket_chat_is_limited_to_agents_of_the_ticket_team(): void
    {
        $ticket = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Server down']);

        $this->withoutVite()->actingAs($this->anna)->get(route('agent.chat.ticket', $ticket))->assertOk()->assertSee('Ticket #'.$ticket->id);
        $this->actingAs($this->outsider)->get(route('agent.chat.ticket', $ticket))->assertForbidden();
        Livewire::actingAs($this->anna)->test(ChatWorkspace::class, ['ticket' => $ticket])
            ->set('body', 'Kannst du übernehmen?')
            ->call('send');

        $channel = app(ChatAccess::class)->ticketChannel($ticket);
        $this->assertTrue(app(ChatAccess::class)->canView($this->ben, $channel));
        $this->assertFalse(app(ChatAccess::class)->canView($this->outsider, $channel));
        $this->assertSame(0, $ticket->messages()->count(), 'ticket chat must not end up in the ticket history');
    }

    public function test_global_channel_respects_post_permission(): void
    {
        Role::findByName('agent', 'web')->revokePermissionTo('chat.global.post');
        $global = app(ChatAccess::class)->globalChannel();

        Livewire::actingAs($this->anna)->test(ChatWorkspace::class)
            ->call('openChannel', $global->id)
            ->assertSee('nur Leserechte')
            ->set('body', 'Hallo alle')
            ->call('send')
            ->assertForbidden();
    }

    public function test_attachment_download_is_access_checked_and_prune_respects_retention(): void
    {
        Storage::fake('local');
        $channel = app(ChatAccess::class)->teamChannel($this->team);
        $message = app(ChatService::class)->send($this->anna, $channel, null, UploadedFile::fake()->create('log.txt', 2));

        $this->actingAs($this->ben)->get(route('agent.chat.attachment', $message))->assertOk();
        $this->actingAs($this->outsider)->get(route('agent.chat.attachment', $message))->assertNotFound();

        $message->forceFill(['created_at' => now()->subDays(40)])->save();
        $this->artisan('chat:prune')->assertSuccessful();
        $this->assertModelExists($message);

        Setting::write('chat.retention_days', '30');
        $this->artisan('chat:prune')->assertSuccessful();
        $this->assertModelMissing($message);
        Storage::disk('local')->assertMissing($message->attachment_path);
    }

    private function agent(string $name, ?Team $team = null): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('agent');
        $team?->users()->attach($user, ['role_in_team' => 'member']);

        return $user;
    }
}

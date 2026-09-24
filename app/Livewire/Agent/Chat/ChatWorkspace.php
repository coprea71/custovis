<?php

namespace App\Livewire\Agent\Chat;

use App\Models\ChatChannel;
use App\Models\ChatDirectThread;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.agent')]
class ChatWorkspace extends Component
{
    use WithFileUploads;

    private const MESSAGE_LIMIT = 100;

    #[Url]
    public ?int $channel = null;

    #[Url]
    public ?int $direct = null;

    public string $body = '';

    public $file = null;

    public ?int $newDirectUserId = null;

    public function mount(?Ticket $ticket = null): void
    {
        Gate::authorize('chat.channels.view');

        if ($ticket?->exists) {
            abort_unless(app(ChatAccess::class)->canSeeTicket(Auth::user(), $ticket), 403);
            $this->channel = app(ChatAccess::class)->ticketChannel($ticket)->id;
        }
    }

    /**
     * Live update via Reverb for the open conversation only; the sidebar
     * counters are refreshed by the wire:poll fallback in the view.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $conversation = $this->conversation();

        return $conversation ? ["echo-private:{$conversation->broadcastName()},ChatMessageSent" => '$refresh'] : [];
    }

    public function openChannel(int $channelId): void
    {
        $this->channel = $channelId;
        $this->direct = null;
    }

    public function openDirect(int $threadId): void
    {
        $this->direct = $threadId;
        $this->channel = null;
    }

    public function startDirect(ChatAccess $access): void
    {
        $this->validate(['newDirectUserId' => ['required', 'integer', 'exists:users,id']]);

        $thread = $access->directThread(Auth::user(), User::query()->findOrFail($this->newDirectUserId));
        $this->newDirectUserId = null;
        $this->openDirect($thread->id);
    }

    public function send(ChatService $chat): void
    {
        $this->validate([
            'body' => ['required_without:file', 'nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $conversation = $this->conversation();
        abort_unless($conversation, 404);

        $chat->send(Auth::user(), $conversation, $this->body !== '' ? $this->body : null, $this->file);
        $this->reset(['body', 'file']);
    }

    public function render(ChatAccess $access, ChatService $chat)
    {
        $user = Auth::user();
        $conversation = $this->conversation();
        abort_if($conversation && ! $access->canView($user, $conversation), 403);

        if ($conversation) {
            $chat->markRead($user, $conversation);
        }

        return view('livewire.agent.chat.chat-workspace', [
            'channels' => $access->visibleChannels($user)->map(fn ($c) => [$c, $chat->unreadCount($user, $c)]),
            'threads' => $access->directThreads($user)->map(fn ($t) => [$t, $chat->unreadCount($user, $t)]),
            'conversation' => $conversation,
            'messages' => $conversation ? $conversation->messages()->with('author')->latest('id')->limit(self::MESSAGE_LIMIT)->get()->reverse() : collect(),
            'canPost' => $conversation && $access->canPost($user, $conversation),
            'directCandidates' => $user->can('chat.direct.create') ? User::permission('chat.channels.view')->whereKeyNot($user->id)->orderBy('name')->get() : collect(),
        ]);
    }

    private function conversation(): ChatChannel|ChatDirectThread|null
    {
        return match (true) {
            $this->channel !== null => ChatChannel::query()->find($this->channel),
            $this->direct !== null => ChatDirectThread::query()->with('participants')->find($this->direct),
            default => null,
        };
    }
}

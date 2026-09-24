<?php

namespace App\Services\Chat;

use App\Events\ChatMessageSent;
use App\Models\ChatChannel;
use App\Models\ChatDirectThread;
use App\Models\ChatMessage;
use App\Models\ChatReadState;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;

class ChatService
{
    public function __construct(
        private readonly ChatAccess $access,
        private readonly AttachmentService $attachments,
    ) {}

    public function send(User $author, ChatChannel|ChatDirectThread $conversation, ?string $body, ?UploadedFile $file = null): ChatMessage
    {
        abort_unless($this->access->canPost($author, $conversation), 403);

        $message = $conversation->messages()->create([
            'user_id' => $author->id,
            'body' => $body,
            ...($file ? $this->attachmentAttributes($file, $conversation) : []),
        ]);

        $this->markRead($author, $conversation);
        ChatMessageSent::dispatch($message);

        return $message;
    }

    public function markRead(User $user, ChatChannel|ChatDirectThread $conversation): void
    {
        $lastId = (int) $conversation->messages()->max('id');

        ChatReadState::query()->updateOrCreate(
            $this->readStateKey($user, $conversation),
            ['last_read_message_id' => $lastId]
        );
    }

    public function unreadCount(User $user, ChatChannel|ChatDirectThread $conversation): int
    {
        $lastRead = (int) ChatReadState::query()->where($this->readStateKey($user, $conversation))->value('last_read_message_id');

        return $conversation->messages()
            ->where('id', '>', $lastRead)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $user->id))
            ->count();
    }

    public function totalUnread(User $user): int
    {
        return $this->access->visibleChannels($user)
            ->concat($this->access->directThreads($user))
            ->sum(fn ($conversation) => $this->unreadCount($user, $conversation));
    }

    /**
     * @return array<string, int|null>
     */
    private function readStateKey(User $user, ChatChannel|ChatDirectThread $conversation): array
    {
        return [
            'user_id' => $user->id,
            'channel_id' => $conversation instanceof ChatChannel ? $conversation->id : null,
            'direct_thread_id' => $conversation instanceof ChatDirectThread ? $conversation->id : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attachmentAttributes(UploadedFile $file, ChatChannel|ChatDirectThread $conversation): array
    {
        $stored = $this->attachments->store($file, 'chat-attachments/'.class_basename($conversation).'-'.$conversation->id);

        return [
            'attachment_disk' => $stored['disk'],
            'attachment_path' => $stored['path'],
            'attachment_name' => $stored['original_name'],
            'attachment_mime' => $stored['mime_type'],
            'attachment_size' => $stored['size_bytes'],
        ];
    }
}

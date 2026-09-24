<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Only the ids go over the socket; clients reload the message through the
 * authorized Livewire component instead of trusting the pushed payload.
 */
class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'notifications';

    public function __construct(public ChatMessage $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->message->conversation()->broadcastName());
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['message_id' => $this->message->id];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'channel_id',
        'direct_thread_id',
        'user_id',
        'body',
        'attachment_disk',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<ChatChannel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    /**
     * @return BelongsTo<ChatDirectThread, $this>
     */
    public function directThread(): BelongsTo
    {
        return $this->belongsTo(ChatDirectThread::class, 'direct_thread_id');
    }

    public function conversation(): ChatChannel|ChatDirectThread
    {
        return $this->channel ?? $this->directThread;
    }
}

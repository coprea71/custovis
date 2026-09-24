<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatDirectThread extends Model
{
    protected $fillable = [
        'participant_key',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_direct_thread_participants', 'thread_id')->withTimestamps();
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'direct_thread_id');
    }

    public static function keyFor(User $a, User $b): string
    {
        return min($a->id, $b->id).'-'.max($a->id, $b->id);
    }

    public function broadcastName(): string
    {
        return "chat.direct.{$this->id}";
    }
}

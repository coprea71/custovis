<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatChannel extends Model
{
    public const TYPE_TEAM = 'team';

    public const TYPE_GLOBAL = 'global';

    public const TYPE_TICKET = 'ticket';

    protected $fillable = [
        'type',
        'team_id',
        'ticket_id',
        'name',
    ];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id');
    }

    public function broadcastName(): string
    {
        return "chat.channel.{$this->id}";
    }
}

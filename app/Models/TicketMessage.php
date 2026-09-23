<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    use HasFactory;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_INTERNAL_NOTE = 'internal_note';

    protected $fillable = [
        'ticket_id',
        'visibility',
        'direction',
        'author_user_id',
        'author_customer_id',
        'external_author_name',
        'external_author_email',
        'body_html',
        'body_text',
        'message_id',
        'whatsapp_message_id',
        'whatsapp_message_type',
    ];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function authorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'author_customer_id');
    }

    /**
     * @return HasMany<TicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function isInternalNote(): bool
    {
        return $this->visibility === self::VISIBILITY_INTERNAL_NOTE;
    }
}

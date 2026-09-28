<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\NotSpamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use Auditable, HasFactory;

    public const STATUSES = ['open', 'pending', 'closed'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent', 'emergency'];

    /**
     * Priorities automated classification may choose; an emergency is only
     * declared by a person or an explicit channel value.
     */
    public const AUTOMATIC_PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public const STATUS_LABELS = ['open' => 'Offen', 'pending' => 'Wartend', 'closed' => 'Geschlossen'];

    public const PRIORITY_LABELS = ['low' => 'Niedrig', 'normal' => 'Normal', 'high' => 'Hoch', 'urgent' => 'Dringend', 'emergency' => 'Notfall'];

    /**
     * Left stripe in the ticket list (normal has none); an emergency is
     * additionally framed in red. Full class names so Tailwind's source scan
     * picks them up.
     */
    public const PRIORITY_STRIPES = [
        'low' => 'border-l-slate-400',
        'normal' => 'border-l-transparent',
        'high' => 'border-l-orange-500',
        'urgent' => 'border-l-red-600',
        'emergency' => 'border-l-red-600 ring-2 ring-inset ring-red-600',
    ];

    /**
     * Mirrors the column defaults so a freshly created ticket already carries
     * them (SLA matching by priority and audit diffs rely on real values).
     */
    protected $attributes = [
        'type' => 'support_ticket',
        'status' => 'open',
        'priority' => 'normal',
    ];

    protected array $auditFields = [
        'team_id',
        'type',
        'status',
        'priority',
        'assigned_to',
        'resolved_with_article_id',
        'spam_at',
    ];

    protected $fillable = [
        'team_id',
        'mailbox_id',
        'whatsapp_account_id',
        'type',
        'source',
        'external_ref',
        'subject',
        'status',
        'priority',
        'customer_id',
        'requester_email',
        'requester_name',
        'requester_phone',
        'assigned_to',
        'tags',
        'resolved_with_article_id',
        'sla_policy_id',
        'sla_response_due_at',
        'sla_resolution_due_at',
        'sla_breached_at',
        'closed_at',
        'spam_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'tags' => 'array',
            'sla_response_due_at' => 'datetime',
            'sla_resolution_due_at' => 'datetime',
            'sla_breached_at' => 'datetime',
            'spam_at' => 'datetime',
        ];
    }

    /**
     * Links new tickets of every channel to a known customer by e-mail, so
     * customer SLAs apply from the start and the ticket shows in the portal
     * (same matching as Customer::linkUnassignedTickets()).
     */
    /**
     * Removes our "[Ticket #N]" tags, so a subject never carries the number
     * of another ticket (e.g. "[Ticket #5] RE: [Ticket #3] …").
     */
    public static function withoutSubjectTags(string $subject): string
    {
        return trim(preg_replace(['/\[Ticket #\d+\]/i', '/\s{2,}/'], ['', ' '], $subject));
    }

    /**
     * Ticket number from a "[Ticket #N]" tag in a mail subject, if any.
     */
    public static function idFromSubjectTag(string $subject): ?int
    {
        return preg_match('/\[Ticket #(\d+)\]/i', $subject, $match) ? (int) $match[1] : null;
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new NotSpamScope);

        static::creating(function (Ticket $ticket) {
            if ($ticket->customer_id === null && $ticket->requester_email) {
                $ticket->customer_id = Customer::query()->where('email', $ticket->requester_email)->value('id');
            }
        });
    }

    /**
     * Customer e-mails are stored lower-cased; normalising here keeps the
     * exact-match lookups (linking, portal policy) independent of how a mail
     * client or API caller spelled the address.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function requesterEmail(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => filled($value) ? Str::lower(trim($value)) : null);
    }

    /**
     * Query-side counterpart of TicketPolicy::view for agent lists.
     *
     * @param  Builder<Ticket>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->can('tickets.view.all')) {
            return;
        }

        $query->where(fn (Builder $inner) => $inner
            ->whereIn('team_id', $user->teams()->select('teams.id'))
            ->orWhere('assigned_to', $user->id));
    }

    /**
     * Adds a boolean has_unread for the list marker.
     *
     * @param  Builder<Ticket>  $query
     */
    public function scopeWithUnreadFlag(Builder $query, User $user): void
    {
        $query->withExists(['messages as has_unread' => self::unreadMessagesFor($user)]);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    public function scopeUnreadFor(Builder $query, User $user): void
    {
        $query->whereHas('messages', self::unreadMessagesFor($user));
    }

    /**
     * Unread = above the user's read watermark (0 if the ticket was never
     * opened) and written by someone else.
     */
    private static function unreadMessagesFor(User $user): \Closure
    {
        return fn (Builder $messages) => $messages
            ->notAuthoredBy($user)
            ->where('ticket_messages.id', '>', TicketReadState::query()
                ->selectRaw('coalesce(max(last_read_message_id), 0)')
                ->whereColumn('ticket_read_states.ticket_id', 'ticket_messages.ticket_id')
                ->where('ticket_read_states.user_id', $user->id));
    }

    /**
     * A reopened ticket is a plain open ticket again so it shows up in the
     * default agent filter; who reopened it is kept in the history instead.
     */
    public function reopen(string $actorName, ?int $authorUserId = null): void
    {
        $this->update(['status' => 'open', 'closed_at' => null]);
        $this->recordReopening($actorName, $authorUserId);
    }

    public function recordReopening(string $actorName, ?int $authorUserId = null): void
    {
        $this->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_INTERNAL_NOTE,
            'direction' => 'outgoing',
            'author_user_id' => $authorUserId,
            'external_author_name' => $authorUserId ? null : 'System',
            'body_text' => 'Ticket am '.now()->format('d.m.Y H:i').' durch '.$actorName.' wiedereröffnet.',
        ]);
    }

    /**
     * Priority is stored as a string, so its business order has to be spelled
     * out; the CASE is built only from the PRIORITIES constant, never user input.
     *
     * @param  Builder<Ticket>  $query
     */
    /**
     * Spam tickets only, for the admin spam folder.
     *
     * @param  Builder<Ticket>  $query
     */
    public function scopeOnlySpam(Builder $query): void
    {
        $query->withoutGlobalScope(NotSpamScope::class)->whereNotNull('spam_at');
    }

    public function scopeOrderByPriority(Builder $query, string $direction = 'desc'): void
    {
        $cases = collect(self::PRIORITIES)
            ->map(fn (string $priority, int $rank) => "WHEN '{$priority}' THEN {$rank}")
            ->implode(' ');

        $query->orderByRaw("CASE priority {$cases} ELSE 0 END ".($direction === 'asc' ? 'asc' : 'desc'));
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Mailbox, $this>
     */
    public function mailbox(): BelongsTo
    {
        return $this->belongsTo(Mailbox::class);
    }

    /**
     * @return BelongsTo<WhatsappAccount, $this>
     */
    public function whatsappAccount(): BelongsTo
    {
        return $this->belongsTo(WhatsappAccount::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return HasOne<TicketIncident, $this>
     */
    public function incident(): HasOne
    {
        return $this->hasOne(TicketIncident::class);
    }

    /**
     * @return HasOne<TicketProblem, $this>
     */
    public function problem(): HasOne
    {
        return $this->hasOne(TicketProblem::class);
    }

    /**
     * @return HasOne<TicketChange, $this>
     */
    public function change(): HasOne
    {
        return $this->hasOne(TicketChange::class);
    }

    /**
     * @return HasOne<TicketServiceRequest, $this>
     */
    public function serviceRequest(): HasOne
    {
        return $this->hasOne(TicketServiceRequest::class);
    }

    /**
     * @return BelongsTo<SlaPolicy, $this>
     */
    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    /**
     * @return HasMany<ServiceAppointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(ServiceAppointment::class)->orderBy('scheduled_start');
    }

    /**
     * @return BelongsTo<KnowledgeBaseArticle, $this>
     */
    public function resolvedWithArticle(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'resolved_with_article_id');
    }

    /**
     * @return BelongsToMany<CmdbConfigurationItem, $this>
     */
    public function configurationItems(): BelongsToMany
    {
        return $this->belongsToMany(CmdbConfigurationItem::class, 'ticket_configuration_items');
    }
}

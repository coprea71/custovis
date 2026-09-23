<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    public const STATUSES = ['open', 'pending', 'closed', 'reopened'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

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
        'sla_policy_id',
        'sla_response_due_at',
        'sla_resolution_due_at',
        'sla_breached_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'tags' => 'array',
            'sla_response_due_at' => 'datetime',
            'sla_resolution_due_at' => 'datetime',
            'sla_breached_at' => 'datetime',
        ];
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
     * @return BelongsToMany<CmdbConfigurationItem, $this>
     */
    public function configurationItems(): BelongsToMany
    {
        return $this->belongsToMany(CmdbConfigurationItem::class, 'ticket_configuration_items');
    }
}

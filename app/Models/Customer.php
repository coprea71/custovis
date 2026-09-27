<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'mobile',
        'street',
        'postal_code',
        'city',
        'country',
        'vat_id',
        'buyer_reference',
        'notes',
        'password',
        'active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'active' => true,
        'country' => 'DE',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        // Internal agent notes must never reach the customer via serialization.
        'notes',
    ];

    /**
     * Whitelist validation of the contact fields, shared by the customer
     * administration and the ticket sidebar.
     *
     * @return array<string, array<int, string>>
     */
    public static function contactRules(): array
    {
        $phone = ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()\/.-]+$/'];

        return [
            'phone' => $phone,
            'mobile' => $phone,
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9 -]+$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function hasContactDetails(): bool
    {
        return (bool) ($this->phone || $this->mobile || $this->street || $this->city);
    }

    /**
     * @return HasMany<SlaPolicy, $this>
     */
    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Claims tickets that were created (e.g. by mail) before the account
     * existed, so they stay linked even if the account e-mail changes later.
     *
     * @return int number of linked tickets
     */
    public function linkUnassignedTickets(): int
    {
        return Ticket::query()
            ->whereNull('customer_id')
            ->where('requester_email', $this->email)
            ->update(['customer_id' => $this->id]);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role_in_team')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Mailbox, $this>
     */
    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return HasMany<ApiClient, $this>
     */
    public function apiClients(): HasMany
    {
        return $this->hasMany(ApiClient::class);
    }

    /**
     * @return HasMany<GitIssueConnection, $this>
     */
    public function gitIssueConnections(): HasMany
    {
        return $this->hasMany(GitIssueConnection::class);
    }

    /**
     * @return HasMany<WhatsappAccount, $this>
     */
    public function whatsappAccounts(): HasMany
    {
        return $this->hasMany(WhatsappAccount::class);
    }
}

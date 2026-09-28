<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Blocks a sender address or a whole domain for one team's mailboxes;
 * new tickets from a match are created as spam instead of reaching agents.
 */
class SpamRule extends Model
{
    public const TYPE_EMAIL = 'email';

    public const TYPE_DOMAIN = 'domain';

    public const TYPES = [self::TYPE_EMAIL, self::TYPE_DOMAIN];

    protected $fillable = [
        'team_id',
        'type',
        'value',
        'created_by',
    ];

    public static function valueFor(string $type, string $email): string
    {
        $email = Str::lower(trim($email));

        return $type === self::TYPE_DOMAIN ? Str::after($email, '@') : $email;
    }

    /**
     * A domain rule also covers its subdomains (x.de blocks mail.x.de).
     */
    public static function matches(int $teamId, string $email): bool
    {
        $email = Str::lower(trim($email));
        $domain = Str::after($email, '@');

        if ($domain === '' || $domain === $email) {
            return false;
        }

        return static::query()->where('team_id', $teamId)->where(fn ($query) => $query
            ->where(fn ($q) => $q->where('type', self::TYPE_EMAIL)->where('value', $email))
            ->orWhere(fn ($q) => $q->where('type', self::TYPE_DOMAIN)->whereIn('value', self::domainCandidates($domain)))
        )->exists();
    }

    /**
     * mail.x.de → [mail.x.de, x.de]; the bare TLD is never a candidate.
     *
     * @return list<string>
     */
    private static function domainCandidates(string $domain): array
    {
        $parts = explode('.', $domain);
        $candidates = [];

        for ($i = 0; $i < count($parts) - 1; $i++) {
            $candidates[] = implode('.', array_slice($parts, $i));
        }

        return $candidates;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

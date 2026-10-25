<?php

namespace App\Support;

use App\Models\TeamMailLayout;
use Illuminate\Support\Str;

/**
 * Recognises error reports of web applications/monitoring (HTTP 4xx/5xx) that
 * a team routes into the ticket system on purpose. A bare three-digit number
 * is not enough — "Bestellung 404" must stay a normal ticket — so the code
 * needs an HTTP context word before it or its reason phrase after it.
 */
class HttpErrorMail
{
    private const STATUS_PATTERN = '/\b(?:http(?:\/\d(?:\.\d)?)?|error|fehler|status(?:code)?|code)[\s:#-]*([45]\d\d)\b'
        .'|\b([45]\d\d)[\s:-]+(?:bad request|unauthori[sz]ed|forbidden|not found|method not allowed|request time-?out|conflict|gone'
        .'|too many requests|internal server error|not implemented|bad gateway|service unavailable|gateway time-?out)\b/i';

    /**
     * @return int|null the HTTP status when the filter is on and the mail matches
     */
    public static function detect(TeamMailLayout $layout, string $fromEmail, string $subject): ?int
    {
        if (! $layout->error_mail_filter_enabled || ! self::senderAllowed($layout, $fromEmail)) {
            return null;
        }

        return self::statusFromSubject($subject);
    }

    public static function statusFromSubject(string $subject): ?int
    {
        if (! preg_match(self::STATUS_PATTERN, $subject, $match)) {
            return null;
        }

        $status = (int) ($match[1] ?: $match[2]);

        return ($status <= 451 || ($status >= 500 && $status <= 511)) ? $status : null;
    }

    /**
     * An empty sender list accepts every sender; a domain entry also covers
     * its subdomains (x.de accepts monitor.x.de).
     */
    private static function senderAllowed(TeamMailLayout $layout, string $fromEmail): bool
    {
        $senders = self::parseSenders((string) $layout->error_mail_senders);

        if ($senders === []) {
            return true;
        }

        $email = Str::lower(trim($fromEmail));
        $domain = Str::after($email, '@');

        foreach ($senders as $sender) {
            if (str_contains($sender, '@') ? $sender === $email : ($domain === $sender || str_ends_with($domain, '.'.$sender))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string> lower-cased addresses and domains, one per line ("@x.de" counts as domain)
     */
    public static function parseSenders(string $senders): array
    {
        return collect(preg_split('/[\r\n,;]+/', Str::lower($senders)))
            ->map(fn (string $line) => ltrim(trim($line), '@'))
            ->filter()->unique()->values()->all();
    }
}

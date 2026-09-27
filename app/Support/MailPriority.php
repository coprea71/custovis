<?php

namespace App\Support;

use Webklex\PHPIMAP\Message;

/**
 * Maps the priority headers mail clients set (Outlook, Thunderbird, Apple
 * Mail, RFC 2156) to a ticket priority. Only raised priorities count — a
 * sender cannot push a ticket below the default by marking mail "low".
 */
class MailPriority
{
    public static function fromMessage(Message $message): ?string
    {
        $header = $message->getHeader();
        $value = fn (string $name) => $header?->get($name)->first() !== null ? strtolower(trim((string) $header->get($name)->first())) : '';

        return self::fromHeaders($value('x-priority'), $value('importance'), $value('x-msmail-priority'), $value('priority'));
    }

    public static function fromHeaders(string $xPriority, string $importance, string $msPriority, string $priority): ?string
    {
        if ($priority === 'urgent') {
            return 'urgent';
        }

        // X-Priority: "1 (Highest)" / "2 (High)"; 3 is normal.
        $highXPriority = preg_match('/^\s*([12])\b/', $xPriority) === 1;

        return $highXPriority || $importance === 'high' || $msPriority === 'high' ? 'high' : null;
    }
}

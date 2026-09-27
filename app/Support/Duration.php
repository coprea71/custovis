<?php

namespace App\Support;

/**
 * Converts between the "1:30" / "90" input agents type and stored minutes.
 */
class Duration
{
    public const PATTERN = '/^(\d{1,3}:[0-5]\d|\d{1,4})$/';

    public static function parse(string $input): ?int
    {
        $input = trim($input);

        if (! preg_match(self::PATTERN, $input)) {
            return null;
        }

        if (str_contains($input, ':')) {
            [$hours, $minutes] = explode(':', $input);

            return (int) $hours * 60 + (int) $minutes;
        }

        return (int) $input;
    }

    public static function format(int $minutes): string
    {
        return intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }
}

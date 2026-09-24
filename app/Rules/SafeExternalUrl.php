<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * External service URLs: HTTPS only (plain HTTP just for local/testing),
 * no embedded credentials, no query or fragment.
 */
class SafeExternalUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) && filter_var($value, FILTER_VALIDATE_URL) ? parse_url($value) : false;

        if (! is_array($parts) || empty($parts['host'])) {
            $fail('Bitte eine gültige URL angeben.');

            return;
        }

        $allowedSchemes = app()->environment('local', 'testing') ? ['https', 'http'] : ['https'];

        if (! in_array(strtolower($parts['scheme'] ?? ''), $allowedSchemes, true)) {
            $fail('Die URL muss HTTPS verwenden.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            $fail('Zugangsdaten dürfen nicht in der URL stehen.');
        }

        if (isset($parts['query']) || isset($parts['fragment'])) {
            $fail('Die URL darf keine Parameter oder Anker enthalten.');
        }
    }
}

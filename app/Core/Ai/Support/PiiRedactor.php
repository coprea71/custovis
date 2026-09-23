<?php

namespace App\Core\Ai\Support;

/**
 * Best-effort redaction of common PII patterns before text leaves the
 * system to an external AI provider (DSGVO, 6.md). Not exhaustive — a
 * regex pass, not an NER model — but covers the common cases (emails,
 * phone numbers, IBANs).
 */
class PiiRedactor
{
    public function redact(string $text): string
    {
        $text = preg_replace('/[\w.+-]+@[\w-]+\.[a-zA-Z]{2,}/', '[REDACTED-EMAIL]', $text);
        $text = preg_replace('/\+?\d[\d\s\-\/()]{7,}\d/', '[REDACTED-PHONE]', $text);
        $text = preg_replace('/\b[A-Z]{2}\d{2}[A-Z0-9]{10,30}\b/', '[REDACTED-IBAN]', $text);

        return $text;
    }
}

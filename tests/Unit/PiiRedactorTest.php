<?php

namespace Tests\Unit;

use App\Core\Ai\Support\PiiRedactor;
use Tests\TestCase;

class PiiRedactorTest extends TestCase
{
    public function test_redacts_email_and_phone(): void
    {
        $redactor = new PiiRedactor;

        $result = $redactor->redact('Kontakt: max@example.com oder +49 170 1234567.');

        $this->assertStringNotContainsString('max@example.com', $result);
        $this->assertStringNotContainsString('170 1234567', $result);
        $this->assertStringContainsString('[REDACTED-EMAIL]', $result);
        $this->assertStringContainsString('[REDACTED-PHONE]', $result);
    }
}

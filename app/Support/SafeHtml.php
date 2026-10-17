<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Filters message HTML before it is rendered in the browser: incoming mails
 * carry sender-controlled markup, which would otherwise run as script in the
 * agent's session (stored XSS). Applied on output, so stored messages are
 * covered too.
 */
class SafeHtml
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(string $html): string
    {
        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
                ->forceAttribute('a', 'target', '_blank')
                // Remote images would let the sender track when an agent opens the ticket.
                ->dropElement('img')
                // Mails easily exceed the 20 KB default, which would cut them off.
                ->withMaxInputLength(-1)
        );
    }
}

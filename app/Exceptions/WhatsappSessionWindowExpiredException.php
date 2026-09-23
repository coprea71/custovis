<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a free-text WhatsApp reply is attempted more than 24 hours
 * after the customer's last inbound message — Meta requires an approved
 * template outside that window (4.md).
 */
class WhatsappSessionWindowExpiredException extends Exception
{
    protected $message = 'Das 24-Stunden-Antwortfenster ist abgelaufen. Bitte eine genehmigte Vorlage auswählen.';
}

<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent synchronously on purpose: a queued mail would persist the plain
 * reset token in the jobs table.
 */
class CustomerPasswordLinkMail extends Mailable
{
    public function __construct(
        public Customer $customer,
        private readonly string $token,
        public bool $invitation = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invitation ? 'Ihr Zugang zum Kundenportal' : 'Passwort für das Kundenportal zurücksetzen',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.customer-password-link',
            with: [
                'url' => $this->url(),
                'expireMinutes' => (int) config('auth.passwords.customers.expire'),
            ],
        );
    }

    public function url(): string
    {
        return route('portal.password.reset', ['token' => $this->token, 'email' => $this->customer->email]);
    }
}

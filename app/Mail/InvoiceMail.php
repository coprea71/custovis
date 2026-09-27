<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Carries both e-invoice formats: the ZUGFeRD PDF (readable by humans and
 * machines) and the plain XRechnung XML for receivers that expect it.
 */
class InvoiceMail extends Mailable
{
    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invoice->title().' '.$this->invoice->number.' – '.$this->invoice->seller['company'],
            replyTo: [$this->invoice->seller['email']],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.invoice-text');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $name = $this->invoice->fileBaseName();

        return [
            Attachment::fromStorageDisk('local', $this->invoice->pdf_path)->as($name.'.pdf')->withMime('application/pdf'),
            Attachment::fromStorageDisk('local', $this->invoice->xml_path)->as($name.'_xrechnung.xml')->withMime('application/xml'),
        ];
    }
}

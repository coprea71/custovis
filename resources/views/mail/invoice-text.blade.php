Guten Tag,

@if ($invoice->isCancellation())
anbei erhalten Sie die Stornorechnung {{ $invoice->number }} zur Rechnung {{ $invoice->cancelledInvoice->number }}.
@else
anbei erhalten Sie unsere Rechnung {{ $invoice->number }} vom {{ $invoice->issue_date->format('d.m.Y') }} über {{ \App\Models\Invoice::money($invoice->gross_cents) }}.
Bitte überweisen Sie den Betrag bis zum {{ $invoice->due_date->format('d.m.Y') }} auf das in der Rechnung genannte Konto.
@endif

Die Rechnung liegt als E-Rechnung in zwei Formaten bei:
- PDF mit eingebetteten Rechnungsdaten (ZUGFeRD, Profil EN 16931)
- XML im Format XRechnung 3.0

Mit freundlichen Grüßen
{{ $invoice->seller['contact_name'] }}
{{ $invoice->seller['company'] }}
{{ $invoice->seller['phone'] }} · {{ $invoice->seller['email'] }}

<?php

namespace App\Services\Invoicing;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\MailSenderService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Draft handling, issuing and cancelling of invoices (32.md). Issued
 * invoices are never changed — corrections go through a cancellation.
 */
class InvoiceService
{
    public function __construct(
        private InvoiceSettings $settings,
        private InvoiceNumberGenerator $numbers,
        private EInvoiceBuilder $builder,
        private MailSenderService $mailer,
    ) {}

    public function createDraft(Customer $customer, Team $team, User $by): Invoice
    {
        return Invoice::query()->create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'tax_rate' => $this->settings->get('tax_rate'),
            'small_business' => $this->settings->smallBusiness(),
            'created_by' => $by->id,
        ]);
    }

    /**
     * Collective invoices (Sammelrechnungen): one draft per customer with
     * open billable time on the team's tickets within the period.
     *
     * @return int number of created drafts
     */
    public function createCollectiveDrafts(Team $team, CarbonInterface $from, CarbonInterface $to, User $by): int
    {
        $customerIds = Ticket::query()->where('team_id', $team->id)->whereNotNull('customer_id')
            ->whereHas('timeEntries', fn ($entries) => $entries->openForBilling()->whereBetween('work_date', [$from->toDateString(), $to->toDateString()]))
            ->distinct()->pluck('customer_id');

        Customer::query()->whereKey($customerIds)->get()->each(function (Customer $customer) use ($team, $from, $to, $by) {
            $this->importOpenTime($this->createDraft($customer, $team, $by), $from, $to);
        });

        return $customerIds->count();
    }

    /**
     * Adds one item per ticket of the customer and team with open billable
     * time; with a period only that period's time is billed and it becomes
     * the service period of the invoice (§ 14 Abs. 4 Nr. 6 UStG).
     *
     * @return int number of imported time entries
     */
    public function importOpenTime(Invoice $invoice, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        $this->assertDraft($invoice);
        $entries = TimeEntry::query()->openForBilling()
            ->whereIn('ticket_id', Ticket::query()->where('customer_id', $invoice->customer_id)->where('team_id', $invoice->team_id)->select('id'))
            ->when($from && $to, fn ($query) => $query->whereBetween('work_date', [$from->toDateString(), $to->toDateString()]))
            ->with('ticket:id,subject')->orderBy('work_date')->get();

        $entries->groupBy('ticket_id')->each(fn (Collection $ticketEntries) => $this->addTimeItem($invoice, $ticketEntries));
        if ($from && $to && $entries->isNotEmpty() && ! $invoice->service_from) {
            $invoice->update(['service_from' => $from, 'service_to' => $to]);
        }
        $this->recalculate($invoice);

        return $entries->count();
    }

    public function addItem(Invoice $invoice, string $description, float $quantity, string $unitCode, int $unitPriceCents, ?int $ticketId = null): InvoiceItem
    {
        $this->assertDraft($invoice);

        $item = $invoice->items()->create([
            'position' => (int) $invoice->items()->max('position') + 1,
            'ticket_id' => $ticketId,
            'description' => $description,
            'quantity' => round($quantity, 2),
            'unit_code' => $unitCode,
            'unit_price_cents' => $unitPriceCents,
            'net_cents' => (int) round(round($quantity, 2) * $unitPriceCents),
        ]);
        $this->recalculate($invoice);

        return $item;
    }

    public function removeItem(InvoiceItem $item): void
    {
        $this->assertDraft($item->invoice);
        $item->timeEntries()->update(['invoice_item_id' => null]);
        $item->delete();
        $this->recalculate($item->invoice);
    }

    public function deleteDraft(Invoice $invoice): void
    {
        $this->assertDraft($invoice);
        $invoice->items->each(fn (InvoiceItem $item) => $item->timeEntries()->update(['invoice_item_id' => null]));
        $invoice->delete();
    }

    public function recalculate(Invoice $invoice): void
    {
        $net = (int) $invoice->items()->sum('net_cents');
        $tax = $invoice->small_business ? 0 : (int) round($net * (float) $invoice->tax_rate / 100);

        $invoice->forceFill(['net_cents' => $net, 'tax_cents' => $tax, 'gross_cents' => $net + $tax])->save();
    }

    public function issue(Invoice $invoice, User $by): Invoice
    {
        $this->assertDraft($invoice);
        $this->assertIssuable($invoice);

        DB::transaction(function () use ($invoice, $by) {
            $this->recalculate($invoice);
            $invoice->forceFill([
                'number' => $this->numbers->next((int) now()->format('Y')),
                'issue_date' => today(),
                'due_date' => today()->addDays((int) $this->settings->get('payment_days')),
                'seller' => $this->sellerSnapshot(),
                'buyer' => $invoice->isCancellation() ? $invoice->cancelledInvoice->buyer : $this->buyerSnapshot($invoice->customer),
                'status' => Invoice::STATUS_ISSUED,
                'issued_by' => $by->id,
            ])->save();
            $this->storeDocuments($invoice);
        });

        AuditLog::record('invoice.issued', $by, null, $invoice, ['number' => $invoice->number]);

        return $invoice;
    }

    public function cancel(Invoice $original, User $by): Invoice
    {
        abort_unless($original->status === Invoice::STATUS_ISSUED && ! $original->isCancellation(), 422);

        return DB::transaction(function () use ($original, $by) {
            $cancellation = $this->copyAsCancellation($original, $by);
            $this->issue($cancellation, $by);

            TimeEntry::query()->whereIn('invoice_item_id', $original->items()->select('id'))->update(['invoice_item_id' => null]);
            $original->update(['status' => Invoice::STATUS_CANCELLED]);

            return $cancellation;
        });
    }

    public function send(Invoice $invoice, User $by): void
    {
        abort_if($invoice->isDraft() || ! $invoice->pdf_path, 422);
        $mailbox = Mailbox::query()->find((int) $this->settings->get('mailbox_id'));

        $this->mailer->sendInvoice($invoice, $mailbox);
        $invoice->forceFill(['sent_at' => now()])->save();

        AuditLog::record('invoice.sent', $by, null, $invoice, ['via_mailbox' => $mailbox?->id]);
    }

    public function markPaid(Invoice $invoice, User $by): void
    {
        abort_unless($invoice->status === Invoice::STATUS_ISSUED && ! $invoice->isCancellation(), 422);
        $invoice->forceFill(['paid_at' => now()])->save();

        AuditLog::record('invoice.paid', $by, null, $invoice);
    }

    /**
     * @param  Collection<int, TimeEntry>  $entries
     */
    private function addTimeItem(Invoice $invoice, Collection $entries): void
    {
        $ticket = $entries->first()->ticket;
        $minutes = (int) $entries->sum('minutes');
        $period = $entries->min('work_date')->format('d.m.Y').' – '.$entries->max('work_date')->format('d.m.Y');

        $item = $this->addItem($invoice, "Ticket #{$ticket->id}: {$ticket->subject} ({$period})",
            $minutes / 60, 'HUR', $this->settings->hourlyRateCents(), $ticket->id);

        TimeEntry::query()->whereKey($entries->modelKeys())->update(['invoice_item_id' => $item->id]);
    }

    private function copyAsCancellation(Invoice $original, User $by): Invoice
    {
        $cancellation = Invoice::query()->create([
            'type' => Invoice::TYPE_CANCELLATION,
            'cancels_invoice_id' => $original->id,
            'team_id' => $original->team_id,
            'customer_id' => $original->customer_id,
            'service_from' => $original->service_from,
            'service_to' => $original->service_to,
            'tax_rate' => $original->tax_rate,
            'small_business' => $original->small_business,
            'notes' => 'Storno der Rechnung '.$original->number.' vom '.$original->issue_date->format('d.m.Y').'.',
            'created_by' => $by->id,
        ]);

        $original->items->each(fn (InvoiceItem $item) => $cancellation->items()->create(
            $item->only(['position', 'ticket_id', 'description', 'quantity', 'unit_code', 'unit_price_cents', 'net_cents'])
        ));

        return $cancellation;
    }

    private function storeDocuments(Invoice $invoice): void
    {
        $base = 'invoices/'.$invoice->issue_date->format('Y').'/'.$invoice->fileBaseName();
        $invoice->load('items', 'cancelledInvoice');

        Storage::disk('local')->put($base.'.xml', $this->builder->xrechnung($invoice));
        Storage::disk('local')->put($base.'.pdf', $this->builder->zugferdPdf($invoice));

        $invoice->forceFill(['xml_path' => $base.'.xml', 'pdf_path' => $base.'.pdf'])->save();
    }

    /**
     * @return array<string, string>
     */
    private function sellerSnapshot(): array
    {
        return collect($this->settings->all())->except(['small_business', 'tax_rate', 'hourly_rate', 'payment_days', 'number_prefix', 'mailbox_id'])->all();
    }

    /**
     * @return array<string, string|null>
     */
    private function buyerSnapshot(Customer $customer): array
    {
        return [
            'customer_number' => (string) $customer->id,
            'name' => $customer->company ?: $customer->name,
            'contact' => $customer->company ? $customer->name : null,
            'street' => $customer->street,
            'postal_code' => $customer->postal_code,
            'city' => $customer->city,
            'country' => $customer->country ?: 'DE',
            'vat_id' => $customer->vat_id,
            'email' => $customer->email,
            // BT-10 is mandatory in XRechnung; without a Leitweg-ID the customer number serves.
            'reference' => $customer->buyer_reference ?: 'Kunde '.$customer->id,
        ];
    }

    private function assertDraft(Invoice $invoice): void
    {
        abort_unless($invoice->isDraft(), 422, 'Nur Entwürfe können geändert werden.');
    }

    private function assertIssuable(Invoice $invoice): void
    {
        $errors = collect($this->settings->missingForIssue())->map(fn (string $label) => "Rechnungseinstellungen: {$label} fehlt.");

        if ($invoice->items()->doesntExist()) {
            $errors->push('Die Rechnung hat keine Positionen.');
        }
        if (! $invoice->isCancellation()) {
            $customer = $invoice->customer;
            $missingAddress = ! $customer || ! $customer->street || ! $customer->postal_code || ! $customer->city;
            $missingAddress && $errors->push('Beim Kunden fehlt die vollständige Anschrift (Straße, PLZ, Ort).');
        }

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages(['issue' => $errors->all()]);
        }
    }
}

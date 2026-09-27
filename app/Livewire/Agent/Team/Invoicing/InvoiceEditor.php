<?php

namespace App\Livewire\Agent\Team\Invoicing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Team;
use App\Services\Invoicing\InvoiceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

#[Layout('layouts.agent')]
class InvoiceEditor extends Component
{
    private const MONEY_PATTERN = '/^\d{1,7}([.,]\d{1,2})?$/';

    #[Locked]
    public Team $team;

    #[Locked]
    public Invoice $invoice;

    #[Locked]
    public bool $readOnly = true;

    public ?string $service_from = null;

    public ?string $service_to = null;

    public string $notes = '';

    public string $itemDescription = '';

    public string $itemQuantity = '1';

    public string $itemUnit = 'HUR';

    public string $itemPrice = '';

    public ?string $status = null;

    public function mount(Team $team, Invoice $invoice): void
    {
        abort_unless($invoice->team_id === $team->id, 404);
        $this->team = $team;
        $this->readOnly = ! InvoiceManager::authorizeView($team);
        $this->invoice = $invoice;
        $this->service_from = $invoice->service_from?->toDateString();
        $this->service_to = $invoice->service_to?->toDateString();
        $this->notes = (string) $invoice->notes;
    }

    public function saveDetails(): void
    {
        $this->authorizeDraft();
        $data = $this->validate([
            'service_from' => ['nullable', 'date', 'required_with:service_to'],
            'service_to' => ['nullable', 'date', 'required_with:service_from', 'after_or_equal:service_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->invoice->update(['service_from' => $data['service_from'] ?: null, 'service_to' => $data['service_to'] ?: null, 'notes' => trim($data['notes']) ?: null]);
        $this->status = 'Angaben gespeichert.';
    }

    public function importTime(InvoiceService $invoices): void
    {
        $this->authorizeDraft();
        $count = $invoices->importOpenTime($this->invoice);
        $this->status = $count ? "{$count} Zeiteintrag/-einträge übernommen." : 'Keine offenen abrechenbaren Zeiten für diesen Kunden.';
    }

    public function addItem(InvoiceService $invoices): void
    {
        $this->authorizeDraft();
        $this->validate([
            'itemDescription' => ['required', 'string', 'max:500'],
            'itemQuantity' => ['required', 'regex:'.self::MONEY_PATTERN],
            'itemUnit' => ['required', Rule::in(array_keys(InvoiceItem::UNITS))],
            'itemPrice' => ['required', 'regex:'.self::MONEY_PATTERN],
        ]);
        if (self::decimal($this->itemQuantity) <= 0) {
            $this->addError('itemQuantity', 'Die Menge muss größer als 0 sein.');

            return;
        }

        $invoices->addItem($this->invoice, trim($this->itemDescription), self::decimal($this->itemQuantity), $this->itemUnit,
            (int) round(self::decimal($this->itemPrice) * 100));
        $this->reset('itemDescription', 'itemPrice');
        $this->itemQuantity = '1';
    }

    public function removeItem(int $itemId, InvoiceService $invoices): void
    {
        $this->authorizeDraft();
        $invoices->removeItem($this->invoice->items()->findOrFail($itemId));
    }

    public function deleteDraft(InvoiceService $invoices): void
    {
        $this->authorizeDraft();
        $invoices->deleteDraft($this->invoice);
        $this->redirectRoute('agent.team.invoices', $this->team);
    }

    public function issue(InvoiceService $invoices): void
    {
        $this->authorizeDraft();
        $this->saveDetails();
        $invoices->issue($this->invoice, Auth::user());
        $this->status = 'Rechnung '.$this->invoice->number.' ausgestellt.';
    }

    public function send(InvoiceService $invoices): void
    {
        $this->authorizeEdit();

        try {
            $invoices->send($this->invoice, Auth::user());
            $this->status = 'Rechnung an '.$this->invoice->buyer['email'].' gesendet.';
        } catch (Throwable $e) {
            Log::warning('Invoice mail failed', ['invoice_id' => $this->invoice->id, 'error' => $e->getMessage()]);
            $this->addError('send', 'Versand fehlgeschlagen. Bitte die SMTP-Einstellungen prüfen (Details im Log).');
        }
    }

    public function markPaid(InvoiceService $invoices): void
    {
        $this->authorizeEdit();
        $invoices->markPaid($this->invoice, Auth::user());
        $this->status = 'Als bezahlt markiert.';
    }

    public function cancel(InvoiceService $invoices): void
    {
        $this->authorizeEdit();
        $cancellation = $invoices->cancel($this->invoice, Auth::user());
        $this->redirectRoute('agent.team.invoices.show', [$this->team, $cancellation]);
    }

    public function render()
    {
        $this->invoice->refresh()->load('items', 'customer', 'cancelledInvoice');

        return view('livewire.agent.team.invoicing.invoice-editor', [
            'cancellation' => Invoice::query()->where('cancels_invoice_id', $this->invoice->id)->first(),
        ]);
    }

    /**
     * Only team admins of the invoice's team create, issue, send or cancel (32.md).
     */
    private function authorizeEdit(): void
    {
        abort_unless(InvoiceManager::authorizeView($this->team), 403);
    }

    private function authorizeDraft(): void
    {
        $this->authorizeEdit();
        abort_unless($this->invoice->isDraft(), 422);
    }

    private static function decimal(string $value): float
    {
        return (float) str_replace(',', '.', $value);
    }
}

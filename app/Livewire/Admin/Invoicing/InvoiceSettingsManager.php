<?php

namespace App\Livewire\Admin\Invoicing;

use App\Models\AuditLog;
use App\Models\Mailbox;
use App\Services\Invoicing\InvoiceSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class InvoiceSettingsManager extends Component
{
    /** @var array<string, string> */
    public array $settings = [];

    public ?string $status = null;

    public function mount(InvoiceSettings $settings): void
    {
        Gate::authorize('invoices.manage');
        $this->settings = $settings->all();
    }

    public function save(InvoiceSettings $settings): void
    {
        Gate::authorize('invoices.manage');
        $this->settings['iban'] = strtoupper(str_replace(' ', '', $this->settings['iban'] ?? ''));
        $this->settings['bic'] = strtoupper(trim($this->settings['bic'] ?? ''));
        $this->validate($this->rules());

        $settings->save($this->settings);
        AuditLog::record('invoicing.settings_updated', Auth::user(), null);
        $this->status = 'Rechnungseinstellungen gespeichert.';
    }

    public function render(InvoiceSettings $settings)
    {
        return view('livewire.admin.invoicing.invoice-settings-manager', [
            'mailboxes' => Mailbox::query()->orderBy('name')->get(['id', 'name', 'email_address']),
            'missing' => $settings->missingForIssue(),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'settings.company' => ['nullable', 'string', 'max:255'],
            'settings.street' => ['nullable', 'string', 'max:255'],
            'settings.postal_code' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9 -]+$/'],
            'settings.city' => ['nullable', 'string', 'max:100'],
            'settings.country' => ['required', 'regex:/^[A-Z]{2}$/'],
            'settings.vat_id' => ['nullable', 'regex:/^[A-Z]{2}[A-Za-z0-9]{2,13}$/'],
            'settings.tax_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 \/-]+$/'],
            'settings.contact_name' => ['nullable', 'string', 'max:255'],
            'settings.email' => ['nullable', 'email', 'max:255'],
            'settings.phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ()\/.-]+$/'],
            'settings.iban' => ['nullable', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/'],
            'settings.bic' => ['nullable', 'regex:/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/'],
            'settings.bank_name' => ['nullable', 'string', 'max:255'],
            'settings.small_business' => ['required', 'in:0,1'],
            'settings.tax_rate' => ['required', 'numeric', 'between:0,99.99'],
            'settings.hourly_rate' => ['required', 'numeric', 'between:0,99999.99'],
            'settings.payment_days' => ['required', 'integer', 'between:0,365'],
            'settings.number_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z0-9-]+$/'],
            'settings.mailbox_id' => ['nullable', 'exists:mailboxes,id'],
        ];
    }
}

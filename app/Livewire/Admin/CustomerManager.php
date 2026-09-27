<?php

namespace App\Livewire\Admin;

use App\Mail\CustomerPasswordLinkMail;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Services\CustomerAccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CustomerManager extends Component
{
    use WithPagination;

    public string $search = '';

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $mobile = '';

    public string $street = '';

    public string $postal_code = '';

    public string $city = '';

    public string $notes = '';

    public string $company = '';

    public string $country = 'DE';

    public string $vat_id = '';

    public string $buyer_reference = '';

    public ?string $status = null;

    private const CONTACT_FIELDS = ['company', 'phone', 'mobile', 'street', 'postal_code', 'city', 'country', 'vat_id', 'buyer_reference', 'notes'];

    public function mount(): void
    {
        Gate::authorize('customers.manage');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $customerId): void
    {
        Gate::authorize('customers.manage');
        $customer = Customer::query()->findOrFail($customerId);

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->email = $customer->email;
        foreach (self::CONTACT_FIELDS as $field) {
            $this->{$field} = (string) $customer->{$field};
        }
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'name', 'email', ...self::CONTACT_FIELDS);
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('customers.manage');
        $this->email = Str::lower(trim($this->email));
        $this->country = Str::upper(trim($this->country));
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($this->editingId)],
            ...Customer::contactRules(),
            'company' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'regex:/^[A-Z]{2}$/'],
            'vat_id' => ['nullable', 'regex:/^[A-Z]{2}[A-Za-z0-9]{2,13}$/'],
            'buyer_reference' => ['nullable', 'string', 'max:100'],
        ]);
        $data = array_map(fn (string $value) => trim($value) === '' ? null : trim($value), $data);

        $this->editingId ? $this->updateCustomer($data) : $this->createCustomer($data);
        $this->cancelEdit();
    }

    public function toggleActive(int $customerId): void
    {
        Gate::authorize('customers.manage');
        $customer = Customer::query()->findOrFail($customerId);
        $customer->update(['active' => ! $customer->active]);

        AuditLog::record($customer->active ? 'customer.unlocked' : 'customer.locked', Auth::user(), null, $customer);
        $this->status = $customer->active ? 'Portal-Zugang entsperrt.' : 'Portal-Zugang gesperrt.';
    }

    public function invite(int $customerId): void
    {
        Gate::authorize('customers.manage');
        $customer = Customer::query()->findOrFail($customerId);

        if (! $customer->active) {
            $this->status = 'Gesperrte Kunden erhalten keinen Einladungs-Link.';

            return;
        }

        $token = Password::broker('customers')->createToken($customer);
        Mail::to($customer)->send(new CustomerPasswordLinkMail($customer, $token, invitation: true));

        AuditLog::record('customer.invited', Auth::user(), null, $customer);
        $this->status = 'Einladungs-Link wurde gesendet.';
    }

    public function render()
    {
        return view('livewire.admin.customer-manager', [
            'customers' => Customer::query()
                ->when($this->search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('mobile', 'like', '%'.$this->search.'%')))
                ->withCount('tickets')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function createCustomer(array $data): void
    {
        $linked = app(CustomerAccountService::class)->create($data, Auth::user());
        $this->status = "Kunde angelegt, {$linked} bestehende Ticket(s) zugeordnet.";
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function updateCustomer(array $data): void
    {
        $customer = Customer::query()->findOrFail($this->editingId);
        $customer->fill($data);
        $changed = array_keys($customer->getDirty());
        $customer->save();

        AuditLog::record('customer.updated', Auth::user(), null, $customer, ['fields_changed' => $changed]);
        $this->status = 'Kunde gespeichert.';
    }
}

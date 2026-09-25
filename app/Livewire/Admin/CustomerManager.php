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

    public ?string $status = null;

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
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'name', 'email');
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('customers.manage');
        $this->email = Str::lower(trim($this->email));
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($this->editingId)],
        ]);

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
                    ->orWhere('email', 'like', '%'.$this->search.'%')))
                ->withCount('tickets')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    /**
     * @param  array{name: string, email: string}  $data
     */
    private function createCustomer(array $data): void
    {
        $linked = app(CustomerAccountService::class)->create($data, Auth::user());
        $this->status = "Kunde angelegt, {$linked} bestehende Ticket(s) zugeordnet.";
    }

    /**
     * @param  array{name: string, email: string}  $data
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

<?php

namespace App\Livewire\Agent;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Contact details of the ticket's customer behind the sidebar info button;
 * missing details can be added right there by holders of customers.manage.
 */
class TicketCustomerContact extends Component
{
    private const FIELDS = ['phone', 'mobile', 'street', 'postal_code', 'city', 'notes'];

    #[Locked]
    public int $ticketId;

    public bool $editing = false;

    public string $phone = '';

    public string $mobile = '';

    public string $street = '';

    public string $postal_code = '';

    public string $city = '';

    public string $notes = '';

    public function edit(): void
    {
        Gate::authorize('customers.manage');
        foreach (self::FIELDS as $field) {
            $this->{$field} = (string) $this->customer()->{$field};
        }
        $this->resetValidation();
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('customers.manage');
        $data = array_map(fn (?string $value) => trim((string) $value) === '' ? null : trim($value), $this->validate(Customer::contactRules()));

        $customer = $this->customer();
        $customer->fill($data);
        $changed = array_keys($customer->getDirty());
        $customer->save();

        AuditLog::record('customer.updated', Auth::user(), null, $customer, ['fields_changed' => $changed]);
        $this->editing = false;
    }

    public function render()
    {
        return view('livewire.agent.ticket-customer-contact', ['customer' => $this->customer()]);
    }

    private function customer(): Customer
    {
        // Scoped so a tampered ticketId can never reveal another team's customer.
        $ticket = Ticket::query()->visibleTo(Auth::user())->with('customer')->findOrFail($this->ticketId);
        abort_unless($ticket->customer, 404);

        return $ticket->customer;
    }
}

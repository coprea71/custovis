<?php

namespace App\Livewire\Agent;

use App\Models\Customer;
use App\Models\Ticket;
use App\Services\CustomerAccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Offers creating a portal customer straight from the ticket sidebar when no
 * ERP connection can supply customer data.
 */
class TicketCustomerCreate extends Component
{
    #[Locked]
    public int $ticketId;

    public bool $open = false;

    public string $name = '';

    public string $email = '';

    public ?string $status = null;

    public function mount(): void
    {
        Gate::authorize('customers.manage');
    }

    public function openModal(): void
    {
        $ticket = $this->ticket();

        $this->name = (string) $ticket->requester_name;
        $this->email = (string) $ticket->requester_email;
        $this->resetValidation();
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    public function save(CustomerAccountService $customers): void
    {
        Gate::authorize('customers.manage');
        $ticket = $this->ticket();

        $this->email = Str::lower(trim($this->email));
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
        ]);

        $customers->create($data, Auth::user());
        $this->linkTicketIfUnassigned($ticket);

        $this->open = false;
        $this->status = 'Kunde angelegt und dem Ticket zugeordnet.';
    }

    public function render()
    {
        return view('livewire.agent.ticket-customer-create');
    }

    private function ticket(): Ticket
    {
        $ticket = Ticket::query()->findOrFail($this->ticketId);
        abort_unless(Auth::user()->teams()->whereKey($ticket->team_id)->exists(), 403);

        return $ticket;
    }

    // The agent may correct the address, so the mail-based linking alone could miss this ticket.
    private function linkTicketIfUnassigned(Ticket $ticket): void
    {
        if ($ticket->customer_id === null) {
            $ticket->update(['customer_id' => Customer::query()->where('email', $this->email)->value('id')]);
        }
    }
}

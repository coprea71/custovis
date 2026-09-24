<?php

namespace App\Livewire\Portal;

use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.portal')]
class TicketList extends Component
{
    use WithPagination;

    public function render()
    {
        // CustomerOwnedScope (registered by ScopeTicketsToCustomer) limits this to own tickets.
        return view('livewire.portal.ticket-list', [
            'tickets' => Ticket::query()->latest()->paginate(20),
        ]);
    }
}

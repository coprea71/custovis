<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Agents work on tickets of their own teams (or assigned to them);
     * only holders of tickets.view.all see every team (11.md).
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.view.all')
            || (int) $ticket->assigned_to === $user->id
            || $user->teams()->whereKey($ticket->team_id)->exists();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    /**
     * Portal-side checks (customer guard). Second line of defence next to
     * CustomerOwnedScope, which already hides foreign tickets from queries.
     */
    public function viewAsCustomer(Customer $customer, Ticket $ticket): bool
    {
        return (int) $ticket->customer_id === $customer->id
            || ($ticket->customer_id === null && $ticket->requester_email === $customer->email);
    }

    public function replyAsCustomer(Customer $customer, Ticket $ticket): bool
    {
        return $this->viewAsCustomer($customer, $ticket);
    }
}

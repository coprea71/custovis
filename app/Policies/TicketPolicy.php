<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Ticket;

/**
 * Portal-side checks (customer guard). Second line of defence next to
 * CustomerOwnedScope, which already hides foreign tickets from queries.
 */
class TicketPolicy
{
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

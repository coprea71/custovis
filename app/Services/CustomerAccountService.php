<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Str;

class CustomerAccountService
{
    /**
     * @param  array{name: string, email: string}  $data
     * @return int number of existing tickets linked to the new customer
     */
    public function create(array $data, User $actor): int
    {
        // Admins never set or know a customer password; access is granted
        // only via the invitation link.
        $customer = Customer::query()->create([...$data, 'password' => Str::password(64)]);
        $linked = $customer->linkUnassignedTickets();

        AuditLog::record('customer.created', $actor, null, $customer, ['tickets_linked' => $linked]);

        return $linked;
    }
}

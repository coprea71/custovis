<?php

namespace App\Http\Middleware;

use App\Models\Scopes\CustomerOwnedScope;
use App\Models\Ticket;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registered as Livewire persistent middleware as well, so the scope also
 * applies to /livewire/update requests of portal components.
 */
class ScopeTicketsToCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            Ticket::addGlobalScope('customer-owned', new CustomerOwnedScope($customer));
        }

        return $next($request);
    }
}

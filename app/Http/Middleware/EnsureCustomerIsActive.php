<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends portal sessions that were opened before an admin locked the account,
 * so a lock takes effect immediately and not only at the next login.
 * Registered as Livewire persistent middleware as well.
 */
class EnsureCustomerIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && ! $customer->active) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login')
                ->withErrors(['email' => 'Ihr Zugang zum Kundenportal ist gesperrt.']);
        }

        return $next($request);
    }
}

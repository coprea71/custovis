<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PortalLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalAuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function create(): View
    {
        return view('portal.login');
    }

    public function store(PortalLoginRequest $request): RedirectResponse
    {
        $key = 'portal-login|'.Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages(['email' => 'Zu viele Anmeldeversuche. Bitte in '.RateLimiter::availableIn($key).' Sekunden erneut versuchen.']);
        }

        if (! Auth::guard('customer')->attempt($request->only('email', 'password'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => 'Die Zugangsdaten sind ungültig.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('portal.tickets.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}

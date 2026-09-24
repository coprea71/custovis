<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PortalForgotPasswordRequest;
use App\Http\Requests\Portal\PortalResetPasswordRequest;
use App\Mail\CustomerPasswordLinkMail;
use App\Models\Customer;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PortalPasswordResetController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public const NEUTRAL_STATUS = 'Falls ein aktives Kundenkonto mit dieser E-Mail-Adresse existiert, haben wir einen Link zum Zurücksetzen des Passworts gesendet.';

    public function create(): View
    {
        return view('portal.forgot-password');
    }

    public function store(PortalForgotPasswordRequest $request): RedirectResponse
    {
        $key = 'portal-forgot|'.Str::transliterate(Str::lower($request->string('email'))).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages(['email' => 'Zu viele Anfragen. Bitte in '.RateLimiter::availableIn($key).' Sekunden erneut versuchen.']);
        }

        RateLimiter::hit($key);

        // Locked accounts are filtered by the credentials; the broker's result
        // (unknown user, throttled, sent) is ignored so the answer never reveals
        // whether an account exists.
        $this->broker()->sendResetLink(
            ['email' => $request->string('email')->toString(), 'active' => true],
            fn (Customer $customer, string $token) => Mail::to($customer)->send(new CustomerPasswordLinkMail($customer, $token)),
        );

        return back()->with('status', self::NEUTRAL_STATUS);
    }

    public function edit(Request $request, string $token): View
    {
        return view('portal.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(PortalResetPasswordRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token') + ['active' => true];

        $status = $this->broker()->reset($credentials, function (Customer $customer, string $password) {
            $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        });

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Der Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.']);
        }

        return redirect()->route('portal.login')->with('status', 'Ihr Passwort wurde gespeichert. Sie können sich jetzt anmelden.');
    }

    private function broker(): PasswordBroker
    {
        return Password::broker('customers');
    }
}

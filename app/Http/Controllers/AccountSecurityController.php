<?php

namespace App\Http\Controllers;

use App\Support\TwoFactorRequirement;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Self-service 2FA setup page. The state changes themselves go through
 * Fortify's password-confirmed endpoints (/user/two-factor-*).
 */
class AccountSecurityController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('account.security', [
            'user' => $user,
            'mandatory' => TwoFactorRequirement::mandatory(),
            'pendingConfirmation' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
        ]);
    }
}

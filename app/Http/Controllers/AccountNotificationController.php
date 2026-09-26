<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\UpdateNotificationSettingsRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Each user toggles their own mail notifications; the target is always the
 * authenticated user, never an id from the request.
 */
class AccountNotificationController extends Controller
{
    public function __invoke(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'notify_new_tickets' => $request->boolean('notify_new_tickets'),
        ])->save();

        return redirect()->route('account.security')->with('status', 'notifications-updated');
    }
}

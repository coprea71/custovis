<?php

namespace App\Actions\Fortify;

use App\Support\TwoFactorRequirement;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

/**
 * Bound in place of Fortify's action: while 2FA is mandatory the DELETE
 * /user/two-factor-authentication endpoint must not remove it.
 */
class PreventDisablingMandatoryTwoFactor extends DisableTwoFactorAuthentication
{
    public function __invoke($user)
    {
        abort_if(TwoFactorRequirement::mandatory(), 403, 'Zwei-Faktor-Authentifizierung ist verpflichtend.');

        parent::__invoke($user);
    }
}

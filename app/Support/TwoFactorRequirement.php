<?php

namespace App\Support;

/**
 * 2FA is mandatory for agents/admins in production and deliberately cannot
 * be switched off by an admin setting (11.md). Local/testing environments
 * or the explicit developer mode keep it optional.
 */
class TwoFactorRequirement
{
    public static function mandatory(): bool
    {
        return ! app()->environment('local', 'testing') && ! config('custovis.dev_mode');
    }
}

<?php

namespace App\Services\Administration;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * User lifecycle for /admin/users (19.md). Guards against locking the
 * installation out: the last active system admin can neither be
 * deactivated nor lose the system_admin role, and nobody deactivates
 * themselves.
 */
class UserAdministration
{
    /**
     * Admins never choose passwords for others: the new user gets a random
     * one and sets their own via the password-reset (invitation) link.
     *
     * @param  array{name: string, email: string, roles: array<int, string>}  $data
     */
    public function create(array $data, User $by): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::random(64)]);
            $user->syncRoles($data['roles']);

            return $user;
        });

        AuditLog::record('user.created', $by, null, $user, ['roles' => $data['roles']]);
        $this->sendInvitation($user, $by);

        return $user;
    }

    /**
     * @param  array{name: string, email: string, roles: array<int, string>}  $data
     */
    public function update(User $user, array $data, User $by): void
    {
        if (! in_array('system_admin', $data['roles'], true)) {
            $this->ensureNotLastAdmin($user, 'Die Rolle „system_admin“ kann dem letzten aktiven System-Admin nicht entzogen werden.');
        }

        DB::transaction(function () use ($user, $data) {
            $user->update(['name' => $data['name'], 'email' => $data['email']]);
            $user->syncRoles($data['roles']);
        });

        AuditLog::record('user.updated', $by, null, $user, ['roles' => $data['roles']]);
    }

    public function setActive(User $user, bool $active, User $by): void
    {
        if (! $active) {
            if ($user->is($by)) {
                throw ValidationException::withMessages(['user' => 'Das eigene Konto kann nicht deaktiviert werden.']);
            }
            $this->ensureNotLastAdmin($user, 'Der letzte aktive System-Admin kann nicht deaktiviert werden.');
        }

        $user->forceFill(['active' => $active])->save();

        if (! $active) {
            // Database session driver: end running sessions right away (the
            // EnsureUserIsActive middleware covers other drivers).
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->tokens()->delete();
        }

        AuditLog::record($active ? 'user.activated' : 'user.deactivated', $by, null, $user);
    }

    public function resetTwoFactor(User $user, User $by): void
    {
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();

        AuditLog::record('user.two_factor_reset', $by, null, $user);
    }

    public function sendInvitation(User $user, User $by): void
    {
        Password::broker('users')->sendResetLink(['email' => $user->email]);

        AuditLog::record('user.invitation_sent', $by, null, $user);
    }

    private function ensureNotLastAdmin(User $user, string $message): void
    {
        $otherActiveAdmins = User::role('system_admin')->where('active', true)->whereKeyNot($user->id)->count();

        if ($user->hasRole('system_admin') && $otherActiveAdmins === 0) {
            throw ValidationException::withMessages(['user' => $message]);
        }
    }
}

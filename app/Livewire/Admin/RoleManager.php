<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role/permission matrix (19.md). system_admin always holds every
 * permission (re-synced by the seeder) and is therefore read-only here.
 */
#[Layout('layouts.admin')]
class RoleManager extends Component
{
    public const PROTECTED_ROLE = 'system_admin';

    public string $newRole = '';

    public function mount(): void
    {
        Gate::authorize('roles.manage');
    }

    public function createRole(): void
    {
        Gate::authorize('roles.manage');
        $this->validate(['newRole' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:roles,name']],
            ['newRole.regex' => 'Nur Kleinbuchstaben, Ziffern und Unterstrich.']);

        $role = Role::create(['name' => $this->newRole, 'guard_name' => 'web']);
        AuditLog::record('role.created', Auth::user(), null, $role, ['name' => $role->name]);
        $this->newRole = '';
    }

    public function toggle(int $roleId, string $permission): void
    {
        Gate::authorize('roles.manage');
        $role = $this->editableRole($roleId);
        $permission = Permission::findByName($permission, 'web');

        $role->hasPermissionTo($permission) ? $role->revokePermissionTo($permission) : $role->givePermissionTo($permission);
        AuditLog::record('role.permission_toggled', Auth::user(), null, $role, ['permission' => $permission->name, 'granted' => $role->hasPermissionTo($permission)]);
    }

    public function deleteRole(int $roleId): void
    {
        Gate::authorize('roles.manage');
        $role = $this->editableRole($roleId);

        AuditLog::record('role.deleted', Auth::user(), null, null, ['name' => $role->name]);
        $role->delete();
    }

    public function render()
    {
        return view('livewire.admin.role-manager', [
            'roles' => Role::query()->where('guard_name', 'web')->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    private function editableRole(int $roleId): Role
    {
        $role = Role::query()->findOrFail($roleId);
        abort_if($role->name === self::PROTECTED_ROLE, 403);

        return $role;
    }
}

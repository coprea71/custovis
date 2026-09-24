<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Module;
use App\Models\User;
use App\Services\ModuleAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.admin')]
class ModuleManager extends Component
{
    public const DESCRIPTIONS = [
        'knowledge-base' => 'Wissensdatenbank für Agenten und Kundenportal',
        'team-chat' => 'Interner Chat mit Team-, Firmen- und Ticket-Kanälen',
        'field-service' => 'Techniker-Einsatzplanung und Techniker-App',
        'ai-agent' => 'KI-Funktionen mit eigenen API-Keys',
        'reporting' => 'Management- und Team-Dashboards',
        'change-management' => 'CAB-Freigaben für Changes',
        'cmdb' => 'Configuration Items und Beziehungen',
        'service-catalog' => 'Service-Katalog und Anfragen im Portal',
        'whatsapp' => 'WhatsApp-Business-Anbindung',
        'erp-integration' => 'Kundendaten aus Odoo/Shopware',
        'incident-management' => 'Incident-Management (Teil des Ticket-Kerns)',
        'problem-management' => 'Problem-Management (Teil des Ticket-Kerns)',
    ];

    public ?int $editingId = null;

    /** @var array<int, int|string> */
    public array $roleIds = [];

    /** @var array<int, int|string> */
    public array $userIds = [];

    public function mount(): void
    {
        Gate::authorize('modules.manage');
    }

    public function toggle(int $moduleId, ModuleAccess $access): void
    {
        Gate::authorize('modules.manage');
        $module = Module::query()->findOrFail($moduleId);
        $module->update(['enabled' => ! $module->enabled]);

        AuditLog::record($module->enabled ? 'module.enabled' : 'module.disabled', Auth::user(), null, $module, ['slug' => $module->slug]);
        $access->flush();
    }

    public function edit(int $moduleId): void
    {
        $module = Module::query()->with('roles', 'users')->findOrFail($moduleId);
        $this->editingId = $module->id;
        $this->roleIds = $module->roles->pluck('id')->all();
        $this->userIds = $module->users->pluck('id')->all();
    }

    public function saveAssignments(ModuleAccess $access): void
    {
        Gate::authorize('modules.manage');
        $this->validate([
            'roleIds.*' => [Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'userIds.*' => ['integer', 'exists:users,id'],
        ]);

        $module = Module::query()->findOrFail($this->editingId);
        $module->roles()->sync($this->roleIds);
        $module->users()->sync($this->userIds);

        AuditLog::record('module.assignments_changed', Auth::user(), null, $module, ['role_ids' => array_map('intval', $this->roleIds), 'user_ids' => array_map('intval', $this->userIds)]);
        $access->flush();
        $this->reset(['editingId', 'roleIds', 'userIds']);
    }

    public function render()
    {
        return view('livewire.admin.module-manager', [
            'modules' => Module::query()->with('roles', 'users')->orderBy('name')->get(),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'users' => User::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }
}

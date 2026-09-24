<div class="space-y-6">
    <h1 class="text-xl font-semibold text-slatecalm-900">Rollen &amp; Berechtigungen</h1>

    <div class="flex gap-2">
        <input type="text" wire:model="newRole" placeholder="Neue Rolle, z. B. dispatcher" aria-label="Name der neuen Rolle" class="flex-1 border border-slatecalm-200 rounded-xl px-3 py-2 text-sm">
        <button type="button" wire:click="createRole" class="px-4 py-2 bg-calm-600 hover:bg-calm-700 text-white rounded-xl text-sm font-medium">Anlegen</button>
    </div>
    @error('newRole') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="bg-white border border-slatecalm-200 rounded-2xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slatecalm-200 text-left">
                    <th class="p-3 font-medium text-slate-600">Berechtigung</th>
                    @foreach ($roles as $role)
                        <th class="p-3 font-medium text-slate-700 whitespace-nowrap">
                            {{ $role->name }} <span class="text-xs text-slate-400">({{ $role->users_count }})</span>
                            @if ($role->name !== \App\Livewire\Admin\RoleManager::PROTECTED_ROLE)
                                <button type="button" wire:click="deleteRole({{ $role->id }})" wire:confirm="Rolle „{{ $role->name }}“ löschen? Nutzer verlieren die zugehörigen Rechte." class="ml-1 text-xs text-red-600" aria-label="Rolle löschen">×</button>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slatecalm-100">
                @foreach ($permissions as $permission)
                    <tr wire:key="p-{{ $permission }}">
                        <td class="p-3 font-mono text-xs text-slate-600">{{ $permission }}</td>
                        @foreach ($roles as $role)
                            <td class="p-3 text-center">
                                <input type="checkbox" aria-label="{{ $role->name }}: {{ $permission }}"
                                       @checked($role->permissions->contains('name', $permission))
                                       @disabled($role->name === \App\Livewire\Admin\RoleManager::PROTECTED_ROLE)
                                       wire:click="toggle({{ $role->id }}, '{{ $permission }}')">
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-400">Die Rolle „system_admin“ besitzt immer alle Berechtigungen und ist nicht änderbar.</p>
</div>

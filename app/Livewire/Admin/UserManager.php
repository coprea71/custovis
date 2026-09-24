<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\Administration\UserAdministration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.admin')]
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    /** @var array{name: string, email: string, roles: array<int, string>} */
    public array $form = ['name' => '', 'email' => '', 'roles' => []];

    public ?string $status = null;

    public function mount(): void
    {
        Gate::authorize('users.manage');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $this->editingId = $user->id;
        $this->form = ['name' => $user->name, 'email' => $user->email, 'roles' => $user->getRoleNames()->all()];
    }

    public function newUser(): void
    {
        $this->reset(['editingId', 'form']);
    }

    public function save(UserAdministration $users): void
    {
        Gate::authorize('users.manage');
        $data = $this->validate($this->rules())['form'];

        $this->attempt(function () use ($users, $data) {
            if ($this->editingId) {
                $users->update(User::query()->findOrFail($this->editingId), $data, Auth::user());
                $this->status = 'Nutzer gespeichert.';
            } else {
                $users->create($data, Auth::user());
                $this->status = 'Nutzer angelegt, Einladung zum Setzen des Passworts wurde versendet.';
                $this->reset(['form']);
            }
        });
    }

    public function toggleActive(int $userId, UserAdministration $users): void
    {
        Gate::authorize('users.manage');
        $user = User::query()->findOrFail($userId);

        $this->attempt(fn () => $users->setActive($user, ! $user->active, Auth::user()));
    }

    public function invite(int $userId, UserAdministration $users): void
    {
        Gate::authorize('users.manage');
        $users->sendInvitation(User::query()->findOrFail($userId), Auth::user());
        $this->status = 'Link zum Setzen des Passworts wurde versendet.';
    }

    public function resetTwoFactor(int $userId, UserAdministration $users): void
    {
        Gate::authorize('users.manage');
        $users->resetTwoFactor(User::query()->findOrFail($userId), Auth::user());
        $this->status = 'Zwei-Faktor-Authentifizierung zurückgesetzt – der Nutzer richtet sie beim nächsten Login neu ein.';
    }

    public function render()
    {
        return view('livewire.admin.user-manager', [
            'users' => User::query()->with('roles', 'teams')
                ->when($this->search !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', '%'.addcslashes($this->search, '%_\\').'%')
                    ->orWhere('email', 'like', '%'.addcslashes($this->search, '%_\\').'%')))
                ->orderBy('name')->paginate(25),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'form.roles' => ['array'],
            'form.roles.*' => [Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    private function attempt(callable $action): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            $this->addError('user', collect($e->errors())->flatten()->first());
        }
    }
}

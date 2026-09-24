<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Skill;
use App\Models\TechnicianAbsence;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Services\FieldService\RoutingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TechnicianManager extends Component
{
    public ?int $selectedId = null;

    public ?int $newUserId = null;

    public string $newSkill = '';

    /** @var array<string, mixed> */
    public array $profile = ['home_address' => '', 'active' => true, 'location_tracking_consent' => false];

    /** @var array<string, mixed> */
    public array $skillForm = ['skill_id' => '', 'level' => 3];

    /** @var array<string, mixed> */
    public array $shiftForm = ['weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '16:00'];

    /** @var array<string, mixed> */
    public array $absenceForm = ['starts_on' => '', 'ends_on' => '', 'reason' => 'vacation'];

    public function mount(): void
    {
        Gate::authorize('technicians.manage');
    }

    public function createProfile(): void
    {
        Gate::authorize('technicians.manage');
        $this->validate(['newUserId' => ['required', 'integer', 'exists:users,id', 'unique:technician_profiles,user_id']]);

        $profile = TechnicianProfile::query()->create(['user_id' => $this->newUserId]);
        AuditLog::record('technician.created', Auth::user(), null, $profile);

        $this->newUserId = null;
        $this->select($profile->id);
    }

    public function select(int $profileId): void
    {
        $profile = TechnicianProfile::query()->findOrFail($profileId);
        $this->selectedId = $profile->id;
        $this->profile = [
            'home_address' => (string) $profile->home_address,
            'active' => $profile->active,
            'location_tracking_consent' => $profile->location_tracking_consent,
        ];
    }

    public function saveProfile(RoutingService $routing): void
    {
        Gate::authorize('technicians.manage');
        $data = $this->validate([
            'profile.home_address' => ['nullable', 'string', 'max:255'],
            'profile.active' => ['boolean'],
            'profile.location_tracking_consent' => ['boolean'],
        ])['profile'];

        $coordinates = $data['home_address'] ? $routing->geocode($data['home_address']) : null;
        $this->selected()->update([
            ...$data,
            'home_lat' => $coordinates['lat'] ?? null,
            'home_lng' => $coordinates['lng'] ?? null,
        ]);
    }

    public function createSkill(): void
    {
        Gate::authorize('technicians.manage');
        $this->validate(['newSkill' => ['required', 'string', 'max:100', 'unique:skills,name']]);
        Skill::query()->create(['name' => $this->newSkill]);
        $this->newSkill = '';
    }

    public function addSkill(): void
    {
        Gate::authorize('technicians.manage');
        $data = $this->validate([
            'skillForm.skill_id' => ['required', 'integer', 'exists:skills,id'],
            'skillForm.level' => ['required', 'integer', 'between:1,5'],
        ])['skillForm'];

        $this->selected()->skills()->syncWithoutDetaching([$data['skill_id'] => ['level' => $data['level']]]);
    }

    public function removeSkill(int $skillId): void
    {
        Gate::authorize('technicians.manage');
        $this->selected()->skills()->detach($skillId);
    }

    public function addShift(): void
    {
        Gate::authorize('technicians.manage');
        $data = $this->validate([
            'shiftForm.weekday' => ['required', 'integer', 'between:1,7'],
            'shiftForm.starts_at' => ['required', 'date_format:H:i'],
            'shiftForm.ends_at' => ['required', 'date_format:H:i', 'after:shiftForm.starts_at'],
        ])['shiftForm'];

        $this->selected()->shifts()->create([
            'weekday' => $data['weekday'],
            'starts_at' => $data['starts_at'].':00',
            'ends_at' => $data['ends_at'].':00',
        ]);
    }

    public function removeShift(int $shiftId): void
    {
        Gate::authorize('technicians.manage');
        $this->selected()->shifts()->whereKey($shiftId)->delete();
    }

    public function addAbsence(): void
    {
        Gate::authorize('technicians.manage');
        $data = $this->validate([
            'absenceForm.starts_on' => ['required', 'date'],
            'absenceForm.ends_on' => ['required', 'date', 'after_or_equal:absenceForm.starts_on'],
            'absenceForm.reason' => ['required', Rule::in(array_keys(TechnicianAbsence::REASONS))],
        ])['absenceForm'];

        $this->selected()->absences()->create($data);
        $this->reset('absenceForm');
    }

    public function removeAbsence(int $absenceId): void
    {
        Gate::authorize('technicians.manage');
        $this->selected()->absences()->whereKey($absenceId)->delete();
    }

    public function render()
    {
        return view('livewire.admin.technician-manager', [
            'technicians' => TechnicianProfile::query()->with('user')->get()->sortBy('user.name'),
            'selected' => $this->selectedId ? $this->selected()->load('skills', 'shifts', 'absences') : null,
            'candidates' => User::query()->whereDoesntHave('technicianProfile')->orderBy('name')->get(),
            'skills' => Skill::query()->orderBy('name')->get(),
        ]);
    }

    private function selected(): TechnicianProfile
    {
        return TechnicianProfile::query()->findOrFail($this->selectedId);
    }
}

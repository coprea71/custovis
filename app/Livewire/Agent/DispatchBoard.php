<?php

namespace App\Livewire\Agent;

use App\Models\AppointmentChecklist;
use App\Models\ServiceAppointment;
use App\Models\Skill;
use App\Models\TechnicianProfile;
use App\Models\Ticket;
use App\Services\FieldService\AppointmentService;
use App\Services\FieldService\DispatchAssignmentService;
use App\States\Appointment\Cancelled;
use App\States\Appointment\Proposed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.agent')]
class DispatchBoard extends Component
{
    #[Url]
    public string $date = '';

    public ?int $suggestFor = null;

    /** @var array<string, mixed> */
    public array $form = [
        'ticket_id' => '', 'kind' => 'service', 'address' => '', 'start_time' => '09:00',
        'duration_minutes' => 60, 'required_skill_ids' => [], 'notes' => '', 'checklist_template_id' => '',
    ];

    public function mount(): void
    {
        Gate::authorize('dispatch.manage');
        $this->date = $this->date ?: now()->toDateString();
    }

    public function createAppointment(AppointmentService $appointments): void
    {
        Gate::authorize('dispatch.manage');
        $data = $this->validate($this->rules())['form'];
        $start = Carbon::parse("{$this->date} {$data['start_time']}");

        $appointments->create(Ticket::query()->findOrFail($data['ticket_id']), [
            'kind' => $data['kind'],
            'address' => $data['address'],
            'scheduled_start' => $start,
            'scheduled_end' => $start->copy()->addMinutes((int) $data['duration_minutes']),
            'required_skill_ids' => array_map('intval', $data['required_skill_ids'] ?? []),
            'notes' => $data['notes'] ?: null,
            'checklist_template_id' => $data['checklist_template_id'] ?: null,
        ], Auth::user());

        $this->reset('form');
    }

    public function assign(int $appointmentId, int $technicianId, AppointmentService $appointments): void
    {
        Gate::authorize('dispatch.manage');

        $error = $appointments->assign(
            ServiceAppointment::query()->findOrFail($appointmentId),
            TechnicianProfile::query()->findOrFail($technicianId),
            Auth::user()
        );

        $error ? $this->addError('board', $error) : $this->suggestFor = null;
    }

    public function unassign(int $appointmentId): void
    {
        Gate::authorize('dispatch.manage');
        $appointment = ServiceAppointment::query()->findOrFail($appointmentId);

        if ($appointment->state->canTransitionTo(Proposed::class)) {
            $appointment->state->transitionTo(Proposed::class);
            $appointment->update(['technician_profile_id' => null]);
        }
    }

    public function cancel(int $appointmentId): void
    {
        Gate::authorize('dispatch.manage');
        $appointment = ServiceAppointment::query()->findOrFail($appointmentId);

        if ($appointment->state->canTransitionTo(Cancelled::class)) {
            $appointment->state->transitionTo(Cancelled::class);
        }
    }

    public function render(DispatchAssignmentService $dispatch)
    {
        $appointments = ServiceAppointment::query()->with('ticket', 'technician')
            ->whereDate('scheduled_start', $this->date)->whereNotState('state', Cancelled::class)
            ->orderBy('scheduled_start')->get();
        $technicians = TechnicianProfile::query()->with('user', 'latestLocation')->where('active', true)->get();
        $suggested = $this->suggestFor ? $appointments->firstWhere('id', $this->suggestFor) : null;

        return view('livewire.agent.dispatch-board', [
            'unassigned' => $appointments->whereNull('technician_profile_id'),
            'byTechnician' => $appointments->whereNotNull('technician_profile_id')->groupBy('technician_profile_id'),
            'technicians' => $technicians,
            'suggestions' => $suggested ? $dispatch->rank($suggested) : collect(),
            'skills' => Skill::query()->orderBy('name')->get(),
            'templates' => AppointmentChecklist::query()->templates()->orderBy('name')->get(),
            'mapPoints' => $this->mapPoints($appointments, $technicians),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'form.ticket_id' => ['required', 'integer', 'exists:tickets,id'],
            'form.kind' => ['required', Rule::in(ServiceAppointment::KINDS)],
            'form.address' => ['required', 'string', 'max:255'],
            'form.start_time' => ['required', 'date_format:H:i'],
            'form.duration_minutes' => ['required', 'integer', 'min:15', 'max:720'],
            'form.required_skill_ids' => ['array'],
            'form.required_skill_ids.*' => ['integer', 'exists:skills,id'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'form.checklist_template_id' => ['nullable', Rule::exists('appointment_checklists', 'id')->whereNull('appointment_id')],
        ];
    }

    /**
     * @return array<int, array{type: string, lat: float, lng: float, label: string}>
     */
    private function mapPoints($appointments, $technicians): array
    {
        $jobs = $appointments->filter(fn ($a) => $a->lat !== null)
            ->map(fn ($a) => ['type' => 'appointment', 'lat' => $a->lat, 'lng' => $a->lng, 'label' => "#{$a->ticket_id} {$a->scheduled_start->format('H:i')} {$a->address}"]);
        $people = $technicians->filter(fn ($t) => $t->latestLocation !== null)
            ->map(fn ($t) => ['type' => 'technician', 'lat' => $t->latestLocation->lat, 'lng' => $t->latestLocation->lng, 'label' => $t->user->name.' ('.$t->latestLocation->recorded_at->format('H:i').')']);

        return $jobs->concat($people)->values()->all();
    }
}

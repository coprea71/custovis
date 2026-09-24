<?php

namespace App\Services\FieldService;

use App\Models\AppointmentChecklist;
use App\Models\AuditLog;
use App\Models\ServiceAppointment;
use App\Models\TechnicianProfile;
use App\Models\Ticket;
use App\Models\User;
use App\States\Appointment\Proposed;
use App\States\Appointment\Scheduled;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private readonly RoutingService $routing,
        private readonly TechnicianAvailability $availability,
    ) {}

    /**
     * @param  array{kind: string, scheduled_start: string, scheduled_end: string, address: string, required_skill_ids?: array<int, int>, notes?: string|null, checklist_template_id?: int|null}  $data
     */
    public function create(Ticket $ticket, array $data, User $by): ServiceAppointment
    {
        return DB::transaction(function () use ($ticket, $data, $by) {
            $appointment = $ticket->appointments()->create([
                ...collect($data)->except('checklist_template_id')->all(),
                ...($this->routing->geocode($data['address']) ?? []),
                'state' => Proposed::class,
            ]);

            if (! empty($data['checklist_template_id'])) {
                $this->copyChecklist($appointment, AppointmentChecklist::query()->templates()->findOrFail($data['checklist_template_id']));
            }

            AuditLog::record('appointment.created', $by, $ticket->team, $appointment, ['ticket_id' => $ticket->id, 'kind' => $appointment->kind]);

            return $appointment;
        });
    }

    /**
     * @return string|null error message, null on success
     */
    public function assign(ServiceAppointment $appointment, TechnicianProfile $technician, User $by): ?string
    {
        $blocker = $this->availability->blocker($technician, $appointment->scheduled_start, $appointment->scheduled_end, $appointment->id);

        if ($blocker !== null) {
            return "Techniker ist {$blocker}.";
        }

        if (! $appointment->state->equals(Scheduled::class) && ! $appointment->state->canTransitionTo(Scheduled::class)) {
            return 'Termin kann in diesem Status nicht (um)geplant werden.';
        }

        DB::transaction(function () use ($appointment, $technician) {
            $appointment->update(['technician_profile_id' => $technician->id]);

            if (! $appointment->state->equals(Scheduled::class)) {
                $appointment->state->transitionTo(Scheduled::class);
            }
        });

        AuditLog::record('appointment.assigned', $by, $appointment->ticket->team, $appointment, ['technician_profile_id' => $technician->id]);

        return null;
    }

    public function copyChecklist(ServiceAppointment $appointment, AppointmentChecklist $template): AppointmentChecklist
    {
        $copy = $appointment->checklists()->create(['name' => $template->name]);

        foreach ($template->items as $item) {
            $copy->items()->create(['label' => $item->label, 'position' => $item->position]);
        }

        return $copy;
    }
}

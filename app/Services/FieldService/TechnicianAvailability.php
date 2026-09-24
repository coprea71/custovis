<?php

namespace App\Services\FieldService;

use App\Models\ServiceAppointment;
use App\Models\TechnicianProfile;
use App\States\Appointment\Cancelled;
use App\States\Appointment\Completed;
use Illuminate\Support\Carbon;

class TechnicianAvailability
{
    /**
     * @return string|null reason why the technician is NOT available, null if available
     */
    public function blocker(TechnicianProfile $technician, Carbon $start, Carbon $end, ?int $ignoreAppointmentId = null): ?string
    {
        return match (true) {
            ! $technician->active => 'inaktiv',
            ! $this->withinShift($technician, $start, $end) => 'außerhalb der Schicht',
            $this->absent($technician, $start, $end) => 'abwesend',
            $this->hasOverlap($technician, $start, $end, $ignoreAppointmentId) => 'bereits verplant',
            default => null,
        };
    }

    private function withinShift(TechnicianProfile $technician, Carbon $start, Carbon $end): bool
    {
        return $technician->shifts
            ->where('weekday', $start->isoWeekday())
            ->contains(fn ($shift) => $start->format('H:i:s') >= $shift->starts_at && $end->format('H:i:s') <= $shift->ends_at);
    }

    private function absent(TechnicianProfile $technician, Carbon $start, Carbon $end): bool
    {
        return $technician->absences()
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->exists();
    }

    private function hasOverlap(TechnicianProfile $technician, Carbon $start, Carbon $end, ?int $ignoreAppointmentId): bool
    {
        return ServiceAppointment::query()
            ->where('technician_profile_id', $technician->id)
            ->when($ignoreAppointmentId, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->whereNotState('state', [Cancelled::class, Completed::class])
            ->where('scheduled_start', '<', $end)
            ->where('scheduled_end', '>', $start)
            ->exists();
    }
}

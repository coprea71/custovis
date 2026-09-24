<?php

namespace App\Services\FieldService;

use App\Models\ServiceAppointment;
use App\Models\TechnicianProfile;
use Illuminate\Support\Collection;

/**
 * Rule-based technician ranking for the dispatcher board (14.md). Returns
 * a suggestion only — the dispatcher always confirms the assignment.
 *
 * Score (0..100): skill match 50, same team as the ticket 20, distance 30
 * (full points up to 10 km, zero from 100 km). Unavailable technicians are
 * listed last with the blocking reason instead of a score.
 */
class DispatchAssignmentService
{
    private const SKILL_POINTS = 50;

    private const TEAM_POINTS = 20;

    private const DISTANCE_POINTS = 30;

    public function __construct(
        private readonly RoutingService $routing,
        private readonly TechnicianAvailability $availability,
    ) {}

    /**
     * @return Collection<int, array{technician: TechnicianProfile, score: int|null, blocker: string|null, distance_km: float|null, sla_risk: bool}>
     */
    public function rank(ServiceAppointment $appointment): Collection
    {
        $slaRisk = $appointment->ticket->sla_resolution_due_at?->lt($appointment->scheduled_end) ?? false;

        return TechnicianProfile::query()
            ->with(['user.teams', 'skills', 'shifts'])
            ->get()
            ->map(fn (TechnicianProfile $technician) => $this->evaluate($appointment, $technician) + ['sla_risk' => $slaRisk])
            ->sortBy([['blocker', 'asc'], ['score', 'desc']])
            ->values();
    }

    /**
     * @return array{technician: TechnicianProfile, score: int|null, blocker: string|null, distance_km: float|null}
     */
    private function evaluate(ServiceAppointment $appointment, TechnicianProfile $technician): array
    {
        $blocker = $this->availability->blocker($technician, $appointment->scheduled_start, $appointment->scheduled_end, $appointment->id);
        $distanceKm = $this->distanceKm($appointment, $technician);

        $score = $blocker ? null : (int) round(
            $this->skillScore($appointment, $technician)
            + ($technician->user->teams->contains('id', $appointment->ticket->team_id) ? self::TEAM_POINTS : 0)
            + $this->distanceScore($distanceKm)
        );

        return ['technician' => $technician, 'score' => $score, 'blocker' => $blocker, 'distance_km' => $distanceKm];
    }

    private function skillScore(ServiceAppointment $appointment, TechnicianProfile $technician): float
    {
        $required = collect($appointment->required_skill_ids ?? [])->map(fn ($id) => (int) $id);

        if ($required->isEmpty()) {
            return self::SKILL_POINTS;
        }

        $matched = $required->intersect($technician->skills->pluck('id'))->count();

        return self::SKILL_POINTS * $matched / $required->count();
    }

    private function distanceScore(?float $distanceKm): float
    {
        if ($distanceKm === null) {
            return self::DISTANCE_POINTS / 2; // unknown location: neutral
        }

        return self::DISTANCE_POINTS * max(0, min(1, (100 - $distanceKm) / 90));
    }

    private function distanceKm(ServiceAppointment $appointment, TechnicianProfile $technician): ?float
    {
        if ($appointment->lat === null || $technician->home_lat === null) {
            return null;
        }

        $route = $this->routing->distance($technician->home_lat, $technician->home_lng, $appointment->lat, $appointment->lng);

        return round($route['meters'] / 1000, 1);
    }
}

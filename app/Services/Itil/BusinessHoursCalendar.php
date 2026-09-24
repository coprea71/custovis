<?php

namespace App\Services\Itil;

use App\Models\BusinessHour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Adds SLA minutes in a team's business hours (21.md): Friday 17:00 + 2 h
 * with Mo–Fr 8–18 ends on Monday 09:00. Teams without business hours keep
 * plain calendar time, so existing installations behave as before.
 */
class BusinessHoursCalendar
{
    private const MAX_DAYS = 366; // guards against endless loops on odd data

    public function addMinutes(int $teamId, Carbon $start, int $minutes): Carbon
    {
        $hours = BusinessHour::query()->where('team_id', $teamId)->get()->keyBy('day_of_week');

        return $hours->isEmpty() ? $start->copy()->addMinutes($minutes) : $this->walk($hours, $start->copy(), $minutes);
    }

    /**
     * @param  Collection<int, BusinessHour>  $hours
     */
    private function walk(Collection $hours, Carbon $cursor, int $remaining): Carbon
    {
        for ($day = 0; $day < self::MAX_DAYS; $day++) {
            $window = $hours->get($cursor->dayOfWeek);

            if ($window) {
                $open = $cursor->copy()->setTimeFromTimeString($window->start_time);
                $close = $cursor->copy()->setTimeFromTimeString($window->end_time);
                $from = $cursor->greaterThan($open) ? $cursor->copy() : $open;
                $available = $from->lessThan($close) ? (int) $from->diffInMinutes($close) : 0;

                if ($remaining <= $available) {
                    return $from->addMinutes($remaining);
                }

                $remaining -= $available;
            }

            $cursor = $cursor->copy()->addDay()->startOfDay();
        }

        return $cursor;
    }
}

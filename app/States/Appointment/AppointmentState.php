<?php

namespace App\States\Appointment;

use Illuminate\Support\Str;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class AppointmentState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Proposed::class)
            ->allowTransition(Proposed::class, Scheduled::class)
            ->allowTransition(Scheduled::class, Proposed::class) // dispatcher unassigns
            ->allowTransition(Scheduled::class, EnRoute::class)
            ->allowTransition(Scheduled::class, OnSite::class)
            ->allowTransition(EnRoute::class, OnSite::class)
            ->allowTransition(OnSite::class, Completed::class)
            ->allowTransition([Proposed::class, Scheduled::class, EnRoute::class, OnSite::class], Cancelled::class);
    }

    /**
     * Stable short key used by the field PWA / sync payloads instead of class names.
     */
    public static function keyOf(string $stateClass): string
    {
        return Str::snake(class_basename($stateClass));
    }

    /**
     * @return array<string, class-string<AppointmentState>>
     */
    public static function byKey(): array
    {
        return collect([Proposed::class, Scheduled::class, EnRoute::class, OnSite::class, Completed::class, Cancelled::class])
            ->mapWithKeys(fn (string $class) => [self::keyOf($class) => $class])
            ->all();
    }
}

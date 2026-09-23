<?php

namespace App\States\Incident;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class IncidentState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(New_::class)
            ->allowTransition(New_::class, InProgress::class)
            ->allowTransition(InProgress::class, Resolved::class)
            ->allowTransition(Resolved::class, Closed::class)
            ->allowTransition(Resolved::class, InProgress::class); // reopen
    }
}

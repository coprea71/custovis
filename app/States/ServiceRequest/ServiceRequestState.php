<?php

namespace App\States\ServiceRequest;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ServiceRequestState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(New_::class)
            ->allowTransition(New_::class, Approved::class)
            ->allowTransition(Approved::class, InProgress::class)
            ->allowTransition(InProgress::class, Fulfilled::class)
            ->allowTransition(Fulfilled::class, Closed::class);
    }
}

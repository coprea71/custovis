<?php

namespace App\States\Change;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ChangeState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, CabReview::class)
            ->allowTransition(CabReview::class, Approved::class)
            ->allowTransition(CabReview::class, Rejected::class)
            ->allowTransition(Approved::class, Implementing::class)
            ->allowTransition(Implementing::class, Closed::class);
    }
}

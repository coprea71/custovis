<?php

namespace App\States\Problem;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ProblemState extends State
{
    abstract public function label(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(New_::class)
            ->allowTransition(New_::class, Investigating::class)
            ->allowTransition(Investigating::class, KnownError::class)
            ->allowTransition(Investigating::class, Resolved::class)
            ->allowTransition(KnownError::class, Resolved::class)
            ->allowTransition(Resolved::class, Closed::class);
    }
}

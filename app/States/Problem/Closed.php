<?php

namespace App\States\Problem;

class Closed extends ProblemState
{
    public function label(): string
    {
        return 'Geschlossen';
    }
}

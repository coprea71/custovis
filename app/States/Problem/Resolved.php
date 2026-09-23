<?php

namespace App\States\Problem;

class Resolved extends ProblemState
{
    public function label(): string
    {
        return 'Gelöst';
    }
}

<?php

namespace App\States\Problem;

class Investigating extends ProblemState
{
    public function label(): string
    {
        return 'Wird untersucht';
    }
}

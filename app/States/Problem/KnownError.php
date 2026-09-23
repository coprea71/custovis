<?php

namespace App\States\Problem;

class KnownError extends ProblemState
{
    public function label(): string
    {
        return 'Bekannter Fehler';
    }
}

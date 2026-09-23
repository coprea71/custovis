<?php

namespace App\States\Change;

class Closed extends ChangeState
{
    public function label(): string
    {
        return 'Geschlossen';
    }
}

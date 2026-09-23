<?php

namespace App\States\Change;

class Draft extends ChangeState
{
    public function label(): string
    {
        return 'Entwurf';
    }
}

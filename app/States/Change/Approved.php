<?php

namespace App\States\Change;

class Approved extends ChangeState
{
    public function label(): string
    {
        return 'Freigegeben';
    }
}

<?php

namespace App\States\Change;

class Rejected extends ChangeState
{
    public function label(): string
    {
        return 'Abgelehnt';
    }
}

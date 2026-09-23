<?php

namespace App\States\ServiceRequest;

class Closed extends ServiceRequestState
{
    public function label(): string
    {
        return 'Geschlossen';
    }
}

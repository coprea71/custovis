<?php

namespace App\States\Incident;

class Closed extends IncidentState
{
    public function label(): string
    {
        return 'Geschlossen';
    }
}

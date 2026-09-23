<?php

namespace App\States\Incident;

class Resolved extends IncidentState
{
    public function label(): string
    {
        return 'Gelöst';
    }
}

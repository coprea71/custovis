<?php

namespace App\States\Incident;

class InProgress extends IncidentState
{
    public function label(): string
    {
        return 'In Bearbeitung';
    }
}

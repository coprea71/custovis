<?php

namespace App\States\ServiceRequest;

class InProgress extends ServiceRequestState
{
    public function label(): string
    {
        return 'In Bearbeitung';
    }
}

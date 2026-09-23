<?php

namespace App\States\ServiceRequest;

class Approved extends ServiceRequestState
{
    public function label(): string
    {
        return 'Freigegeben';
    }
}

<?php

namespace App\States\ServiceRequest;

class Fulfilled extends ServiceRequestState
{
    public function label(): string
    {
        return 'Erfüllt';
    }
}

<?php

namespace App\States\Appointment;

class Proposed extends AppointmentState
{
    public function label(): string
    {
        return 'Vorgeschlagen';
    }
}

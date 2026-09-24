<?php

namespace App\States\Appointment;

class Cancelled extends AppointmentState
{
    public function label(): string
    {
        return 'Storniert';
    }
}

<?php

namespace App\States\Appointment;

class Scheduled extends AppointmentState
{
    public function label(): string
    {
        return 'Geplant';
    }
}

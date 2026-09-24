<?php

namespace App\States\Appointment;

class Completed extends AppointmentState
{
    public function label(): string
    {
        return 'Abgeschlossen';
    }
}

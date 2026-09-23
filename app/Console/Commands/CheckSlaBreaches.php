<?php

namespace App\Console\Commands;

use App\Events\SlaBreached;
use App\Models\Ticket;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    protected $signature = 'sla:check-breaches';

    protected $description = 'Marks tickets whose SLA resolution deadline has passed and fires an escalation event.';

    public function handle(): void
    {
        Ticket::query()
            ->whereNotNull('sla_resolution_due_at')
            ->whereNull('sla_breached_at')
            ->where('sla_resolution_due_at', '<', now())
            ->whereNotIn('status', ['closed'])
            ->each(function (Ticket $ticket) {
                $ticket->update(['sla_breached_at' => now()]);
                SlaBreached::dispatch($ticket);
                $this->info("Ticket #{$ticket->id}: SLA-Verletzung markiert.");
            });
    }
}

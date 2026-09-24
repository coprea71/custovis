<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\Ticket;
use App\Services\Compliance\GdprService;
use Illuminate\Console\Command;

/**
 * Configurable retention periods (11.md), all opt-in (0 = keep forever):
 * audit log entries are deleted, closed tickets are anonymised (kept for
 * statistics). Chat messages have their own chat:prune command.
 */
class ApplyRetentionPolicies extends Command
{
    public const AUDIT_DAYS_KEY = 'retention.audit_log_days';

    public const CLOSED_TICKET_DAYS_KEY = 'retention.closed_ticket_days';

    protected $signature = 'retention:apply';

    protected $description = 'Apply configured retention periods to audit logs and closed tickets';

    public function handle(GdprService $gdpr): int
    {
        $auditDays = (int) Setting::read(self::AUDIT_DAYS_KEY, '0');
        $ticketDays = (int) Setting::read(self::CLOSED_TICKET_DAYS_KEY, '0');

        $deletedLogs = $auditDays > 0 ? AuditLog::query()->where('created_at', '<', now()->subDays($auditDays))->delete() : 0;
        $anonymised = 0;

        if ($ticketDays > 0) {
            Ticket::query()->where('status', 'closed')->where('closed_at', '<', now()->subDays($ticketDays))
                ->where(fn ($query) => $query->whereNull('requester_name')->orWhere('requester_name', '!=', GdprService::PLACEHOLDER))
                ->each(function (Ticket $ticket) use ($gdpr, &$anonymised) {
                    $gdpr->anonymizeTicket($ticket);
                    $anonymised++;
                });
        }

        $this->info("{$deletedLogs} audit log(s) deleted, {$anonymised} closed ticket(s) anonymised.");

        return self::SUCCESS;
    }
}

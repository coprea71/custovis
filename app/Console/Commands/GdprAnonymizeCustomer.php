<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Compliance\GdprService;
use Illuminate\Console\Command;

class GdprAnonymizeCustomer extends Command
{
    protected $signature = 'gdpr:anonymize-customer
        {identifier : Kunden-ID, E-Mail-Adresse oder Telefonnummer}
        {--actor= : ID des auslösenden Admins (für das Audit-Log)}';

    protected $description = 'Anonymise a data subject in customers, tickets and messages (Art. 17 DSGVO)';

    public function handle(GdprService $gdpr): int
    {
        $actor = $this->option('actor') ? User::query()->find((int) $this->option('actor')) : null;
        $count = $gdpr->anonymize($gdpr->subject($this->argument('identifier')), $actor);

        $this->info("{$count} ticket(s) anonymised.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Compliance\GdprService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Triggered from /admin/compliance (no shell on the target servers); the
 * export file is streamed to the admin and deleted right afterwards.
 */
class GdprExportCustomerData extends Command
{
    public const DIRECTORY = 'gdpr-exports';

    protected $signature = 'gdpr:export-customer-data {identifier : Kunden-ID, E-Mail-Adresse oder Telefonnummer}';

    protected $description = 'Export all personal data of a data subject as JSON (Art. 15 DSGVO)';

    public function handle(GdprService $gdpr): int
    {
        $subject = $gdpr->subject($this->argument('identifier'));
        $path = self::DIRECTORY.'/'.Str::uuid().'.json';

        Storage::disk('local')->put($path, json_encode($gdpr->export($subject), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->line($path);

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Installation\InstallationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Installation for hosts with shell access; expects a configured .env
 * (DB credentials, APP_KEY). FTP-only hosts use the web installer.
 */
class InstallCustovis extends Command
{
    protected $signature = 'custovis:install
        {--admin-name= : Name des ersten Administrators}
        {--admin-email= : E-Mail des ersten Administrators}
        {--admin-password= : Passwort (ohne Angabe wird interaktiv gefragt)}
        {--demo : Demo-Daten anlegen}';

    protected $description = 'Migrate the database, seed base data and create the first admin';

    public function handle(InstallationService $installation): int
    {
        if ($installation->isInstalled()) {
            $this->error('Custovis ist bereits installiert (storage/installed.lock bzw. vorhandene Benutzer).');

            return self::FAILURE;
        }

        $admin = [
            'name' => $this->option('admin-name') ?? $this->ask('Name des Administrators'),
            'email' => $this->option('admin-email') ?? $this->ask('E-Mail des Administrators'),
            'password' => $this->option('admin-password') ?? $this->secret('Passwort (mind. 12 Zeichen)'),
        ];

        $validator = Validator::make($admin, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            collect($validator->errors()->all())->each(fn (string $message) => $this->error($message));

            return self::FAILURE;
        }

        $installation->install($admin, (bool) $this->option('demo'));
        $this->info('Installation abgeschlossen. Anmeldung unter '.url('/login'));

        return self::SUCCESS;
    }
}

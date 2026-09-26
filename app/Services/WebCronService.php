<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Replaces the "schedule:run + queue:work" cronjobs on hosts that can only
 * call a URL periodically (no shell, see README "Nach der Installation").
 */
class WebCronService
{
    public const TOKEN_HASH_KEY = 'web_cron.token_hash';

    public const LAST_RUN_KEY = 'web_cron.last_run_at';

    public const QUEUES = 'default,mail-fetch,mail-send,git-sync,ai-processing,whatsapp,notifications,field-sync';

    // Stays below common web-cron timeouts; the next call picks up the rest.
    private const QUEUE_MAX_SECONDS = 40;

    public function __construct(private Application $app, private Schedule $schedule) {}

    /**
     * Only the hash is stored, so the URL is shown exactly once.
     */
    public function regenerateToken(): string
    {
        $token = Str::random(48);
        Setting::write(self::TOKEN_HASH_KEY, hash('sha256', $token));

        return $token;
    }

    public function isConfigured(): bool
    {
        return Setting::read(self::TOKEN_HASH_KEY) !== null;
    }

    public function verify(string $token): bool
    {
        $hash = Setting::read(self::TOKEN_HASH_KEY);

        return $hash !== null && hash_equals($hash, hash('sha256', $token));
    }

    public function run(): void
    {
        // routes/console.php holds every Schedule:: entry but is only loaded by
        // the console kernel, which a web request never boots on its own.
        $this->app->make(ConsoleKernel::class)->bootstrap();

        foreach ($this->schedule->dueEvents($this->app) as $event) {
            if ($event->filtersPass($this->app)) {
                $this->runInProcess($event);
            }
        }

        Artisan::call('queue:work', [
            '--queue' => self::QUEUES,
            '--stop-when-empty' => true,
            '--max-time' => self::QUEUE_MAX_SECONDS,
            '--tries' => 3,
        ]);

        Setting::write(self::LAST_RUN_KEY, now()->toIso8601String());
    }

    // schedule:run would spawn "php artisan …" subprocesses, which fails when
    // PHP runs as FPM/CGI or proc_open is disabled on shared hosting.
    private function runInProcess(Event $event): void
    {
        if ($event instanceof CallbackEvent) {
            $event->run($this->app);

            return;
        }

        Artisan::call(trim(Str::after($event->command, 'artisan'), "' \""));
    }
}

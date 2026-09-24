<?php

namespace App\Livewire\Admin;

use App\Console\Commands\ApplyRetentionPolicies;
use App\Console\Commands\PruneChatMessages;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * DSGVO tools and retention settings (11.md). The GDPR commands are run via
 * Artisan::call because target servers offer no shell access.
 */
#[Layout('layouts.admin')]
class ComplianceCenter extends Component
{
    public string $identifier = '';

    public string $confirmation = '';

    public ?string $result = null;

    /** @var array<string, int|string> */
    public array $retention = ['chat_days' => 0, 'audit_log_days' => 0, 'closed_ticket_days' => 0];

    public function mount(): void
    {
        Gate::authorize('compliance.manage');

        $this->retention = [
            'chat_days' => (int) Setting::read(PruneChatMessages::RETENTION_SETTING_KEY, '0'),
            'audit_log_days' => (int) Setting::read(ApplyRetentionPolicies::AUDIT_DAYS_KEY, '0'),
            'closed_ticket_days' => (int) Setting::read(ApplyRetentionPolicies::CLOSED_TICKET_DAYS_KEY, '0'),
        ];
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('compliance.manage');
        $this->validate(['identifier' => ['required', 'string', 'max:255']]);

        Artisan::call('gdpr:export-customer-data', ['identifier' => $this->identifier]);
        $path = trim(Artisan::output());
        AuditLog::record('gdpr.exported', Auth::user(), null);

        return response()->streamDownload(function () use ($path) {
            echo Storage::disk('local')->get($path);
            Storage::disk('local')->delete($path); // personal data must not linger on disk
        }, 'dsgvo-auskunft-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    public function anonymize(): void
    {
        Gate::authorize('compliance.manage');
        $this->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'confirmation' => ['required', 'same:identifier'],
        ], ['confirmation.same' => 'Zur Bestätigung die Kennung exakt wiederholen.']);

        Artisan::call('gdpr:anonymize-customer', ['identifier' => $this->identifier, '--actor' => Auth::id()]);

        $this->result = trim(Artisan::output());
        $this->reset(['identifier', 'confirmation']);
    }

    public function saveRetention(): void
    {
        Gate::authorize('compliance.manage');
        $data = $this->validate([
            'retention.chat_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'retention.audit_log_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'retention.closed_ticket_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ])['retention'];

        Setting::write(PruneChatMessages::RETENTION_SETTING_KEY, (string) $data['chat_days']);
        Setting::write(ApplyRetentionPolicies::AUDIT_DAYS_KEY, (string) $data['audit_log_days']);
        Setting::write(ApplyRetentionPolicies::CLOSED_TICKET_DAYS_KEY, (string) $data['closed_ticket_days']);

        AuditLog::record('retention.updated', Auth::user(), null, null, array_map('intval', $data));
        $this->result = 'Aufbewahrungsfristen gespeichert.';
    }

    public function render()
    {
        return view('livewire.admin.compliance-center');
    }
}

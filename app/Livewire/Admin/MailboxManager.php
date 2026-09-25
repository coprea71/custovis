<?php

namespace App\Livewire\Admin;

use App\Models\Mailbox;
use App\Models\Team;
use App\Services\MailboxImapService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.admin')]
class MailboxManager extends Component
{
    public ?int $editingId = null;

    public int $team_id = 0;

    public string $name = '';

    public string $email_address = '';

    public string $imap_host = '';

    public int $imap_port = 993;

    public string $imap_encryption = 'ssl';

    public string $imap_username = '';

    public string $imap_password = '';

    public string $smtp_host = '';

    public int $smtp_port = 587;

    public string $smtp_encryption = 'tls';

    public string $smtp_username = '';

    public string $smtp_password = '';

    /** @var array<int, array{ok: bool, message: string}> */
    public array $testResults = [];

    public function mount(): void
    {
        Gate::authorize('mailboxes.manage');
    }

    public function edit(int $mailboxId): void
    {
        $mailbox = Mailbox::query()->findOrFail($mailboxId);

        $this->editingId = $mailbox->id;
        $this->team_id = $mailbox->team_id;
        $this->name = $mailbox->name;
        $this->email_address = $mailbox->email_address;
        $this->imap_host = $mailbox->imap_host;
        $this->imap_port = $mailbox->imap_port;
        $this->imap_encryption = $mailbox->imap_encryption;
        $this->imap_username = $mailbox->imap_username;
        $this->smtp_host = $mailbox->smtp_host;
        $this->smtp_port = $mailbox->smtp_port;
        $this->smtp_encryption = $mailbox->smtp_encryption;
        $this->smtp_username = $mailbox->smtp_username;
        // Passwords are intentionally not pre-filled — re-entering rotates the secret.
        $this->imap_password = '';
        $this->smtp_password = '';
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'team_id', 'name', 'email_address',
            'imap_host', 'imap_port', 'imap_encryption', 'imap_username', 'imap_password',
            'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
        ]);
        $this->imap_port = 993;
        $this->smtp_port = 587;
        $this->imap_encryption = 'ssl';
        $this->smtp_encryption = 'tls';
    }

    public function save(): void
    {
        Gate::authorize('mailboxes.manage');

        $data = $this->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:255'],
            'email_address' => ['required', 'email', 'max:255'],
            'imap_host' => ['required', 'string', 'max:255'],
            'imap_port' => ['required', 'integer', 'between:1,65535'],
            'imap_encryption' => ['required', 'in:ssl,tls,none'],
            'imap_username' => ['required', 'string', 'max:255'],
            'imap_password' => [$this->editingId ? 'nullable' : 'required', 'string'],
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['required', 'in:ssl,tls,none'],
            'smtp_username' => ['required', 'string', 'max:255'],
            'smtp_password' => [$this->editingId ? 'nullable' : 'required', 'string'],
        ]);

        if ($this->editingId) {
            $mailbox = Mailbox::query()->findOrFail($this->editingId);

            if ($data['imap_password'] === '') {
                unset($data['imap_password']);
            }
            if ($data['smtp_password'] === '') {
                unset($data['smtp_password']);
            }

            $mailbox->update($data);
        } else {
            Mailbox::query()->create($data);
        }

        $this->resetForm();
    }

    public function toggleActive(int $mailboxId): void
    {
        Gate::authorize('mailboxes.manage');

        $mailbox = Mailbox::query()->findOrFail($mailboxId);
        $mailbox->update(['active' => ! $mailbox->active]);
    }

    public function testConnection(int $mailboxId, MailboxImapService $imap): void
    {
        Gate::authorize('mailboxes.manage');

        $mailbox = Mailbox::query()->findOrFail($mailboxId);

        try {
            $unseen = $imap->countUnseen($mailbox);
            $this->testResults[$mailboxId] = ['ok' => true, 'message' => "Verbindung erfolgreich – {$unseen} ungelesene Nachricht(en) in der INBOX."];
        } catch (Throwable $e) {
            Log::warning("MailboxManager: IMAP test failed for mailbox [{$mailbox->id}]: {$e->getMessage()}");
            $this->testResults[$mailboxId] = ['ok' => false, 'message' => 'Verbindung fehlgeschlagen: '.Str::limit($e->getMessage(), 200)];
        }
    }

    public function render()
    {
        return view('livewire.admin.mailbox-manager', [
            'mailboxes' => Mailbox::query()->with('team')->latest()->get(),
            'teams' => Team::query()->orderBy('name')->get(),
        ]);
    }
}

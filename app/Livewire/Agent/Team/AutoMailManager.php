<?php

namespace App\Livewire\Agent\Team;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\TeamMailLayout;
use App\Support\HttpErrorMail;
use Closure;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Holders of team.auto_mails.manage configure the automatic customer mails of
 * every team and decide per team whether its team admins may do so too.
 */
#[Layout('layouts.agent')]
class AutoMailManager extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public bool $isCentralAdmin = false;

    /** @var array<string, bool> type => enabled */
    public array $enabled = [];

    /** @var array<string, string> type => text */
    public array $texts = [];

    public bool $errorMailFilterEnabled = false;

    /** Allowed senders of HTTP error mails, one address or domain per line; empty = any sender. */
    public string $errorMailSenders = '';

    public ?string $status = null;

    public function mount(Team $team): void
    {
        abort_unless($team->allowsAutoMailsFor(Auth::user()), 403);

        $this->team = $team;
        $this->isCentralAdmin = Auth::user()->can('team.auto_mails.manage');
        $layout = TeamMailLayout::forTeam($team);
        foreach (array_keys(TeamMailLayout::AUTO_MAILS) as $type) {
            $this->enabled[$type] = $layout->autoMailEnabled($type);
            $this->texts[$type] = $layout->autoMailText($type);
        }
        $this->errorMailFilterEnabled = $layout->error_mail_filter_enabled;
        $this->errorMailSenders = (string) $layout->error_mail_senders;
    }

    public function save(): void
    {
        abort_unless($this->team->allowsAutoMailsFor(Auth::user()), 403);
        $this->validate($this->rules());
        $layout = TeamMailLayout::forTeam($this->team);

        foreach (array_keys(TeamMailLayout::AUTO_MAILS) as $type) {
            $text = trim(str_replace("\r\n", "\n", $this->texts[$type]));
            $layout->{$type.'_enabled'} = $this->enabled[$type];
            // The unchanged default is not stored, so later default improvements still reach the team.
            $layout->{$type.'_text'} = $text === TeamMailLayout::AUTO_MAILS[$type][1] ? null : $text;
        }
        $senders = HttpErrorMail::parseSenders($this->errorMailSenders);
        $layout->error_mail_filter_enabled = $this->errorMailFilterEnabled;
        $layout->error_mail_senders = $senders === [] ? null : implode("\n", $senders);
        $this->errorMailSenders = (string) $layout->error_mail_senders;
        $changed = array_keys($layout->getDirty());
        $layout->save();

        AuditLog::record('team.auto_mails_updated', Auth::user(), $this->team, $layout, ['fields_changed' => $changed]);
        $this->status = 'Automatische E-Mails gespeichert.';
    }

    public function toggleTeamAdminAccess(): void
    {
        abort_unless(Auth::user()->can('team.auto_mails.manage'), 403);

        $this->team->forceFill(['auto_mails_team_admin_access' => ! $this->team->auto_mails_team_admin_access])->save();
        AuditLog::record('team.auto_mails_access_changed', Auth::user(), $this->team, null, ['team_admin_access' => $this->team->auto_mails_team_admin_access]);
    }

    public function resetText(string $type): void
    {
        abort_unless(isset(TeamMailLayout::AUTO_MAILS[$type]), 404);

        $this->texts[$type] = TeamMailLayout::AUTO_MAILS[$type][1];
    }

    public function render()
    {
        return view('livewire.agent.team.auto-mail-manager');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        $rules = [
            'errorMailFilterEnabled' => ['required', 'boolean'],
            'errorMailSenders' => ['nullable', 'string', 'max:2000', $this->validSenders(...)],
        ];
        foreach (array_keys(TeamMailLayout::AUTO_MAILS) as $type) {
            $rules["enabled.{$type}"] = ['required', 'boolean'];
            $rules["texts.{$type}"] = ['required', 'string', 'max:5000'];
        }

        return $rules;
    }

    private function validSenders(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (HttpErrorMail::parseSenders((string) $value) as $sender) {
            $valid = str_contains($sender, '@')
                ? filter_var($sender, FILTER_VALIDATE_EMAIL) !== false
                : filter_var($sender, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false && str_contains($sender, '.');
            if (! $valid) {
                $fail("„{$sender}“ ist weder eine gültige E-Mail-Adresse noch eine Domain.");

                return;
            }
        }
    }
}

<?php

namespace App\Livewire\Agent\Team;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\TeamMailLayout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Team admins switch and word the automatic customer mails; holders of
 * team.manage see them read-only — same pattern as the mail layout.
 */
#[Layout('layouts.agent')]
class AutoMailManager extends Component
{
    #[Locked]
    public Team $team;

    #[Locked]
    public bool $readOnly = true;

    /** @var array<string, bool> type => enabled */
    public array $enabled = [];

    /** @var array<string, string> type => text */
    public array $texts = [];

    public ?string $status = null;

    public function mount(Team $team): void
    {
        $user = Auth::user();
        abort_unless($user->isTeamAdminOf($team) || $user->can('team.manage'), 403);

        $this->team = $team;
        $this->readOnly = ! $user->isTeamAdminOf($team);
        $layout = TeamMailLayout::forTeam($team);
        foreach (array_keys(TeamMailLayout::AUTO_MAILS) as $type) {
            $this->enabled[$type] = $layout->autoMailEnabled($type);
            $this->texts[$type] = $layout->autoMailText($type);
        }
    }

    public function save(): void
    {
        abort_if($this->readOnly, 403);
        $this->validate($this->rules());
        $layout = TeamMailLayout::forTeam($this->team);

        foreach (array_keys(TeamMailLayout::AUTO_MAILS) as $type) {
            $text = trim(str_replace("\r\n", "\n", $this->texts[$type]));
            $layout->{$type.'_enabled'} = $this->enabled[$type];
            // The unchanged default is not stored, so later default improvements still reach the team.
            $layout->{$type.'_text'} = $text === TeamMailLayout::AUTO_MAILS[$type][1] ? null : $text;
        }
        $changed = array_keys($layout->getDirty());
        $layout->save();

        AuditLog::record('team.auto_mails_updated', Auth::user(), $this->team, $layout, ['fields_changed' => $changed]);
        $this->status = 'Automatische E-Mails gespeichert.';
    }

    public function resetText(string $type): void
    {
        abort_if($this->readOnly, 403);
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
        $rules = [];
        foreach (array_keys(TeamMailLayout::AUTO_MAILS) as $type) {
            $rules["enabled.{$type}"] = ['required', 'boolean'];
            $rules["texts.{$type}"] = ['required', 'string', 'max:5000'];
        }

        return $rules;
    }
}

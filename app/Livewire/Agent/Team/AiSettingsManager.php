<?php

namespace App\Livewire\Agent\Team;

use App\Models\AiBudget;
use App\Models\AiSetting;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agent')]
class AiSettingsManager extends Component
{
    public Team $team;

    public bool $readOnly = false;

    public string $use_case = 'summarize';

    public string $provider = 'openai';

    public string $api_key = '';

    public string $endpoint_url = '';

    public string $model = '';

    public bool $redact_pii = false;

    public string $monthly_limit_euros = '';

    public function mount(Team $team): void
    {
        $user = Auth::user();

        if ($user->isTeamAdminOf($team)) {
            $this->readOnly = false;
        } elseif ($user->can('team.ai.manage')) {
            $this->readOnly = true;
        } else {
            abort(403);
        }

        $this->team = $team;
        $this->monthly_limit_euros = (string) ((AiBudget::query()->where('team_id', $team->id)->value('monthly_limit_cents') ?? 0) / 100);
    }

    public function saveSetting(): void
    {
        abort_if($this->readOnly, 403);

        $data = $this->validate([
            'use_case' => ['required', 'in:summarize,suggest_reply,classify,embed'],
            'provider' => ['required', 'in:openai,anthropic,ollama,custom'],
            'api_key' => ['nullable', 'string'],
            'endpoint_url' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'redact_pii' => ['boolean'],
        ]);

        AiSetting::query()->updateOrCreate(
            ['team_id' => $this->team->id, 'use_case' => $data['use_case']],
            [
                'provider' => $data['provider'],
                'api_key' => $data['api_key'] ?: null,
                'endpoint_url' => $data['endpoint_url'] ?: null,
                'model' => $data['model'] ?: null,
                'redact_pii' => $data['redact_pii'],
            ]
        );

        $this->reset(['api_key', 'endpoint_url', 'model']);
    }

    public function saveBudget(): void
    {
        abort_if($this->readOnly, 403);

        $this->validate(['monthly_limit_euros' => ['required', 'numeric', 'min:0']]);

        AiBudget::query()->updateOrCreate(
            ['team_id' => $this->team->id],
            ['monthly_limit_cents' => (int) round(((float) $this->monthly_limit_euros) * 100)]
        );
    }

    public function render()
    {
        return view('livewire.agent.team.ai-settings-manager', [
            'settings' => AiSetting::query()->where('team_id', $this->team->id)->get()->keyBy('use_case'),
        ]);
    }
}

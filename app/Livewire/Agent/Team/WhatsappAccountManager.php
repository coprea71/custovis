<?php

namespace App\Livewire\Agent\Team;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agent')]
class WhatsappAccountManager extends Component
{
    public Team $team;

    public bool $readOnly = false;

    public string $display_name = '';

    public string $phone_number_id = '';

    public string $business_account_id = '';

    public string $access_token = '';

    public string $webhook_verify_token = '';

    public string $app_secret = '';

    public ?string $connectionTestResult = null;

    public function mount(Team $team): void
    {
        $user = Auth::user();

        if ($user->isTeamAdminOf($team)) {
            $this->readOnly = false;
        } elseif ($user->can('team.whatsapp.manage')) {
            $this->readOnly = true;
        } else {
            abort(403);
        }

        $this->team = $team;
    }

    public function createAccount(): void
    {
        abort_if($this->readOnly, 403);

        $data = $this->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'phone_number_id' => ['required', 'string', 'max:255', 'unique:whatsapp_accounts,phone_number_id'],
            'business_account_id' => ['required', 'string', 'max:255'],
            'access_token' => ['required', 'string'],
            'webhook_verify_token' => ['required', 'string', 'min:8'],
            'app_secret' => ['required', 'string'],
        ]);

        $account = WhatsappAccount::query()->create([...$data, 'team_id' => $this->team->id]);

        AuditLog::record('whatsapp_account.created', Auth::user(), $this->team, $account, [
            'display_name' => $account->display_name,
            'phone_number_id' => $account->phone_number_id,
        ]);

        $this->reset(['display_name', 'phone_number_id', 'business_account_id', 'access_token', 'webhook_verify_token', 'app_secret']);
    }

    public function testConnection(int $accountId): void
    {
        $account = WhatsappAccount::query()->where('team_id', $this->team->id)->findOrFail($accountId);

        $response = Http::withToken($account->access_token)
            ->get("https://graph.facebook.com/v20.0/{$account->phone_number_id}");

        $this->connectionTestResult = $response->successful()
            ? 'Verbindung erfolgreich.'
            : 'Verbindung fehlgeschlagen: '.$response->status();
    }

    public function toggleActive(int $accountId): void
    {
        abort_if($this->readOnly, 403);

        $account = WhatsappAccount::query()->where('team_id', $this->team->id)->findOrFail($accountId);
        $account->update(['active' => ! $account->active]);
    }

    public function render()
    {
        return view('livewire.agent.team.whatsapp-account-manager', [
            'accounts' => WhatsappAccount::query()->where('team_id', $this->team->id)->latest()->get(),
        ]);
    }
}

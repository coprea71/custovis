<?php

namespace App\Livewire\Agent\Team;

use App\Models\Team;
use App\Services\ModuleAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Hub for a team's settings pages (API keys, WhatsApp, AI, Git, ERP …),
 * which previously were only reachable by typing the URL.
 */
#[Layout('layouts.agent')]
class TeamSettings extends Component
{
    /**
     * route name => [label, description, permission granting (read) access to non-team-admins, module slug (optional)]
     */
    public const PAGES = [
        'agent.team.canned-responses' => ['Textbausteine', 'Vorlagen für Antworten im Ticket', 'team.manage'],
        'agent.team.api-keys' => ['API-Keys', 'Ticket-API und MCP-Zugänge für Telefonassistenten', 'team.api_keys.manage'],
        'agent.team.whatsapp' => ['WhatsApp', 'WhatsApp-Business-Konten und Vorlagen', 'team.whatsapp.manage', 'whatsapp'],
        'agent.team.git-issues' => ['Git-Issues', 'GitHub-/GitLab-Issue-Import', 'team.git_issues.manage'],
        'agent.team.ai' => ['KI', 'KI-Provider, Budgets und Datenschutz', 'team.ai.manage', 'ai-agent'],
        'agent.team.erp' => ['ERP', 'Kundendaten aus Odoo oder Shopware', 'team.erp.manage', 'erp-integration'],
    ];

    #[Locked]
    public Team $team;

    public function mount(Team $team): void
    {
        abort_if($this->pages($team) === [], 403);

        $this->team = $team;
    }

    public function render()
    {
        return view('livewire.agent.team.team-settings', ['pages' => $this->pages($this->team)]);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    private function pages(Team $team): array
    {
        $user = Auth::user();
        $isTeamAdmin = $user->isTeamAdminOf($team);

        $modules = app(ModuleAccess::class);

        return array_filter(self::PAGES, fn (array $page) => ($isTeamAdmin || $user->can($page[2]))
            && (! isset($page[3]) || $modules->allows($user, $page[3])));
    }
}

<?php

namespace App\Livewire\Agent;

use App\Core\Ai\Support\AiTicketAssistant;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ModuleAccess;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Summary and reply suggestion on request (25.md). The result arrives as an
 * internal note once the ai-processing queue has run the job.
 */
class TicketAiPanel extends Component
{
    #[Locked]
    public int $ticketId;

    public ?string $status = null;

    public static function availableFor(Ticket $ticket, ?User $user): bool
    {
        return $user !== null
            && app(ModuleAccess::class)->allows($user, 'ai-agent')
            && $user->can('update', $ticket);
    }

    public function mount(): void
    {
        $this->authorizedTicket();
    }

    public function request(string $useCase, AiTicketAssistant $assistant): void
    {
        $ticket = $this->authorizedTicket();

        $this->status = $assistant->request($ticket, $useCase)
            ? 'In Arbeit – das Ergebnis erscheint in Kürze als interne Notiz.'
            : 'Nicht verfügbar: Für diese Funktion ist kein KI-Provider eingerichtet oder das Monatsbudget ist aufgebraucht.';
    }

    public function render(AiTicketAssistant $assistant)
    {
        $team = $this->authorizedTicket()->team;

        return view('livewire.agent.ticket-ai-panel', [
            'summarizeAvailable' => $assistant->canRun($team, 'summarize'),
            'suggestReplyAvailable' => $assistant->canRun($team, 'suggest_reply'),
        ]);
    }

    private function authorizedTicket(): Ticket
    {
        $ticket = Ticket::query()->with('team')->findOrFail($this->ticketId);
        abort_unless(self::availableFor($ticket, auth()->user()), 403);

        return $ticket;
    }
}

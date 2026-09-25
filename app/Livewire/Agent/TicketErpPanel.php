<?php

namespace App\Livewire\Agent;

use App\Models\ErpConnection;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Erp\ErpCustomerLookupService;
use App\Services\Erp\ErpLookupException;
use App\Services\ModuleAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * ERP customer data in the ticket sidebar (15.md). Loaded only on explicit
 * request so an ERP outage never delays opening a ticket.
 */
class TicketErpPanel extends Component
{
    #[Locked]
    public int $ticketId;

    /**
     * @var list<array{name: string, status: string, fields: array<string, ?string>}>|null
     */
    #[Locked]
    public ?array $results = null;

    /**
     * The panel is only offered when the user may use it and the ticket's
     * team actually has an active ERP connection to query.
     */
    public static function availableFor(Ticket $ticket, ?User $user): bool
    {
        return $user !== null
            && app(ModuleAccess::class)->allows($user, 'erp-integration')
            && $user->can('erp.customer.view')
            && ErpConnection::query()->where('team_id', $ticket->team_id)->where('is_active', true)->exists();
    }

    public function mount(): void
    {
        Gate::authorize('erp.customer.view');
    }

    public function loadCustomer(ErpCustomerLookupService $lookup): void
    {
        Gate::authorize('erp.customer.view');

        $ticket = Ticket::query()->findOrFail($this->ticketId);
        // Customer data is only released to members of the ticket's own team.
        abort_unless(Auth::user()->teams()->whereKey($ticket->team_id)->exists(), 403);

        $reference = trim((string) $ticket->requester_email);

        $this->results = $reference === '' ? [] : ErpConnection::query()
            ->where('team_id', $ticket->team_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ErpConnection $connection) => $this->lookupOne($lookup, $connection, $reference))
            ->all();
    }

    public function render()
    {
        return view('livewire.agent.ticket-erp-panel');
    }

    private function lookupOne(ErpCustomerLookupService $lookup, ErpConnection $connection, string $reference): array
    {
        try {
            $fields = $lookup->lookup($connection, $reference, Auth::user());
        } catch (ErpLookupException) {
            return ['name' => $connection->name, 'status' => 'error', 'fields' => []];
        }

        return ['name' => $connection->name, 'status' => $fields === null ? 'not_found' : 'found', 'fields' => $fields ?? []];
    }
}

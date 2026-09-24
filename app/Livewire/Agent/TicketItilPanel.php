<?php

namespace App\Livewire\Agent;

use App\Models\CmdbConfigurationItem;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ChangeApprovalService;
use App\States\Change\Approved;
use App\States\Change\CabReview;
use App\States\Change\Draft;
use App\States\Change\Rejected;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * ITIL controls in the ticket sidebar (21.md): state transitions offered
 * from the state machine, type-specific fields, CAB request, linked CIs.
 */
class TicketItilPanel extends Component
{
    /** CAB outcome states are set by ChangeApprovalService only, never by hand. */
    private const CAB_DRIVEN = [CabReview::class, Approved::class, Rejected::class];

    #[Locked]
    public int $ticketId;

    /** @var array<string, mixed> */
    public array $details = [];

    /** @var array<int, int|string> */
    public array $approverIds = [];

    public ?int $ciId = null;

    public function mount(): void
    {
        $this->details = $this->extension()?->only(['impact', 'urgency', 'root_cause', 'change_type', 'risk_level', 'planned_start', 'planned_end']) ?? [];
        $this->details = array_map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d\TH:i') : $value, $this->details);
    }

    public function changeState(string $state): void
    {
        $extension = $this->authorizedExtension();
        abort_unless(in_array($state, $this->manualTransitions($extension), true), 422);

        $extension->state->transitionTo($state);
    }

    public function saveDetails(): void
    {
        $extension = $this->authorizedExtension();
        $level = Rule::in(['low', 'medium', 'high']);
        $data = $this->validate([
            'details.impact' => ['sometimes', $level],
            'details.urgency' => ['sometimes', $level],
            'details.root_cause' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'details.change_type' => ['sometimes', Rule::in(['standard', 'normal', 'emergency'])],
            'details.risk_level' => ['sometimes', $level],
            'details.planned_start' => ['sometimes', 'nullable', 'date'],
            'details.planned_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:details.planned_start'],
        ])['details'] ?? [];

        $extension->update(array_intersect_key($data, array_flip($extension->getFillable())));
    }

    public function requestCab(ChangeApprovalService $approvals): void
    {
        $extension = $this->authorizedExtension();
        abort_unless($this->ticket()->type === 'change' && $extension->state->equals(Draft::class), 422);
        $this->validate([
            'approverIds' => ['required', 'array', 'min:1'],
            'approverIds.*' => [Rule::in($this->approverCandidates()->pluck('id'))],
        ], ['approverIds.required' => 'Mindestens eine genehmigende Person auswählen.']);

        $approvals->requestApprovals($this->ticket(), collect($this->approverIds)->map(fn ($id) => (int) $id));
        $this->approverIds = [];
    }

    public function attachCi(): void
    {
        Gate::authorize('update', $this->ticket());
        $this->validate(['ciId' => ['required', Rule::in($this->availableCis()->pluck('id'))]]);

        $this->ticket()->configurationItems()->syncWithoutDetaching([$this->ciId]);
        $this->ciId = null;
    }

    public function detachCi(int $ciId): void
    {
        Gate::authorize('update', $this->ticket());
        $this->ticket()->configurationItems()->detach($ciId);
    }

    public function render()
    {
        $ticket = $this->ticket()->load('configurationItems', 'change.approvals.approver');
        $extension = $this->extension();

        return view('livewire.agent.ticket-itil-panel', [
            'ticket' => $ticket,
            'extension' => $extension,
            'transitions' => $extension ? collect($this->manualTransitions($extension))->mapWithKeys(fn ($class) => [$class => (new $class($extension))->label()]) : collect(),
            'candidates' => $ticket->type === 'change' ? $this->approverCandidates() : collect(),
            'cis' => $this->availableCis(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function manualTransitions(Model $extension): array
    {
        return array_values(array_diff($extension->state->transitionableStates(), self::CAB_DRIVEN));
    }

    private function authorizedExtension(): Model
    {
        Gate::authorize('update', $this->ticket());

        return $this->extension() ?? abort(404);
    }

    private function extension(): ?Model
    {
        $ticket = $this->ticket();

        return match ($ticket->type) {
            'incident' => $ticket->incident,
            'problem' => $ticket->problem,
            'change' => $ticket->change,
            'service_request' => $ticket->serviceRequest,
            default => null,
        };
    }

    private function ticket(): Ticket
    {
        return Ticket::query()->visibleTo(Auth::user())->findOrFail($this->ticketId);
    }

    private function approverCandidates()
    {
        return User::permission('changes.approve')->where('active', true)->orderBy('name')->get();
    }

    private function availableCis()
    {
        return CmdbConfigurationItem::query()->where('team_id', $this->ticket()->team_id)->where('status', 'active')->orderBy('name')->get();
    }
}

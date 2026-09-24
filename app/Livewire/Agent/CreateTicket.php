<?php

namespace App\Livewire\Agent;

use App\Models\Team;
use App\Models\Ticket;
use App\Services\ManualTicketService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.agent')]
class CreateTicket extends Component
{
    /** @var array<string, mixed> */
    public array $form = [
        'team_id' => '', 'type' => 'support_ticket', 'subject' => '', 'priority' => 'normal',
        'requester_name' => '', 'requester_email' => '', 'requester_phone' => '', 'body' => '',
    ];

    public function mount(): void
    {
        abort_if($this->allowedTeams()->isEmpty(), 403);

        $this->form['team_id'] = $this->allowedTeams()->first()->id;
    }

    public function save(ManualTicketService $tickets): void
    {
        $data = $this->validate([
            'form.team_id' => ['required', 'integer', Rule::in($this->allowedTeams()->pluck('id'))],
            'form.type' => ['required', Rule::in(ManualTicketService::TYPES)],
            'form.subject' => ['required', 'string', 'max:255'],
            'form.priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'form.requester_name' => ['nullable', 'string', 'max:255'],
            'form.requester_email' => ['nullable', 'email', 'max:255'],
            'form.requester_phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9 ()\/-]{5,40}$/'],
            'form.body' => ['required', 'string', 'max:20000'],
        ])['form'];

        $ticket = $tickets->create(array_map(fn ($value) => $value === '' ? null : $value, $data), Auth::user());

        $this->redirectRoute('agent.tickets.show', $ticket);
    }

    public function render()
    {
        return view('livewire.agent.create-ticket', ['teams' => $this->allowedTeams()]);
    }

    /**
     * @return Collection<int, Team>
     */
    private function allowedTeams(): Collection
    {
        $user = Auth::user();

        return $user->can('tickets.view.all') ? Team::query()->orderBy('name')->get() : $user->teams()->orderBy('name')->get();
    }
}

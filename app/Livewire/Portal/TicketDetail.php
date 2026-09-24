<?php

namespace App\Livewire\Portal;

use App\Http\Requests\Portal\ReplyToTicketRequest;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.portal')]
class TicketDetail extends Component
{
    #[Locked]
    public int $ticketId;

    public string $reply = '';

    public function mount(Ticket $ticket): void
    {
        Gate::forUser(Auth::guard('customer')->user())->authorize('viewAsCustomer', $ticket);

        $this->ticketId = $ticket->id;
    }

    public function sendReply(): void
    {
        $ticket = $this->ticket();
        $customer = Auth::guard('customer')->user();
        Gate::forUser($customer)->authorize('replyAsCustomer', $ticket);

        $data = $this->validate((new ReplyToTicketRequest)->rules());

        $ticket->messages()->create([
            'visibility' => TicketMessage::VISIBILITY_PUBLIC,
            'direction' => 'incoming',
            'author_customer_id' => $customer->id,
            'body_text' => $data['reply'],
            'body_html' => nl2br(e($data['reply'])),
        ]);

        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'reopened', 'closed_at' => null]);
        }

        $this->reset('reply');
    }

    public function render()
    {
        $ticket = $this->ticket();

        return view('livewire.portal.ticket-detail', [
            'ticket' => $ticket,
            'timeline' => $this->timeline($ticket),
        ]);
    }

    /**
     * Customer-facing history: creation, public messages only (internal
     * notes never leave the agent area) and closing.
     *
     * @return Collection<int, array{at: Carbon, label: string, body: string|null}>
     */
    private function timeline(Ticket $ticket): Collection
    {
        $messages = $ticket->messages()
            ->where('visibility', TicketMessage::VISIBILITY_PUBLIC)
            ->get()
            ->map(fn (TicketMessage $message) => [
                'at' => $message->created_at,
                'label' => $message->direction === 'incoming' ? 'Ihre Nachricht' : 'Antwort des Supports',
                'body' => $message->body_text,
            ]);

        return collect([['at' => $ticket->created_at, 'label' => 'Anfrage eingegangen', 'body' => null]])
            ->concat($messages)
            ->when($ticket->closed_at, fn ($items) => $items->push(['at' => $ticket->closed_at, 'label' => 'Anfrage abgeschlossen', 'body' => null]))
            ->sortBy('at')
            ->values();
    }

    private function ticket(): Ticket
    {
        return Ticket::query()->findOrFail($this->ticketId);
    }
}

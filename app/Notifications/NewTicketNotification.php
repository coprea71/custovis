<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Deliberately carries no ticket body or requester data: the mail leaves the
 * system unencrypted, the agent reads the details after logging in.
 */
class NewTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket)
    {
        $this->onQueue('notifications');
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Ticket #'.$this->ticket->id.'] Neues Ticket: '.$this->ticket->subject)
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('im Team „'.$this->ticket->team->name.'“ ist ein neues Ticket eingegangen.')
            ->line('Betreff: '.$this->ticket->subject)
            ->line('Priorität: '.(Ticket::PRIORITY_LABELS[$this->ticket->priority] ?? $this->ticket->priority))
            ->action('Ticket öffnen', route('agent.tickets.show', $this->ticket))
            ->line('Sie erhalten diese E-Mail, weil Sie Benachrichtigungen zu neuen Tickets aktiviert haben. Abschalten können Sie das im Konto-Menü unter „Kontosicherheit“.');
    }
}

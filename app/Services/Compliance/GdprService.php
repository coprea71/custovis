<?php

namespace App\Services\Compliance;

use App\Models\AppointmentDelivery;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Art. 15 (export) and Art. 17 (erasure by anonymisation) for a data
 * subject identified by customer id, e-mail address or phone number.
 * Anonymisation keeps tickets for statistics/audit but removes everything
 * that identifies the person (11.md).
 */
class GdprService
{
    public const PLACEHOLDER = '[anonymisiert]';

    /**
     * @return array{customer: Customer|null, email: string|null, phone: string|null}
     */
    public function subject(string $identifier): array
    {
        $identifier = trim($identifier);
        $customer = ctype_digit($identifier) ? Customer::query()->find((int) $identifier)
            : (str_contains($identifier, '@') ? Customer::query()->where('email', $identifier)->first() : null);

        return [
            'customer' => $customer,
            'email' => $customer?->email ?? (str_contains($identifier, '@') ? $identifier : null),
            'phone' => ! ctype_digit($identifier) && ! str_contains($identifier, '@') ? $identifier : null,
        ];
    }

    /**
     * @param  array{customer: Customer|null, email: string|null, phone: string|null}  $subject
     * @return Builder<Ticket>
     */
    public function tickets(array $subject): Builder
    {
        return Ticket::query()->where(fn (Builder $query) => $query
            ->when($subject['customer'], fn (Builder $q) => $q->orWhere('customer_id', $subject['customer']->id))
            ->when($subject['email'], fn (Builder $q) => $q->orWhere('requester_email', $subject['email']))
            ->when($subject['phone'], fn (Builder $q) => $q->orWhere('requester_phone', $subject['phone']))
            ->when(! $subject['customer'] && ! $subject['email'] && ! $subject['phone'], fn (Builder $q) => $q->whereRaw('1 = 0')));
    }

    /**
     * @param  array{customer: Customer|null, email: string|null, phone: string|null}  $subject
     * @return array<string, mixed>
     */
    public function export(array $subject): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'customer' => $subject['customer']?->only(['id', 'name', 'email', 'created_at']),
            'tickets' => $this->tickets($subject)->with(['messages' => fn ($q) => $q->where('visibility', TicketMessage::VISIBILITY_PUBLIC)])
                ->get()->map(fn (Ticket $ticket) => [
                    ...$ticket->only(['id', 'subject', 'status', 'type', 'source', 'requester_name', 'requester_email', 'requester_phone', 'created_at', 'closed_at']),
                    'messages' => $ticket->messages->map->only(['direction', 'body_text', 'created_at'])->all(),
                ])->all(),
        ];
    }

    /**
     * @param  array{customer: Customer|null, email: string|null, phone: string|null}  $subject
     */
    public function anonymize(array $subject, ?User $by): int
    {
        $ticketIds = $this->tickets($subject)->pluck('id');

        DB::transaction(function () use ($ticketIds, $subject) {
            $ticketIds->each(fn (int $id) => $this->anonymizeTicket(Ticket::query()->findOrFail($id)));
            $subject['customer']?->forceFill([
                'name' => self::PLACEHOLDER,
                'email' => 'anonymized-'.$subject['customer']->id.'@invalid.invalid',
                'password' => Str::random(64),
            ])->save();
        });

        AuditLog::record('gdpr.anonymized', $by, null, null, ['customer_id' => $subject['customer']?->id, 'ticket_count' => $ticketIds->count()]);

        return $ticketIds->count();
    }

    public function anonymizeTicket(Ticket $ticket): void
    {
        $ticket->forceFill(['requester_name' => self::PLACEHOLDER, 'requester_email' => null, 'requester_phone' => null, 'customer_id' => null])->saveQuietly();

        $ticket->messages()->where('direction', 'incoming')->with('attachments')->get()->each(function (TicketMessage $message) {
            $message->attachments->each(function ($attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            });
            $message->forceFill(['body_text' => self::PLACEHOLDER, 'body_html' => null, 'external_author_name' => null, 'external_author_email' => null])->save();
        });

        AppointmentDelivery::query()->whereHas('appointment', fn ($q) => $q->where('ticket_id', $ticket->id))
            ->update(['recipient_name' => self::PLACEHOLDER, 'recipient_email' => null, 'recipient_phone' => null]);
    }
}

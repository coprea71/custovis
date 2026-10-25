<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Storage;

/**
 * Structured per-team design of outgoing ticket mails. Deliberately no free
 * HTML: fixed, table-based markup renders reliably in Outlook/Gmail and
 * leaves no room for injected markup.
 */
class TeamMailLayout extends Model
{
    public const DEFAULT_COLOR = '#3D5E50';

    /**
     * Web-safe font stacks that render consistently across mail clients.
     */
    public const FONTS = [
        'arial' => ['Arial', 'Arial, Helvetica, sans-serif'],
        'verdana' => ['Verdana', 'Verdana, Geneva, sans-serif'],
        'tahoma' => ['Tahoma', 'Tahoma, Geneva, sans-serif'],
        'trebuchet' => ['Trebuchet MS', "'Trebuchet MS', Helvetica, sans-serif"],
        'georgia' => ['Georgia', 'Georgia, serif'],
    ];

    public const PLACEHOLDERS = [
        '{agent_name}' => 'Name des antwortenden Agenten',
        '{team_name}' => 'Name des Teams',
        '{mailbox_email}' => 'Absenderadresse der Mailbox',
        '{ticket_id}' => 'Ticketnummer',
        '{ticket_subject}' => 'Betreff des Tickets',
        '{ticket_status}' => 'Aktueller Status des Tickets',
        '{requester_name}' => 'Name des Kunden',
    ];

    /**
     * Automatic customer mails: type => [label, default text]. The default
     * applies as long as the team has not saved its own text.
     */
    public const AUTO_MAILS = [
        'acknowledgement' => ['Eingangsbestätigung', "Guten Tag {requester_name},\n\nvielen Dank für Ihre Nachricht! Wir haben Ihre Anfrage unter der Ticketnummer {ticket_id} erhalten und kümmern uns schnellstmöglich um Ihr Anliegen.\n\nWenn Sie auf diese E-Mail antworten, wird Ihre Nachricht automatisch Ihrem Ticket zugeordnet."],
        'resolved' => ['Problem behoben', "Guten Tag {requester_name},\n\nIhr Anliegen „{ticket_subject}“ (Ticket {ticket_id}) wurde gelöst und das Ticket geschlossen.\n\nSollte das Problem weiterhin bestehen, antworten Sie einfach auf diese E-Mail."],
        'status_change' => ['Statuswechsel', "Guten Tag {requester_name},\n\nder Status Ihres Tickets {ticket_id} „{ticket_subject}“ hat sich geändert. Neuer Status: {ticket_status}."],
    ];

    protected $fillable = [
        'team_id', 'accent_color', 'font', 'logo_path', 'header_text', 'signature', 'footer_text',
        'acknowledgement_enabled', 'acknowledgement_text', 'resolved_enabled', 'resolved_text',
        'status_change_enabled', 'status_change_text', 'error_mail_filter_enabled', 'error_mail_senders',
    ];

    protected $attributes = [
        'accent_color' => self::DEFAULT_COLOR,
        'font' => 'arial',
        'acknowledgement_enabled' => true,
        'resolved_enabled' => true,
        'status_change_enabled' => false,
        'error_mail_filter_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'acknowledgement_enabled' => 'boolean',
            'resolved_enabled' => 'boolean',
            'status_change_enabled' => 'boolean',
            'error_mail_filter_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return array<string, string> placeholder => value
     */
    public static function placeholderValues(Ticket $ticket, ?User $agent): array
    {
        return [
            '{agent_name}' => (string) $agent?->name,
            '{team_name}' => (string) $ticket->team?->name,
            '{mailbox_email}' => (string) $ticket->mailbox?->email_address,
            '{ticket_id}' => (string) $ticket->id,
            '{ticket_subject}' => (string) $ticket->subject,
            '{ticket_status}' => Ticket::STATUS_LABELS[$ticket->status] ?? (string) $ticket->status,
            '{requester_name}' => (string) ($ticket->requester_name ?: $ticket->customer?->name),
        ];
    }

    public static function forTeam(Team $team): self
    {
        return self::query()->firstOrNew(['team_id' => $team->id]);
    }

    public function autoMailEnabled(string $type): bool
    {
        return (bool) $this->{$type.'_enabled'};
    }

    public function autoMailText(string $type): string
    {
        return (string) ($this->{$type.'_text'} ?? self::AUTO_MAILS[$type][1]);
    }

    public function fontStack(): string
    {
        return (self::FONTS[$this->font] ?? self::FONTS['arial'])[1];
    }

    /**
     * @param  array<string, string>  $values  placeholder => value
     */
    private function renderField(string $field, array $values): string
    {
        return strtr((string) $this->{$field}, $values);
    }

    /**
     * @param  array<string, string>  $values  placeholder => value
     * @return array{header: string, signature: string, footer: string}
     */
    public function renderTexts(array $values): array
    {
        return [
            'header' => $this->renderField('header_text', $values),
            'signature' => $this->renderField('signature', $values),
            'footer' => $this->renderField('footer_text', $values),
        ];
    }

    /**
     * Embedded as CID attachment: remote images are blocked by many clients
     * and would let the recipient's mail client call home (tracking).
     */
    public function embeddedLogo(Message $message): ?string
    {
        if (! $this->logo_path || ! Storage::disk('local')->exists($this->logo_path)) {
            return null;
        }

        return $message->embedData(Storage::disk('local')->get($this->logo_path), basename($this->logo_path),
            Storage::disk('local')->mimeType($this->logo_path) ?: 'image/png');
    }

    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('local')->exists($this->logo_path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($this->logo_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($this->logo_path));
    }
}

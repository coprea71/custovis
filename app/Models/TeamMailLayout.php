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
    ];

    protected $fillable = ['team_id', 'accent_color', 'font', 'logo_path', 'header_text', 'signature', 'footer_text'];

    protected $attributes = ['accent_color' => self::DEFAULT_COLOR, 'font' => 'arial'];

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public static function forTeam(Team $team): self
    {
        return self::query()->firstOrNew(['team_id' => $team->id]);
    }

    public function fontStack(): string
    {
        return (self::FONTS[$this->font] ?? self::FONTS['arial'])[1];
    }

    /**
     * @param  array<string, string>  $values  placeholder => value
     */
    public function renderSignature(array $values): string
    {
        return strtr((string) $this->signature, $values);
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

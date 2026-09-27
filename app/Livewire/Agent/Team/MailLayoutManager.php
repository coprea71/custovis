<?php

namespace App\Livewire\Agent\Team;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\TeamMailLayout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Team admins design their outgoing ticket mails; holders of team.manage
 * see the settings read-only — same pattern as the other team settings.
 */
#[Layout('layouts.agent')]
class MailLayoutManager extends Component
{
    use WithFileUploads;

    #[Locked]
    public Team $team;

    #[Locked]
    public bool $readOnly = true;

    public string $accent_color = TeamMailLayout::DEFAULT_COLOR;

    public string $font = 'arial';

    public string $header_text = '';

    public string $signature = '';

    public string $footer_text = '';

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    public ?string $status = null;

    public function mount(Team $team): void
    {
        $user = Auth::user();
        abort_unless($user->isTeamAdminOf($team) || $user->can('team.manage'), 403);

        $this->team = $team;
        $this->readOnly = ! $user->isTeamAdminOf($team);
        $layout = TeamMailLayout::forTeam($team);
        foreach (['accent_color', 'font', 'header_text', 'signature', 'footer_text'] as $field) {
            $this->{$field} = (string) $layout->{$field};
        }
    }

    public function save(): void
    {
        abort_if($this->readOnly, 403);
        $data = $this->validate($this->rules());
        $layout = TeamMailLayout::forTeam($this->team);

        $layout->fill(array_map(fn ($value) => is_string($value) && trim($value) === '' ? null : $value, collect($data)->except('logo')->all()));
        if ($this->logo) {
            $this->replaceLogo($layout, $this->logo->store('mail-logos', 'local'));
        }
        $changed = array_keys($layout->getDirty());
        $layout->save();

        AuditLog::record('team.mail_layout_updated', Auth::user(), $this->team, $layout, ['fields_changed' => $changed]);
        $this->reset('logo');
        $this->status = 'E-Mail-Layout gespeichert.';
    }

    public function removeLogo(): void
    {
        abort_if($this->readOnly, 403);
        $layout = TeamMailLayout::forTeam($this->team);
        $this->replaceLogo($layout, null);
        $layout->save();

        $this->status = 'Logo entfernt.';
    }

    public function render()
    {
        return view('livewire.agent.team.mail-layout-manager', [
            'preview' => $this->preview(),
            'hasLogo' => (bool) TeamMailLayout::forTeam($this->team)->logo_path,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font' => ['required', Rule::in(array_keys(TeamMailLayout::FONTS))],
            'header_text' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'string', 'max:2000'],
            'footer_text' => ['nullable', 'string', 'max:2000'],
            // No SVG: it can carry script and many mail clients do not render it.
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif', 'max:200', 'dimensions:max_width=1200,max_height=600'],
        ];
    }

    private function replaceLogo(TeamMailLayout $layout, ?string $path): void
    {
        if ($layout->logo_path) {
            Storage::disk('local')->delete($layout->logo_path);
        }
        $layout->logo_path = $path;
    }

    private function preview(): string
    {
        $layout = TeamMailLayout::forTeam($this->team)->fill([
            'accent_color' => preg_match('/^#[0-9A-Fa-f]{6}$/', $this->accent_color) ? $this->accent_color : TeamMailLayout::DEFAULT_COLOR,
            'font' => $this->font,
            'header_text' => $this->header_text,
            'signature' => Str::limit($this->signature, 2000, ''),
            'footer_text' => Str::limit($this->footer_text, 2000, ''),
        ]);

        return view('mail.ticket-reply', [
            'layout' => $layout,
            'logoSrc' => $layout->logoDataUri(),
            'body' => '<p>Guten Tag Frau Muster,</p><p>vielen Dank für Ihre Nachricht. Wir haben das Problem behoben.</p>',
            'signature' => $layout->renderSignature([
                '{agent_name}' => Auth::user()->name,
                '{team_name}' => $this->team->name,
                '{mailbox_email}' => 'support@example.com',
                '{ticket_id}' => '1234',
            ]),
        ])->render();
    }
}

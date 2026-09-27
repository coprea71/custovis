<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\Team\MailLayoutManager;
use App\Mail\TicketReplyMail;
use App\Models\AuditLog;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\TeamMailLayout;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MailLayoutManagerTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
    }

    public function test_team_admin_saves_layout_with_logo(): void
    {
        $admin = $this->member('team_admin');

        Livewire::actingAs($admin)->test(MailLayoutManager::class, ['team' => $this->team])
            ->set('accent_color', '#AA3300')->set('font', 'georgia')
            ->set('signature', 'Viele Grüße, {agent_name}')->set('footer_text', 'Muster GmbH')
            ->set('logo', UploadedFile::fake()->image('logo.png', 200, 80)->size(20))
            ->call('save')->assertHasNoErrors()
            ->assertSee('Viele Grüße, Anna');

        $layout = TeamMailLayout::forTeam($this->team);
        $this->assertSame('#AA3300', $layout->accent_color);
        $this->assertSame('georgia', $layout->font);
        Storage::disk('local')->assertExists($layout->logo_path);
        $this->assertTrue(AuditLog::query()->where('action', 'team.mail_layout_updated')->exists());
    }

    public function test_inputs_are_whitelist_validated(): void
    {
        Livewire::actingAs($this->member('team_admin'))->test(MailLayoutManager::class, ['team' => $this->team])
            ->set('accent_color', 'red;background:url(x)')->set('font', 'comic')
            ->set('logo', UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'))
            ->call('save')->assertHasErrors(['accent_color', 'font', 'logo']);

        $this->assertFalse(TeamMailLayout::query()->exists());
    }

    public function test_plain_members_are_denied_and_team_managers_read_only(): void
    {
        Livewire::actingAs($this->member('member'))->test(MailLayoutManager::class, ['team' => $this->team])->assertForbidden();

        $manager = User::factory()->create();
        $manager->assignRole('system_admin');
        Livewire::actingAs($manager)->test(MailLayoutManager::class, ['team' => $this->team])
            ->assertSet('readOnly', true)->call('save')->assertForbidden();
    }

    public function test_reply_mail_uses_team_layout_with_escaped_signature_and_embedded_logo(): void
    {
        config(['mail.default' => 'array']);
        Storage::disk('local')->put('mail-logos/logo.png', UploadedFile::fake()->image('logo.png', 10, 10)->getContent());
        TeamMailLayout::query()->create(['team_id' => $this->team->id, 'accent_color' => '#AA3300', 'logo_path' => 'mail-logos/logo.png',
            'signature' => "{agent_name}\n{team_name} <b>fett</b>", 'footer_text' => 'Muster GmbH']);
        $message = $this->reply();

        Mail::to('kunde@example.com')->send(new TicketReplyMail($message));

        $sent = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
        $html = $sent->getHtmlBody();
        $this->assertStringContainsString('#AA3300', $html);
        $this->assertStringContainsString('Anna<br />', $html);
        $this->assertStringContainsString('Support &lt;b&gt;fett&lt;/b&gt;', $html);
        $this->assertStringContainsString('src="cid:', $html);
        $this->assertStringContainsString("-- \nAnna\nSupport <b>fett</b>", $sent->getTextBody());
        $this->assertStringContainsString('Muster GmbH', $sent->getTextBody());
    }

    public function test_reply_mail_without_layout_has_no_signature_block(): void
    {
        $html = (new TicketReplyMail($this->reply()))->render();

        $this->assertStringContainsString('Wir kümmern uns darum.', $html);
        $this->assertStringNotContainsString('cid:', $html);
    }

    private function member(string $role): User
    {
        $user = User::factory()->create(['name' => 'Anna']);
        $this->team->users()->attach($user, ['role_in_team' => $role]);

        return $user;
    }

    private function reply(): TicketMessage
    {
        $mailbox = Mailbox::factory()->create(['team_id' => $this->team->id]);
        $ticket = Ticket::query()->create(['team_id' => $this->team->id, 'mailbox_id' => $mailbox->id, 'source' => 'mailbox',
            'subject' => 'Druckerproblem', 'requester_email' => 'kunde@example.com']);

        return $ticket->messages()->create(['visibility' => TicketMessage::VISIBILITY_PUBLIC, 'direction' => 'outgoing',
            'author_user_id' => $this->member('member')->id, 'body_text' => 'Wir kümmern uns darum.']);
    }
}

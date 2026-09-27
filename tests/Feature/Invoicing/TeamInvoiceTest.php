<?php

namespace Tests\Feature\Invoicing;

use App\Livewire\Admin\Invoicing\InvoiceSettingsManager;
use App\Livewire\Agent\Team\Invoicing\InvoiceEditor;
use App\Livewire\Agent\Team\Invoicing\InvoiceManager;
use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Module;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Invoicing\InvoiceSettings;
use App\Services\ModuleAccess;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TeamInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $teamAdmin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        $this->seed([RolesAndPermissionsSeeder::class, ModuleSeeder::class]);
        Module::query()->whereIn('slug', ['invoicing', 'time-tracking'])->update(['enabled' => true]);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->teamAdmin = User::factory()->create();
        $this->team->users()->attach($this->teamAdmin, ['role_in_team' => 'team_admin']);
        $this->customer = Customer::factory()->create(['name' => 'Erika Muster', 'email' => 'erika@example.com',
            'street' => 'Hauptstr. 1', 'postal_code' => '10115', 'city' => 'Berlin']);
        app(InvoiceSettings::class)->save(InvoiceServiceTest::seller());
    }

    public function test_settings_are_whitelist_validated_and_saved(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');

        Livewire::actingAs($admin)->test(InvoiceSettingsManager::class)
            ->set('settings.iban', 'kein iban')->set('settings.vat_id', 'DE 12<script>')->set('settings.number_prefix', 'RE/../')
            ->call('save')->assertHasErrors(['settings.iban', 'settings.vat_id', 'settings.number_prefix'])
            ->set('settings.iban', 'de02 1203 0000 0000 2020 51')->set('settings.vat_id', 'DE123456789')->set('settings.number_prefix', 'RE-')
            ->call('save')->assertHasNoErrors();

        $this->assertSame('DE02120300000000202051', app(InvoiceSettings::class)->get('iban'));
    }

    public function test_team_admin_runs_full_workflow_from_time_to_sent_paid_and_cancelled_invoice(): void
    {
        Mail::fake();
        $ticket = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Drucker', 'customer_id' => $this->customer->id]);
        $ticket->timeEntries()->create(['user_id' => $this->teamAdmin->id, 'work_date' => today(), 'minutes' => 45]);

        Livewire::actingAs($this->teamAdmin)->test(InvoiceManager::class, ['team' => $this->team])
            ->set('customerId', $this->customer->id)->call('createDraft')->assertRedirect();
        $invoice = Invoice::query()->sole();
        $this->assertSame([$this->team->id, 1], [$invoice->team_id, $invoice->items()->count()]);

        Livewire::actingAs($this->teamAdmin)->test(InvoiceEditor::class, ['team' => $this->team, 'invoice' => $invoice])
            ->set('itemDescription', 'Anfahrt')->set('itemQuantity', '1')->set('itemUnit', 'C62')->set('itemPrice', '35,50')
            ->call('addItem')->assertHasNoErrors()
            ->call('issue')->assertHasNoErrors()
            ->call('send')->assertHasNoErrors()
            ->call('markPaid');

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo('erika@example.com') && count($mail->attachments()) === 2);
        $invoice->refresh();
        $this->assertSame(9550, $invoice->net_cents);
        $this->assertNotNull($invoice->sent_at);
        $this->assertNotNull($invoice->paid_at);

        $this->actingAs($this->teamAdmin)->get(route('agent.team.invoices.file', [$this->team, $invoice, 'pdf']))->assertOk()->assertDownload($invoice->number.'.pdf');

        Livewire::actingAs($this->teamAdmin)->test(InvoiceEditor::class, ['team' => $this->team, 'invoice' => $invoice->fresh()])->call('cancel')->assertRedirect();
        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->fresh()->status);
    }

    public function test_collective_invoices_are_created_for_the_period(): void
    {
        $ticket = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Wartung', 'customer_id' => $this->customer->id]);
        $ticket->timeEntries()->create(['user_id' => $this->teamAdmin->id, 'work_date' => today()->subMonthNoOverflow()->startOfMonth(), 'minutes' => 60]);

        Livewire::actingAs($this->teamAdmin)->test(InvoiceManager::class, ['team' => $this->team])
            ->call('createCollective')->assertHasNoErrors()
            ->assertSee('1 Sammelrechnung(en) als Entwurf angelegt');

        $this->assertSame(1, Invoice::query()->where('team_id', $this->team->id)->count());
    }

    public function test_only_team_admins_create_invoices(): void
    {
        $member = User::factory()->create();
        $this->team->users()->attach($member, ['role_in_team' => 'member']);
        $this->actingAs($member)->get(route('agent.team.invoices', $this->team))->assertForbidden();

        $systemAdmin = User::factory()->create();
        $systemAdmin->assignRole('system_admin');
        Livewire::actingAs($systemAdmin)->test(InvoiceManager::class, ['team' => $this->team])
            ->assertSet('readOnly', true)->assertDontSee('Entwurf anlegen')
            ->set('customerId', $this->customer->id)->call('createDraft')->assertForbidden();

        $otherAdmin = User::factory()->create();
        Team::query()->create(['name' => 'Ops', 'slug' => 'ops'])->users()->attach($otherAdmin, ['role_in_team' => 'team_admin']);
        $this->actingAs($otherAdmin)->get(route('agent.team.invoices', $this->team))->assertForbidden();

        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_invoice_of_another_team_is_not_reachable(): void
    {
        $foreign = Invoice::query()->create(['team_id' => Team::query()->create(['name' => 'Ops', 'slug' => 'ops'])->id, 'customer_id' => $this->customer->id]);

        $this->actingAs($this->teamAdmin)->get(route('agent.team.invoices.show', [$this->team, $foreign]))->assertNotFound();
    }

    public function test_issued_invoice_cannot_be_edited_through_the_editor(): void
    {
        $invoice = Invoice::query()->create(['team_id' => $this->team->id, 'customer_id' => $this->customer->id]);

        Livewire::actingAs($this->teamAdmin)->test(InvoiceEditor::class, ['team' => $this->team, 'invoice' => $invoice])
            ->set('itemDescription', 'Leistung')->set('itemPrice', '10')->call('addItem')->call('issue');

        Livewire::actingAs($this->teamAdmin)->test(InvoiceEditor::class, ['team' => $this->team, 'invoice' => $invoice->fresh()])
            ->set('itemDescription', 'Nachträglich')->set('itemPrice', '10')->call('addItem')->assertStatus(422);
    }

    public function test_disabled_module_hides_invoices(): void
    {
        Module::query()->where('slug', 'invoicing')->update(['enabled' => false]);
        app(ModuleAccess::class)->flush();

        $this->actingAs($this->teamAdmin)->get(route('agent.team.invoices', $this->team))->assertNotFound();
    }
}

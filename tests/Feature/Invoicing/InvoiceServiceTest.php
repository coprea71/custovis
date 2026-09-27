<?php

namespace Tests\Feature\Invoicing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\Invoicing\InvoiceService;
use App\Services\Invoicing\InvoiceSettings;
use horstoeko\zugferd\ZugferdDocumentPdfReader;
use horstoeko\zugferd\ZugferdDocumentReader;
use horstoeko\zugferd\ZugferdXsdValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;

    private User $admin;

    private Customer $customer;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 9, 27));
        $this->service = app(InvoiceService::class);
        $this->admin = User::factory()->create();
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->customer = Customer::factory()->create(['name' => 'Erika Muster', 'company' => 'Muster GmbH', 'email' => 'buchhaltung@muster.example',
            'street' => 'Hauptstr. 1', 'postal_code' => '10115', 'city' => 'Berlin', 'buyer_reference' => '991-12345-67']);
        app(InvoiceSettings::class)->save(self::seller());
    }

    /**
     * @return array<string, string>
     */
    public static function seller(): array
    {
        return ['company' => 'Service GmbH', 'street' => 'Am Markt 2', 'postal_code' => '20095', 'city' => 'Hamburg',
            'vat_id' => 'DE123456789', 'contact_name' => 'Max Service', 'email' => 'rechnung@service.example',
            'phone' => '040 123456', 'iban' => 'DE02120300000000202051', 'bic' => 'BYLADEM1001', 'hourly_rate' => '80.00'];
    }

    public function test_open_time_is_imported_per_ticket_and_locked(): void
    {
        $ticket = $this->ticketWithTime([90, 30]);
        $this->ticketWithTime([60], Customer::factory()->create());

        $invoice = $this->service->createDraft($this->customer, $this->team, $this->admin);
        $this->assertSame(2, $this->service->importOpenTime($invoice));

        $item = $invoice->items()->sole();
        $this->assertSame('2.00', $item->quantity);
        $this->assertSame(16000, $item->net_cents);
        $this->assertStringContainsString("Ticket #{$ticket->id}", $item->description);
        $this->assertSame([16000, 3040, 19040], [$invoice->fresh()->net_cents, $invoice->fresh()->tax_cents, $invoice->fresh()->gross_cents]);
        $this->assertSame(0, TimeEntry::query()->openForBilling()->whereIn('ticket_id', [$ticket->id])->count());

        $this->service->removeItem($item);
        $this->assertSame(2, TimeEntry::query()->openForBilling()->where('ticket_id', $ticket->id)->count());
    }

    public function test_collective_invoices_bundle_a_period_per_customer_and_team(): void
    {
        $first = $this->ticketWithTime([60], date: '2026-08-05');
        $second = $this->ticketWithTime([30], date: '2026-08-20');
        $this->ticketWithTime([45], date: '2026-09-02');
        $this->ticketWithTime([15], Customer::factory()->create(), date: '2026-08-10');
        $this->ticketWithTime([90], team: Team::query()->create(['name' => 'Ops', 'slug' => 'ops']), date: '2026-08-10');

        $count = $this->service->createCollectiveDrafts($this->team, now()->setDate(2026, 8, 1), now()->setDate(2026, 8, 31), $this->admin);

        $this->assertSame(2, $count);
        $invoice = Invoice::query()->where('customer_id', $this->customer->id)->sole();
        $this->assertSame([$first->id, $second->id], $invoice->items()->pluck('ticket_id')->all());
        $this->assertSame(['2026-08-01', '2026-08-31'], [$invoice->service_from->toDateString(), $invoice->service_to->toDateString()]);
        $this->assertSame(12000, $invoice->net_cents);
        $this->assertSame(2, TimeEntry::query()->openForBilling()->count(), 'September time and the other team stay open');
    }

    public function test_issue_assigns_consecutive_numbers_and_valid_e_invoice_files(): void
    {
        $first = $this->issuedInvoice();
        $second = $this->issuedInvoice();

        $this->assertSame('RE-2026-00001', $first->number);
        $this->assertSame('RE-2026-00002', $second->number);
        $this->assertSame('2026-10-11', $first->due_date->toDateString());

        $xml = Storage::disk('local')->get($first->xml_path);
        $validator = (new ZugferdXsdValidator(ZugferdDocumentReader::readAndGuessFromContent($xml)))->validate();
        $this->assertTrue($validator->validationPased(), implode("\n", $validator->validationErrors()));
        $this->assertStringContainsString('urn:xeinkauf.de:kosit:xrechnung_3.0', $xml);
        $this->assertStringContainsString('991-12345-67', $xml);

        $embedded = ZugferdDocumentPdfReader::getXmlFromContent(Storage::disk('local')->get($first->pdf_path));
        $this->assertStringContainsString('urn:cen.eu:en16931:2017', $embedded);
        $this->assertStringContainsString('RE-2026-00001', $embedded);
    }

    public function test_issued_invoice_is_immutable_and_undeletable(): void
    {
        $invoice = $this->issuedInvoice();

        $this->assertThrows(fn () => $invoice->forceFill(['net_cents' => 1])->save(), LogicException::class);
        $this->assertThrows(fn () => $invoice->fresh()->delete(), LogicException::class);
        $this->assertThrows(fn () => $invoice->items()->first()->update(['description' => 'x']), LogicException::class);
    }

    public function test_cancellation_references_original_and_releases_time(): void
    {
        $ticket = $this->ticketWithTime([60]);
        $invoice = $this->service->createDraft($this->customer, $this->team, $this->admin);
        $this->service->importOpenTime($invoice);
        $this->service->issue($invoice, $this->admin);

        $cancellation = $this->service->cancel($invoice, $this->admin);

        $this->assertSame(Invoice::STATUS_CANCELLED, $invoice->fresh()->status);
        $this->assertSame('RE-2026-00002', $cancellation->number);
        $this->assertSame('381', $cancellation->documentTypeCode());
        $this->assertStringContainsString('RE-2026-00001', Storage::disk('local')->get($cancellation->xml_path));
        $this->assertSame(1, TimeEntry::query()->openForBilling()->where('ticket_id', $ticket->id)->count());
    }

    public function test_small_business_invoices_carry_no_vat(): void
    {
        app(InvoiceSettings::class)->save(['small_business' => '1', 'vat_id' => '', 'tax_number' => '12/345/67890']);
        $invoice = $this->issuedInvoice();

        $this->assertSame(0, $invoice->tax_cents);
        $this->assertStringContainsString('§ 19 UStG', Storage::disk('local')->get($invoice->xml_path));
    }

    public function test_issue_requires_seller_data_customer_address_and_items(): void
    {
        app(InvoiceSettings::class)->save(['iban' => '']);
        $this->customer->update(['street' => null]);
        $invoice = $this->service->createDraft($this->customer, $this->team, $this->admin);

        try {
            $this->service->issue($invoice, $this->admin);
            $this->fail('Issue must be rejected.');
        } catch (ValidationException $e) {
            $messages = implode(' ', $e->errors()['issue']);
            $this->assertStringContainsString('IBAN', $messages);
            $this->assertStringContainsString('Anschrift', $messages);
            $this->assertStringContainsString('keine Positionen', $messages);
        }

        $this->assertNull($invoice->fresh()->number);
    }

    private function issuedInvoice(): Invoice
    {
        $invoice = $this->service->createDraft($this->customer, $this->team, $this->admin);
        $this->service->addItem($invoice, 'Einrichtung Arbeitsplatz', 1.5, 'HUR', 8000);

        return $this->service->issue($invoice, $this->admin);
    }

    /**
     * @param  list<int>  $minutes
     */
    private function ticketWithTime(array $minutes, ?Customer $customer = null, ?Team $team = null, ?string $date = null): Ticket
    {
        $ticket = Ticket::query()->create(['team_id' => ($team ?? $this->team)->id, 'source' => 'api', 'subject' => 'Drucker', 'customer_id' => ($customer ?? $this->customer)->id]);
        foreach ($minutes as $value) {
            $ticket->timeEntries()->create(['user_id' => $this->admin->id, 'work_date' => $date ?? today(), 'minutes' => $value]);
        }

        return $ticket;
    }
}

<?php

namespace Tests\Feature\FieldService;

use App\Livewire\Agent\DispatchBoard;
use App\Models\AppointmentChecklist;
use App\Models\ServiceAppointment;
use App\Models\Skill;
use App\Models\Team;
use App\Models\TechnicianProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Services\FieldService\AppointmentService;
use App\Services\FieldService\DispatchAssignmentService;
use App\Services\FieldService\RoutingService;
use App\States\Appointment\Cancelled;
use App\States\Appointment\Completed;
use App\States\Appointment\Proposed;
use App\States\Appointment\Scheduled;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class FieldServiceTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private Team $team;

    private Ticket $ticket;

    private User $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Carbon::setTestNow('2026-09-28 07:00:00'); // Monday
        $this->team = Team::query()->create(['name' => 'Service', 'slug' => 'service']);
        $this->ticket = Ticket::query()->create(['team_id' => $this->team->id, 'type' => 'incident', 'source' => 'api', 'subject' => 'Heizung ausgefallen']);
        $this->dispatcher = User::factory()->create();
        $this->dispatcher->givePermissionTo('dispatch.manage');
    }

    public function test_drag_and_drop_assignment_creates_scheduled_appointment(): void
    {
        $technician = $this->technician('Tina');
        $appointment = $this->appointment();

        Livewire::actingAs($this->dispatcher)->test(DispatchBoard::class)
            ->call('assign', $appointment->id, $technician->id)
            ->assertHasNoErrors();

        $appointment->refresh();
        $this->assertTrue($appointment->state->equals(Scheduled::class));
        $this->assertSame($technician->id, $appointment->technician_profile_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.assigned', 'user_id' => $this->dispatcher->id]);
    }

    public function test_assignment_is_refused_outside_shift_or_during_absence(): void
    {
        $technician = $this->technician('Tina');
        $technician->absences()->create(['starts_on' => '2026-09-28', 'ends_on' => '2026-09-30', 'reason' => 'vacation']);

        Livewire::actingAs($this->dispatcher)->test(DispatchBoard::class)
            ->call('assign', $this->appointment()->id, $technician->id)
            ->assertHasErrors('board');
    }

    public function test_engine_ranks_technician_with_required_skill_first(): void
    {
        $heating = Skill::query()->create(['name' => 'Heizung']);
        $withoutSkill = $this->technician('Ohne Skill', homeLat: 52.52, homeLng: 13.40);
        $withSkill = $this->technician('Mit Skill', homeLat: 52.60, homeLng: 13.50);
        $withSkill->skills()->attach($heating, ['level' => 4]);

        $ranking = app(DispatchAssignmentService::class)->rank($this->appointment(['required_skill_ids' => [$heating->id]]));

        $this->assertSame('Mit Skill', $ranking->first()['technician']->user->name);
        $this->assertGreaterThan($ranking->last()['score'], $ranking->first()['score']);
    }

    public function test_offline_queue_syncs_checklist_signature_and_status(): void
    {
        Storage::fake('local');
        $technician = $this->technician('Tina');
        $appointment = $this->scheduledAppointment($technician, withChecklist: true);
        $item = $appointment->checklists->first()->items->first();

        $this->syncAs($technician, [
            $this->op('status', $appointment, ['from' => 'scheduled', 'to' => 'on_site']),
            $this->op('checklist_item', $appointment, ['item_id' => $item->id, 'checked' => true]),
            $this->op('signature', $appointment, ['signer_name' => 'Frau Kunde', 'png' => self::PNG]),
            $this->op('status', $appointment, ['from' => 'on_site', 'to' => 'completed']),
        ])->assertStatus(202);

        $appointment->refresh();
        $this->assertTrue($appointment->state->equals(Completed::class));
        $this->assertTrue($item->fresh()->checked);
        $this->assertSame('Frau Kunde', $appointment->signatures()->first()->signer_name);
        Storage::disk('local')->assertExists($appointment->signatures()->first()->path);
    }

    public function test_server_wins_on_conflicting_status_and_retries_are_idempotent(): void
    {
        $technician = $this->technician('Tina');
        $appointment = $this->scheduledAppointment($technician);
        $appointment->state->transitionTo(Cancelled::class); // dispatcher cancelled while technician was offline

        $operation = $this->op('status', $appointment, ['from' => 'scheduled', 'to' => 'on_site']);
        $this->syncAs($technician, [$operation])->assertStatus(202);
        $this->syncAs($technician, [$operation])->assertStatus(202);

        $this->assertTrue($appointment->fresh()->state->equals(Cancelled::class));
        $this->assertDatabaseHas('field_sync_operations', ['operation_uuid' => $operation['id'], 'result' => 'conflict']);
        $this->assertDatabaseCount('field_sync_operations', 1);
    }

    public function test_technician_cannot_touch_foreign_appointments(): void
    {
        $tina = $this->technician('Tina');
        $foreign = $this->scheduledAppointment($this->technician('Tom'));

        $this->syncAs($tina, [$this->op('status', $foreign, ['from' => 'scheduled', 'to' => 'on_site'])])->assertStatus(202);

        $this->assertTrue($foreign->fresh()->state->equals(Scheduled::class));
        $this->assertDatabaseHas('field_sync_operations', ['result' => 'rejected']);
    }

    public function test_delivery_with_receipt_creates_signature_and_pdf_attachment(): void
    {
        Storage::fake('local');
        $technician = $this->technician('Tina');
        $appointment = $this->scheduledAppointment($technician, ['kind' => 'delivery']);

        $this->syncAs($technician, [
            $this->op('part', $appointment, ['description' => 'Umwälzpumpe', 'quantity' => 1]),
            $this->op('delivery', $appointment, [
                'delivery_note_number' => 'LS-2026-0042', 'recipient_name' => 'Max Empfänger',
                'recipient_email' => 'max@example.com', 'signature' => self::PNG,
            ]),
        ])->assertStatus(202);

        $delivery = $appointment->fresh()->delivery;
        $this->assertSame('Max Empfänger', $delivery->recipient_name);
        $this->assertSame('delivery', $appointment->signatures()->first()->context);
        $this->assertSame('application/pdf', $delivery->pdfAttachment->mime_type);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($delivery->pdfAttachment->path));
        $this->assertSame($this->ticket->id, $delivery->pdfAttachment->message->ticket_id);
    }

    public function test_today_endpoint_and_field_app_require_technician_permission(): void
    {
        $technician = $this->technician('Tina');
        $this->scheduledAppointment($technician);

        $this->actingAs($technician->user)->getJson('/field/today')->assertOk()
            ->assertJsonPath('appointments.0.state', 'scheduled')
            ->assertJsonPath('appointments.0.next_states.0.key', 'proposed');
        $this->actingAs($this->dispatcher)->getJson('/field/today')->assertForbidden();
        $this->actingAs($technician->user)->get('/field')->assertOk()->assertSee('field-manifest.webmanifest', false);
        $this->actingAs($this->dispatcher)->get('/agent/dispatch')->assertOk()->assertSee('Heizung ausgefallen');
        $this->actingAs($technician->user)->get('/agent/dispatch')->assertForbidden();
    }

    public function test_routing_service_uses_osrm_and_validates_answers(): void
    {
        config(['custovis.field_service.osrm_url' => 'https://osrm.example.test', 'custovis.field_service.nominatim_url' => 'https://nominatim.example.test']);
        Http::fake([
            'osrm.example.test/*' => Http::response(['routes' => [['distance' => 12345.6, 'duration' => 900.4]]]),
            'nominatim.example.test/*' => Http::response([['lat' => '999', 'lon' => '13.4']]),
        ]);
        $routing = app(RoutingService::class);

        $this->assertSame(['meters' => 12346, 'seconds' => 900, 'source' => 'osrm'], $routing->distance(52.52, 13.40, 52.40, 13.05));
        $this->assertNull($routing->geocode('Unter den Linden 1, Berlin'), 'out-of-range latitude must be rejected');
    }

    private function technician(string $name, float $homeLat = 52.5, float $homeLng = 13.4): TechnicianProfile
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('technician');
        $this->team->users()->attach($user, ['role_in_team' => 'member']);
        $profile = TechnicianProfile::query()->create(['user_id' => $user->id, 'home_lat' => $homeLat, 'home_lng' => $homeLng]);
        $profile->shifts()->create(['weekday' => 1, 'starts_at' => '08:00:00', 'ends_at' => '17:00:00']);

        return $profile;
    }

    private function appointment(array $attributes = []): ServiceAppointment
    {
        return $this->ticket->appointments()->create($attributes + [
            'state' => Proposed::class,
            'scheduled_start' => '2026-09-28 10:00:00',
            'scheduled_end' => '2026-09-28 11:00:00',
            'address' => 'Musterstraße 1, Berlin',
            'lat' => 52.45,
            'lng' => 13.30,
        ]);
    }

    private function scheduledAppointment(TechnicianProfile $technician, array $attributes = [], bool $withChecklist = false): ServiceAppointment
    {
        $appointment = $this->appointment($attributes);

        if ($withChecklist) {
            $template = AppointmentChecklist::query()->create(['name' => 'Wartung']);
            $template->items()->create(['label' => 'Druck geprüft', 'position' => 0]);
            app(AppointmentService::class)->copyChecklist($appointment, $template);
        }

        $this->assertNull(app(AppointmentService::class)->assign($appointment, $technician, $this->dispatcher));

        return $appointment->fresh(['checklists.items']);
    }

    private function op(string $type, ServiceAppointment $appointment, array $payload): array
    {
        return ['id' => (string) Str::uuid(), 'type' => $type, 'appointment_id' => $appointment->id, 'payload' => $payload];
    }

    private function syncAs(TechnicianProfile $technician, array $operations)
    {
        return $this->actingAs($technician->user)->postJson('/field/sync', ['operations' => $operations]);
    }
}

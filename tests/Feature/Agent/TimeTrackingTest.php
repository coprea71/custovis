<?php

namespace Tests\Feature\Agent;

use App\Livewire\Agent\TicketTimeTracking;
use App\Livewire\Agent\TicketWorkspace;
use App\Models\Module;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ModuleAccess;
use App\Support\Duration;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RolesAndPermissionsSeeder::class, ModuleSeeder::class]);
        Module::query()->where('slug', 'time-tracking')->update(['enabled' => true]);
        $this->team = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->agent = User::factory()->create(['name' => 'Anna']);
        $this->agent->assignRole('agent');
        $this->team->users()->attach($this->agent);
        $this->ticket = Ticket::query()->create(['team_id' => $this->team->id, 'source' => 'api', 'subject' => 'Drucker']);
    }

    public function test_duration_accepts_hours_and_minutes(): void
    {
        $this->assertSame(90, Duration::parse('1:30'));
        $this->assertSame(45, Duration::parse('45'));
        $this->assertNull(Duration::parse('1:75'));
        $this->assertNull(Duration::parse('abc'));
        $this->assertSame('2:05', Duration::format(125));
    }

    public function test_agent_books_time_manually(): void
    {
        Livewire::actingAs($this->agent)->test(TicketTimeTracking::class, ['ticketId' => $this->ticket->id])
            ->set('duration', '1:30')->set('description', 'Treiber installiert')
            ->call('save')->assertHasNoErrors()
            ->assertSee('1:30 h abrechenbar')
            ->set('duration', '15')->set('billable', false)->call('save')
            ->assertSee('0:15 h intern');

        $this->assertSame(105, (int) TimeEntry::query()->sum('minutes'));
        $this->assertSame($this->agent->id, TimeEntry::query()->first()->user_id);
    }

    public function test_invalid_or_future_input_is_rejected(): void
    {
        Livewire::actingAs($this->agent)->test(TicketTimeTracking::class, ['ticketId' => $this->ticket->id])
            ->set('duration', '1h30')->set('workDate', today()->addDay()->toDateString())
            ->call('save')->assertHasErrors(['duration', 'workDate']);

        $this->assertSame(0, TimeEntry::query()->count());
    }

    public function test_timer_books_elapsed_minutes_and_only_one_runs_per_user(): void
    {
        $this->travelTo(now()->setTime(9, 0));
        $component = Livewire::actingAs($this->agent)->test(TicketTimeTracking::class, ['ticketId' => $this->ticket->id])
            ->call('startTimer')->assertSee('Timer läuft seit 09:00 Uhr');

        $this->travelTo(now()->setTime(9, 20));
        $component->call('startTimer');
        $this->travelTo(now()->setTime(9, 30));
        $component->call('stopTimer');

        $this->assertSame([20, 10], TimeEntry::query()->orderBy('id')->pluck('minutes')->all());
    }

    public function test_foreign_and_invoiced_entries_cannot_be_deleted(): void
    {
        $colleague = User::factory()->create();
        $foreign = $this->ticket->timeEntries()->create(['user_id' => $colleague->id, 'work_date' => today(), 'minutes' => 30]);
        $locked = $this->ticket->timeEntries()->create(['user_id' => $this->agent->id, 'work_date' => today(), 'minutes' => 30]);
        $locked->forceFill(['invoice_item_id' => 99])->save();

        Livewire::actingAs($this->agent)->test(TicketTimeTracking::class, ['ticketId' => $this->ticket->id])
            ->call('delete', $foreign->id)->assertNotFound();
        Livewire::actingAs($this->agent)->test(TicketTimeTracking::class, ['ticketId' => $this->ticket->id])
            ->call('delete', $locked->id)->assertForbidden();

        $this->assertSame(2, TimeEntry::query()->count());
    }

    public function test_tickets_of_other_teams_and_disabled_module_are_denied(): void
    {
        $other = Ticket::query()->create(['team_id' => Team::query()->create(['name' => 'Ops', 'slug' => 'ops'])->id, 'source' => 'api', 'subject' => 'Fremd']);
        Livewire::actingAs($this->agent)->test(TicketTimeTracking::class, ['ticketId' => $other->id])->assertNotFound();

        Module::query()->where('slug', 'time-tracking')->update(['enabled' => false]);
        app(ModuleAccess::class)->flush();
        Livewire::actingAs($this->agent)->test(TicketWorkspace::class, ['ticket' => $this->ticket])->assertDontSee('Zeiterfassung');
    }
}

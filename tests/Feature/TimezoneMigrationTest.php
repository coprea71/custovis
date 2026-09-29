<?php

namespace Tests\Feature;

use App\Models\ServiceAppointment;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimezoneMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_utc_timestamps_move_to_app_timezone_but_wall_clock_appointments_stay(): void
    {
        config(['app.timezone' => 'Europe/Berlin']);
        $team = Team::query()->create(['name' => 'Service', 'slug' => 'service']);
        $summer = $this->ticketCreatedAt($team, '2026-07-01 10:00:00');
        $winter = $this->ticketCreatedAt($team, '2026-01-15 10:00:00');
        $appointment = ServiceAppointment::query()->create([
            'ticket_id' => $summer, 'kind' => 'service', 'address' => 'Berlin',
            'scheduled_start' => '2026-07-01 09:00:00', 'scheduled_end' => '2026-07-01 10:00:00',
        ]);

        $this->migration()->up();

        $this->assertSame('2026-07-01 12:00:00', $this->column('tickets', $summer, 'created_at'));
        $this->assertSame('2026-01-15 11:00:00', $this->column('tickets', $winter, 'created_at'));
        $this->assertSame('2026-07-01 09:00:00', $this->column('service_appointments', $appointment->id, 'scheduled_start'));

        $this->migration()->down();

        $this->assertSame('2026-07-01 10:00:00', $this->column('tickets', $summer, 'created_at'));
    }

    private function ticketCreatedAt(Team $team, string $createdAt): int
    {
        return DB::table('tickets')->insertGetId([
            'team_id' => $team->id, 'type' => 'support_ticket', 'source' => 'api', 'subject' => 'Test',
            'status' => 'open', 'priority' => 'normal', 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);
    }

    private function column(string $table, int $id, string $column): string
    {
        return substr((string) DB::table($table)->where('id', $id)->value($column), 0, 19);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_09_29_140000_convert_utc_timestamps_to_app_timezone.php');
    }
}

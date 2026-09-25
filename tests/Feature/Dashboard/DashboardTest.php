<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Admin\ManagementDashboard;
use App\Livewire\Agent\Team\TeamDashboard;
use App\Models\DashboardSnapshot;
use App\Models\Mailbox;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Dashboard\DashboardSnapshotService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Team $support;

    private Team $ops;

    protected function setUp(): void
    {
        parent::setUp();

        $this->support = Team::query()->create(['name' => 'Support', 'slug' => 'support']);
        $this->ops = Team::query()->create(['name' => 'Ops', 'slug' => 'ops']);

        $this->ticket($this->support, ['type' => 'incident', 'priority' => 'high']);
        $this->ticket($this->support, ['type' => 'incident', 'sla_breached_at' => now()]);
        $this->ticket($this->support, ['status' => 'closed']);
        $this->ticket($this->ops, ['type' => 'problem', 'subject' => 'Ops-Geheimnis']);
    }

    public function test_management_snapshot_aggregates_over_all_teams(): void
    {
        $data = app(DashboardSnapshotService::class)->refresh(null)->data;

        $this->assertSame(['incident' => 2, 'problem' => 1], $data['open_by_type']);
        $this->assertSame(['incident' => 1], $data['overdue_by_type']);
        $this->assertSame(['Ops' => 1, 'Support' => 2], $data['open_by_team']);
        $this->assertSame(4, array_sum($data['volume']));
    }

    public function test_team_snapshot_only_contains_own_team(): void
    {
        $data = app(DashboardSnapshotService::class)->refresh($this->ops)->data;

        $this->assertSame(['problem' => 1], $data['open_by_type']);
        $this->assertSame([], $data['overdue_by_type']);
        $this->assertArrayNotHasKey('open_by_team', $data);
    }

    public function test_team_member_sees_own_dashboard_without_cross_team_leak(): void
    {
        $member = User::factory()->create(['name' => 'Anna']);
        $this->support->users()->attach($member, ['role_in_team' => 'member']);

        Livewire::actingAs($member)
            ->test(TeamDashboard::class, ['team' => $this->support])
            ->assertOk()
            ->assertSee('Anna')
            ->assertDontSee('Ops-Geheimnis');

        Livewire::actingAs($member)
            ->test(TeamDashboard::class, ['team' => $this->ops])
            ->assertForbidden();
    }

    public function test_management_dashboard_requires_permission(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ManagementDashboard::class)
            ->assertForbidden();

        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findOrCreate('dashboard.management.view', 'web'));

        Livewire::actingAs($manager)
            ->test(ManagementDashboard::class)
            ->assertOk()
            ->assertSee('Offene Tickets je Team');
    }

    public function test_management_dashboard_warns_about_failing_mailboxes(): void
    {
        Mailbox::factory()->withFetchError()->create();
        Mailbox::factory()->withFetchError()->create(['active' => false]);

        $manager = User::factory()->create();
        $manager->givePermissionTo(
            Permission::findOrCreate('dashboard.management.view', 'web'),
            Permission::findOrCreate('mailboxes.manage', 'web'),
        );

        Livewire::actingAs($manager)
            ->test(ManagementDashboard::class)
            ->assertSee('Bei 1 Mailbox schlägt der Mailabruf fehl')
            ->assertSee(route('admin.mailboxes.index'));
    }

    public function test_refresh_command_is_scheduled_hourly_and_writes_all_scopes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'dashboards:refresh-snapshots'));

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);

        $this->artisan('dashboards:refresh-snapshots')->assertSuccessful();
        $this->assertSame(3, DashboardSnapshot::query()->count());
    }

    private function ticket(Team $team, array $attributes = []): Ticket
    {
        return Ticket::query()->create($attributes + [
            'team_id' => $team->id,
            'source' => 'api',
            'subject' => 'Test',
        ]);
    }
}

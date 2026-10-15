<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\BackupManager;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use App\Services\Backup\BackupService;
use App\Services\Backup\ScheduledBackup;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class BackupTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // No RefreshDatabase transaction: a restore drops and recreates every table.
        $this->artisan('migrate');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system_admin');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/private/backups'));
        File::deleteDirectory($this->scheduledDir());

        parent::tearDown();
    }

    public function test_backup_is_encrypted_and_restore_brings_back_the_old_data(): void
    {
        Team::query()->create(['name' => "Support; mit 'Quote'\nund Zeilenumbruch", 'slug' => 'support']);
        $backup = $this->createBackup();

        $zip = new \ZipArchive;
        $zip->open($backup['archive']);
        $this->assertFalse($zip->getFromName('custovis-dump.sql'), 'Dump must not be readable without password.');
        $zip->close();

        Team::query()->delete();
        Team::query()->create(['name' => 'Neu', 'slug' => 'neu']);

        $this->actingAs($this->admin)->post(route('admin.backup.restore'), $this->restoreUpload($backup))
            ->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertSame(["Support; mit 'Quote'\nund Zeilenumbruch"], Team::query()->pluck('name')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'system.backup_restored']);
    }

    public function test_restore_with_wrong_password_keeps_the_database(): void
    {
        $backup = $this->createBackup();
        Team::query()->create(['name' => 'Bleibt', 'slug' => 'bleibt']);

        $this->actingAs($this->admin)->from(route('admin.backup'))
            ->post(route('admin.backup.restore'), $this->restoreUpload($backup, 'Passwort: falsch'))
            ->assertRedirect(route('admin.backup'))->assertSessionHasErrors('archive');

        $this->assertDatabaseHas('teams', ['name' => 'Bleibt']);
    }

    public function test_reminder_disappears_after_backup_and_returns_after_interval_even_when_deleted(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users'))->assertSee('Datensicherung fällig');

        $this->createBackup();
        Livewire::actingAs($this->admin)->test(BackupManager::class)->call('downloadArchive')->assertFileDownloaded()
            ->call('deleteStored');

        $this->assertNull(app(BackupService::class)->latest());
        $this->actingAs($this->admin)->get(route('admin.users'))->assertDontSee('Datensicherung fällig');

        $this->travel(6)->days();
        $this->assertFalse(app(BackupService::class)->isOverdue());
        $this->travel(2)->days();
        $this->assertTrue(app(BackupService::class)->isOverdue());
    }

    public function test_only_system_maintainers_may_back_up_or_restore(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        Livewire::actingAs($agent)->test(BackupManager::class)->assertForbidden();
        $this->actingAs($agent)->post(route('admin.backup.restore'))->assertForbidden();
        $this->actingAs($agent)->get(route('agent.tickets.index'))->assertDontSee('Datensicherung fällig');
    }

    public function test_scheduled_backup_runs_daily_into_configured_directory_and_keeps_newest(): void
    {
        Livewire::actingAs($this->admin)->test(BackupManager::class)
            ->set('scheduledEnabled', true)->set('scheduledDirectory', $this->scheduledDir())->set('scheduledKeep', 2)
            ->call('saveSchedule')->assertHasNoErrors();

        foreach (range(1, 4) as $day) {
            app(ScheduledBackup::class)->runIfDue();
            app(ScheduledBackup::class)->runIfDue();
            $this->travel(1)->days();
        }

        $this->assertCount(2, File::glob($this->scheduledDir().'/custovis-backup-*.zip'));
        $this->assertCount(2, File::glob($this->scheduledDir().'/custovis-backup-*-passwort.txt'));
        $this->assertSame(4, AuditLog::query()->where('action', 'system.backup_scheduled')->count());
    }

    public function test_scheduled_directory_must_be_absolute_and_outside_web_root(): void
    {
        foreach (['relativ/pfad', '/tmp/../etc', public_path('backups')] as $directory) {
            Livewire::actingAs($this->admin)->test(BackupManager::class)
                ->set('scheduledDirectory', $directory)->call('saveSchedule')->assertHasErrors('scheduledDirectory');
        }
    }

    /**
     * @return array{name: string, archive: string, password: string}
     */
    private function createBackup(): array
    {
        Livewire::actingAs($this->admin)->test(BackupManager::class)->call('create')->assertSee('Sicherung erstellt');

        return app(BackupService::class)->latest();
    }

    /**
     * @param  array{archive: string, password: string}  $backup
     * @return array<string, mixed>
     */
    private function scheduledDir(): string
    {
        return storage_path('framework/testing/backups-scheduled');
    }

    private function restoreUpload(array $backup, ?string $password = null): array
    {
        return [
            'archive' => new UploadedFile($backup['archive'], 'sicherung.zip', 'application/zip', null, true),
            'password_file' => UploadedFile::fake()->createWithContent('passwort.txt', $password ?? File::get($backup['password'])),
            'confirm' => '1',
        ];
    }
}

<?php

namespace Tests\Feature\Release;

use App\Livewire\Admin\SystemMaintenance;
use App\Models\ServiceAppointment;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Installation\EnvironmentWriter;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->tmp = storage_path('framework/testing/install-'.uniqid());
        File::ensureDirectoryExists($this->tmp);
        config(['custovis.install_lock_path' => $this->tmp.'/installed.lock']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    public function test_installer_is_available_on_a_fresh_system_and_shows_requirements(): void
    {
        $this->get('/install')->assertOk()->assertSee('Systemvoraussetzungen')->assertSee('PHP >= 8.2');
    }

    public function test_installer_is_hard_disabled_once_users_exist_even_without_lock_file(): void
    {
        User::factory()->create();

        $this->get('/install')->assertNotFound();
        $this->post('/install', [])->assertNotFound();
        $this->assertFileExists($this->tmp.'/installed.lock');
    }

    public function test_installer_rejects_invalid_input_without_touching_env(): void
    {
        $this->post('/install', ['app_url' => 'kein-url', 'db_host' => "evil\nAPP_DEBUG=true"])
            ->assertOk()
            ->assertSee('app url')
            ->assertSee('db host');
    }

    public function test_environment_writer_sets_values_and_blocks_injection(): void
    {
        File::put($this->tmp.'/.env.example', "APP_NAME=Custovis\nAPP_KEY=\nAPP_DEBUG=true\nDB_PASSWORD=\n");
        $writer = new EnvironmentWriter($this->tmp.'/.env.example', $this->tmp.'/.env');

        $key = $writer->write(['DB_PASSWORD' => 'p@ss wort$1']);
        $env = File::get($this->tmp.'/.env');

        $this->assertStringStartsWith('base64:', $key);
        $this->assertStringContainsString("APP_KEY={$key}", $env);
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertStringContainsString('DB_PASSWORD="p@ss wort$1"', $env);

        $this->expectException(InvalidArgumentException::class);
        $writer->write(['DB_PASSWORD' => "x\nAPP_DEBUG=true"]);
    }

    public function test_install_command_creates_admin_seeds_base_data_and_locks(): void
    {
        $this->artisan('custovis:install', [
            '--admin-name' => 'Admin', '--admin-email' => 'admin@example.com', '--admin-password' => 'ein-sehr-sicheres-passwort',
        ])->assertSuccessful();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue($admin->hasRole('system_admin'));
        $this->assertTrue($admin->can('system.maintain'));
        $this->assertFileExists($this->tmp.'/installed.lock');
        $this->artisan('custovis:install', ['--admin-name' => 'X', '--admin-email' => 'x@example.com', '--admin-password' => 'ein-sehr-sicheres-passwort'])
            ->assertFailed();
    }

    public function test_demo_seeder_builds_a_consistent_presentable_system(): void
    {
        $this->seed([RolesAndPermissionsSeeder::class, DemoSeeder::class]);

        $this->assertEqualsCanonicalizing(
            ['support_ticket', 'incident', 'problem', 'change', 'service_request'],
            Ticket::query()->distinct()->pluck('type')->all()
        );
        $this->assertSame(1, ServiceAppointment::query()->count());
        $this->assertDatabaseCount('knowledge_base_articles', 3);
        $this->assertDatabaseCount('chat_messages', 3);
        $anna = User::query()->where('email', 'anna@demo.custovis.local')->firstOrFail();
        $this->actingAs($anna)->get('/agent')->assertOk()->assertSee('Rechnung doppelt erhalten')->assertDontSee('Mailserver nicht erreichbar');

        $this->seed(DemoSeeder::class); // idempotent
        $this->assertSame(3, Team::query()->count());
    }

    public function test_schema_update_page_requires_system_maintain(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('system_admin');

        Livewire::actingAs(User::factory()->create())->test(SystemMaintenance::class)->assertForbidden();
        Livewire::actingAs($admin)->test(SystemMaintenance::class)
            ->assertSee('auf dem aktuellen Stand')
            ->call('migrate')
            ->assertSet('output', 'Keine ausstehenden Migrationen.');
        $this->assertDatabaseHas('audit_logs', ['action' => 'system.migrated', 'user_id' => $admin->id]);
    }
}

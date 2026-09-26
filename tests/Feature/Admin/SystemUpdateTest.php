<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SystemMaintenance;
use App\Models\User;
use App\Services\Update\UpdateChecker;
use App\Services\Update\UpdateInstaller;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class SystemUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const DOWNLOAD_URL = 'https://github.com/coprea71/custovis/releases/download/v9.9.9/custovis-v9.9.9.zip';

    private User $admin;

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system_admin');
        $this->tmp = storage_path('framework/testing/update-'.uniqid());
        File::ensureDirectoryExists($this->tmp.'/target');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);

        parent::tearDown();
    }

    public function test_check_stores_newer_release_and_admin_sees_notice(): void
    {
        $this->fakeLatestRelease('v9.9.9', 'sha256:'.str_repeat('a', 64));

        $release = app(UpdateChecker::class)->check();

        $this->assertSame('9.9.9', $release['version']);
        $this->assertSame(self::DOWNLOAD_URL, $release['download_url']);
        $this->actingAs($this->admin)->get(route('admin.users'))->assertSee('Neue Version 9.9.9 verfügbar');
    }

    public function test_notice_is_hidden_for_current_version_and_for_users_without_permission(): void
    {
        $this->fakeLatestRelease('v'.config('custovis.version'), null);
        app(UpdateChecker::class)->check();
        $this->actingAs($this->admin)->get(route('admin.users'))->assertDontSee('verfügbar (installiert');

        $this->fakeLatestRelease('v9.9.9', null);
        app(UpdateChecker::class)->check();
        $manager = User::factory()->create();
        $manager->givePermissionTo('users.manage');
        $this->actingAs($manager)->get(route('admin.users'))->assertDontSee('Neue Version');
    }

    public function test_release_from_foreign_url_is_ignored(): void
    {
        Http::fake(['api.github.com/*' => Http::response([
            'tag_name' => 'v9.9.9',
            'html_url' => 'https://evil.example/releases/tag/v9.9.9',
            'assets' => [],
        ])]);

        $this->assertNull(app(UpdateChecker::class)->check());
        $this->assertNull(app(UpdateChecker::class)->availableUpdate());
    }

    public function test_installer_copies_files_but_keeps_env_and_storage(): void
    {
        $zip = $this->buildArchive([
            'config/custovis.php' => 'neu',
            '.env' => 'APP_KEY=boese',
            'storage/app/x.txt' => 'boese',
        ]);
        file_put_contents($this->tmp.'/target/.env', 'APP_KEY=alt');
        $this->fakeDownload($zip);

        (new UpdateInstaller($this->tmp.'/target'))->install($this->release($zip));

        $this->assertStringEqualsFile($this->tmp.'/target/config/custovis.php', 'neu');
        $this->assertStringEqualsFile($this->tmp.'/target/.env', 'APP_KEY=alt');
        $this->assertFileDoesNotExist($this->tmp.'/target/storage/app/x.txt');
    }

    public function test_installer_rejects_wrong_digest(): void
    {
        $zip = $this->buildArchive(['config/custovis.php' => 'neu']);
        $this->fakeDownload($zip);
        $release = ['version' => '9.9.9', 'download_url' => self::DOWNLOAD_URL, 'digest' => 'sha256:'.str_repeat('0', 64)];

        $this->expectException(RuntimeException::class);
        try {
            (new UpdateInstaller($this->tmp.'/target'))->install($release);
        } finally {
            $this->assertFileDoesNotExist($this->tmp.'/target/config/custovis.php');
        }
    }

    public function test_installer_refuses_git_checkout_and_foreign_download_url(): void
    {
        $zip = $this->buildArchive(['a.txt' => 'x']);

        File::ensureDirectoryExists($this->tmp.'/target/.git');
        $this->assertInstallFails($this->release($zip), 'Git-Checkout');

        File::deleteDirectory($this->tmp.'/target/.git');
        $this->assertInstallFails(['download_url' => 'https://evil.example/x.zip'] + $this->release($zip), 'kein installierbares');
    }

    public function test_install_action_is_forbidden_without_permission(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        Livewire::actingAs($agent)->test(SystemMaintenance::class)->assertForbidden();
    }

    public function test_install_action_reports_errors_without_redirect(): void
    {
        $this->fakeLatestRelease('v9.9.9', null);
        app(UpdateChecker::class)->check();

        Livewire::actingAs($this->admin)->test(SystemMaintenance::class)
            ->assertSee('Version 9.9.9 ist verfügbar')
            ->call('installUpdate')
            ->assertNoRedirect()
            ->assertSet('output', fn (string $output) => str_contains($output, 'Git-Checkout') || str_contains($output, 'Prüfsumme'));
    }

    private function assertInstallFails(array $release, string $message): void
    {
        try {
            (new UpdateInstaller($this->tmp.'/target'))->install($release);
            $this->fail('Update hätte abgelehnt werden müssen.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }
    }

    private function fakeLatestRelease(string $tag, ?string $digest): void
    {
        Http::fake(['api.github.com/*' => Http::response([
            'tag_name' => $tag,
            'html_url' => "https://github.com/coprea71/custovis/releases/tag/{$tag}",
            'assets' => [[
                'name' => "custovis-{$tag}.zip",
                'browser_download_url' => "https://github.com/coprea71/custovis/releases/download/{$tag}/custovis-{$tag}.zip",
                'digest' => $digest,
            ]],
        ])]);
    }

    private function fakeDownload(string $zip): void
    {
        Http::fake([self::DOWNLOAD_URL => Http::response(file_get_contents($zip))]);
    }

    private function release(string $zip): array
    {
        return ['version' => '9.9.9', 'download_url' => self::DOWNLOAD_URL, 'digest' => 'sha256:'.hash_file('sha256', $zip)];
    }

    private function buildArchive(array $files): string
    {
        $path = $this->tmp.'/release.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return $path;
    }
}

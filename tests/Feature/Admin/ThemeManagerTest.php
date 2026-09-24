<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\ThemeManager;
use App\Models\Theme;
use App\Models\User;
use App\Services\ThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ThemeManagerTest extends TestCase
{
    use RefreshDatabase;

    private string $themesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themesPath = storage_path('framework/testing/themes-'.uniqid());
        File::copyDirectory(resource_path('themes/musterlayout'), $this->themesPath.'/musterlayout');
        config(['custovis.themes_path' => $this->themesPath]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themesPath);

        parent::tearDown();
    }

    public function test_fresh_installation_uses_the_musterlayout_default_theme(): void
    {
        app(ThemeRegistry::class)->sync();

        $this->assertSame('musterlayout', app(ThemeRegistry::class)->activeTheme()?->slug);
        $this->withoutVite()->get('/login')
            ->assertSee('data-theme="musterlayout"', false)
            ->assertSee('--color-calm-600:#3D5E50;', false);
    }

    public function test_dropping_a_theme_folder_registers_it_without_core_changes(): void
    {
        $this->writeTheme('ozean', ['name' => 'Ozean', 'preview_image' => 'preview.png', 'css_variables' => ['color-calm-600' => '#1D4ED8']]);

        app(ThemeRegistry::class)->sync();

        $this->assertDatabaseHas('themes', ['slug' => 'ozean', 'name' => 'Ozean', 'active' => true]);
    }

    public function test_incomplete_or_unsafe_theme_is_logged_and_skipped(): void
    {
        Log::spy();
        $this->writeTheme('kaputt', ['name' => 'Kaputt', 'css_variables' => ['color-calm-600' => '#000000']]);
        $this->writeTheme('boese', ['name' => 'Böse', 'preview_image' => 'preview.png', 'css_variables' => ['color-calm-600' => 'red;}</style><script>']]);

        app(ThemeRegistry::class)->sync();

        $this->assertDatabaseMissing('themes', ['slug' => 'kaputt']);
        $this->assertDatabaseMissing('themes', ['slug' => 'boese']);
        $this->assertDatabaseHas('themes', ['slug' => 'musterlayout']);
        Log::shouldHaveReceived('warning')->twice();
    }

    public function test_admin_switches_theme_for_all_areas_and_it_is_audited(): void
    {
        $this->writeTheme('ozean', ['name' => 'Ozean', 'preview_image' => 'preview.png', 'css_variables' => ['color-calm-600' => '#1D4ED8']]);
        app(ThemeRegistry::class)->sync();
        $admin = $this->adminUser();

        Livewire::actingAs($admin)
            ->test(ThemeManager::class)
            ->call('activate', Theme::query()->where('slug', 'ozean')->value('id'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'theme.changed', 'user_id' => $admin->id]);
        $this->withoutVite()->actingAs($admin)->get('/agent')->assertSee('data-theme="ozean"', false);
        $this->withoutVite()->actingAs($admin)->get('/admin/settings/theme')->assertSee('data-theme="ozean"', false);
    }

    public function test_user_without_permission_cannot_open_theme_settings(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ThemeManager::class)
            ->assertForbidden();
    }

    private function writeTheme(string $slug, array $manifest): void
    {
        File::ensureDirectoryExists("{$this->themesPath}/{$slug}");
        File::copy(resource_path('themes/musterlayout/preview.png'), "{$this->themesPath}/{$slug}/preview.png");
        File::put("{$this->themesPath}/{$slug}/theme.json", json_encode($manifest));
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('system.settings.manage', 'web'));

        return $user;
    }
}

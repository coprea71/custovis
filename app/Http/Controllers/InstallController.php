<?php

namespace App\Http\Controllers;

use App\Services\Installation\EnvironmentWriter;
use App\Services\Installation\InstallationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Single-step web installer for FTP/SFTP-only hosting (12.md). Runs without
 * session/cookies (there may be no APP_KEY yet), so errors are rendered
 * directly instead of via redirect-with-flash.
 */
class InstallController extends Controller
{
    public function __construct(private readonly InstallationService $installation) {}

    public function show(): View
    {
        return $this->form(new MessageBag, []);
    }

    public function store(Request $request, EnvironmentWriter $environment): View
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->form($validator->errors(), $request->except(['db_password', 'admin_password', 'admin_password_confirmation']));
        }

        $data = $validator->validated();
        $db = ['host' => $data['db_host'], 'port' => $data['db_port'], 'database' => $data['db_database'], 'username' => $data['db_username'], 'password' => $data['db_password'] ?? ''];

        if ($error = $this->installation->testDatabase($db)) {
            return $this->form(new MessageBag(['db_host' => $error]), $request->except(['db_password', 'admin_password', 'admin_password_confirmation']));
        }

        try {
            $this->configure($environment->write($this->environmentValues($data)), $db, $data['app_url']);
        } catch (InvalidArgumentException $e) {
            return $this->form(new MessageBag(['db_password' => $e->getMessage()]), []);
        }

        $this->installation->install(
            ['name' => $data['admin_name'], 'email' => $data['admin_email'], 'password' => $data['admin_password']],
            (bool) ($data['demo_data'] ?? false)
        );

        return view('install.done', ['loginUrl' => rtrim($data['app_url'], '/').'/login']);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'db_host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-_]+$/'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'db_username' => ['required', 'string', 'max:255', 'regex:/^[^\s"\\\\]+$/'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
            'demo_data' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function environmentValues(array $data): array
    {
        return [
            'APP_URL' => rtrim($data['app_url'], '/'),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => (string) ($data['db_password'] ?? ''),
        ];
    }

    /**
     * The new .env only applies from the next request on — the current one
     * needs the values at runtime to migrate and create the admin.
     *
     * @param  array<string, mixed>  $db
     */
    private function configure(string $appKey, array $db, string $appUrl): void
    {
        config([
            'app.key' => $appKey,
            'app.url' => $appUrl,
            'database.default' => 'mysql',
            'database.connections.mysql' => array_merge(config('database.connections.mysql'), $db),
        ]);
        DB::purge('mysql');
    }

    /**
     * @param  array<string, mixed>  $old
     */
    private function form(MessageBag $errors, array $old): View
    {
        return view('install.form', [
            'requirements' => $this->installation->requirements(),
            'errors' => $errors,
            'old' => $old,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class InstallerController extends Controller
{
    public function index()
    {
        return view('install.index', [
            'requirements' => $this->requirements(),
            'ready' => collect($this->requirements())->filter(fn ($item) => empty($item['optional']))->every(fn ($item) => $item['ok']),
            'installKeyRequired' => (bool) env('ITTUBE_INSTALL_KEY'),
        ]);
    }

    public function install(Request $request)
    {
        if (File::exists(storage_path('app/installed.lock'))) {
            abort(404);
        }

        $data = $request->validate([
            'app_url' => ['required', 'url', 'max:200'],
            'db_host' => ['required', 'string', 'max:120'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:120'],
            'db_username' => ['required', 'string', 'max:120'],
            'db_password' => ['nullable', 'string', 'max:200'],
            'admin_name' => ['required', 'string', 'max:80'],
            'admin_email' => ['required', 'email', 'max:120'],
            'admin_password' => ['required', 'string', 'min:10', 'max:100', 'confirmed'],
            'install_key' => ['nullable', 'string'],
        ]);

        if (env('ITTUBE_INSTALL_KEY') && ! hash_equals((string) env('ITTUBE_INSTALL_KEY'), (string) ($data['install_key'] ?? ''))) {
            return back()->withInput()->withErrors(['install_key' => 'Invalid installation key.']);
        }

        $requirements = $this->requirements();
        $failed = collect($requirements)->filter(fn ($item) => empty($item['optional']) && ! $item['ok']);
        if ($failed->isNotEmpty()) {
            return back()->withErrors(['requirements' => 'Server requirements are not met: '.$failed->pluck('label')->implode(', ')]);
        }

        try {
            new PDO(
                "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_database']};charset=utf8mb4",
                $data['db_username'],
                $data['db_password'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['db_database' => 'Database connection failed. Check your database name, username and password.']);
        }

        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $previousKey = config('app.key');
        $this->writeEnvironmentFile($data, $appKey);

        Config::set('app.key', $appKey);
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ]);
        DB::purge('mysql');

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);
            File::delete(base_path('.env'));
            Config::set('app.key', $previousKey);

            return back()->withInput()->withErrors(['db_database' => 'Database setup failed: '.$e->getMessage()]);
        }

        $superRole = AdminRole::where('is_super', true)->first();
        AdminUser::updateOrCreate(
            ['email' => strtolower($data['admin_email'])],
            [
                'name' => $data['admin_name'],
                'password' => Hash::make($data['admin_password']),
                'role_id' => $superRole?->id,
                'status' => 'active',
            ]
        );

        File::ensureDirectoryExists(storage_path('app'));
        File::put(storage_path('app/installed.lock'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_url' => $data['app_url'],
            'admin_email' => strtolower($data['admin_email']),
        ], JSON_PRETTY_PRINT));

        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (Throwable $e) {
                // Cache clearing is best-effort on shared hosting.
            }
        }

        return redirect()->route('admin.login')->with('status', 'IT TUBE installed successfully. Log in with your Super Admin account.');
    }

    protected function writeEnvironmentFile(array $data, string $appKey): void
    {
        $dbPassword = str_replace(['"', '\\'], ['\"', '\\\\'], (string) ($data['db_password'] ?? ''));

        $env = <<<ENV
APP_NAME="IT TUBE"
APP_ENV=production
APP_KEY={$appKey}
APP_DEBUG=false
APP_URL={$data['app_url']}
APP_TIMEZONE=UTC

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$data['db_host']}
DB_PORT={$data['db_port']}
DB_DATABASE={$data['db_database']}
DB_USERNAME={$data['db_username']}
DB_PASSWORD="{$dbPassword}"

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database
CACHE_STORE=file
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@{$this->domainFromUrl($data['app_url'])}"
MAIL_FROM_NAME="IT TUBE"

SANCTUM_STATEFUL_DOMAINS={$this->domainFromUrl($data['app_url'])}
ITTUBE_INSTALLED=true
ENV;

        File::put(base_path('.env'), $env."\n");
    }

    protected function domainFromUrl(string $url): string
    {
        return parse_url($url, PHP_URL_HOST) ?: 'localhost';
    }

    protected function requirements(): array
    {
        $writable = [
            'storage' => storage_path(),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'public' => public_path(),
        ];

        $items = [
            ['label' => 'PHP 8.2 or newer (detected '.PHP_VERSION.')', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
        ];

        foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'gd', 'zip', 'curl', 'json', 'tokenizer', 'xml', 'ctype', 'bcmath'] as $extension) {
            $items[] = ['label' => 'PHP extension: '.$extension, 'ok' => extension_loaded($extension)];
        }

        foreach ($writable as $label => $path) {
            $items[] = ['label' => 'Writable: '.$label, 'ok' => is_writable($path)];
        }

        $items[] = ['label' => 'Base directory writable (.env)', 'ok' => is_writable(base_path())];
        $items[] = ['label' => 'FFmpeg available (optional, for auto thumbnails)', 'ok' => trim((string) @shell_exec('command -v ffmpeg 2>/dev/null')) !== '', 'optional' => true];

        return $items;
    }
}

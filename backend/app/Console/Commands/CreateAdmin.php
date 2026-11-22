<?php

namespace App\Console\Commands;

use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'ittube:create-admin {email} {--name=Super Admin} {--password=}';

    protected $description = 'Create or update an IT TUBE super admin account';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $password = $this->option('password') ?: $this->secret('Admin password (min 10 characters)');

        if (strlen((string) $password) < 10) {
            $this->error('Password must be at least 10 characters.');

            return self::FAILURE;
        }

        $role = AdminRole::where('is_super', true)->first();

        AdminUser::updateOrCreate(
            ['email' => $email],
            ['name' => $this->option('name'), 'password' => Hash::make($password), 'role_id' => $role?->id, 'status' => 'active']
        );

        File::ensureDirectoryExists(storage_path('app'));
        File::put(storage_path('app/installed.lock'), json_encode(['installed_at' => now()->toIso8601String(), 'admin_email' => $email]));

        $this->info('Super admin ready: '.$email);
        $this->info('Installer locked.');

        return self::SUCCESS;
    }
}

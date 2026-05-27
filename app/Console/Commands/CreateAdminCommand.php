<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Create or update the admin user.
 *
 * Idempotent — re-runnable without duplicating accounts. Looks up by email,
 * updates name/password if the user exists, creates otherwise.
 *
 * Useful on first Plesk deploy (no SSH, only Plesk Scheduled Tasks) because
 * `php artisan make:filament-user` is interactive and won't run via cron.
 *
 * Example (Plesk → Scheduled Tasks → Run-once):
 *   php artisan triad:create-admin admin@triad.kz <password> "Admin"
 */
final class CreateAdminCommand extends Command
{
    protected $signature = 'triad:create-admin
        {email : Email address (also the login)}
        {password : Plain-text password — will be hashed via bcrypt}
        {name=Admin : Display name (optional)}';

    protected $description = 'Create or update an admin user. Idempotent.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->argument('name');

        if (mb_strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $existed = $user->exists;
        $user->name = $name;
        $user->password = Hash::make($password);
        $user->email_verified_at ??= now();
        $user->save();

        $this->info(sprintf(
            '%s admin: %s (id=%d)',
            $existed ? 'Updated' : 'Created',
            $email,
            $user->id,
        ));

        return self::SUCCESS;
    }
}

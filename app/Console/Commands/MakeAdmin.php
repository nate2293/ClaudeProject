<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Grants or removes access to the admin area at /admin.
 *
 *   php artisan shop:make-admin jo@example.com           # grant
 *   php artisan shop:make-admin jo@example.com --revoke  # remove
 */
class MakeAdmin extends Command
{
    protected $signature = 'shop:make-admin {email : The email of an existing account} {--revoke : Remove admin access instead}';

    protected $description = 'Give an existing user access to the admin area (or remove it)';

    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if (! $user) {
            $this->error('No account with that email. Register on the shop first, then run this again.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $this->info($user->is_admin
            ? "{$user->email} can now sign in at ".url('/admin').'.'
            : "{$user->email} no longer has admin access.");

        return self::SUCCESS;
    }
}

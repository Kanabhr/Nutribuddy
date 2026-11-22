<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Console\Command;

class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:2fa:reset {email : The admin\'s email address}';

    protected $description = 'Disable 2FA for a locked-out admin user (emergency reset)';

    public function handle(TwoFactorService $twoFactor): int
    {
        $email = $this->argument('email');
        $admin = User::where('email', $email)->where('role', 'admin')->first();

        if (! $admin) {
            $this->error("No admin found with email: {$email}");

            return 1;
        }

        if (! $twoFactor->isEnabled($admin)) {
            $this->info('2FA is not currently enabled for this admin.');

            return 0;
        }

        if (! $this->confirm("Disable 2FA for {$admin->email}? This cannot be undone.")) {
            return 0;
        }

        $twoFactor->disable($admin);
        $this->info("2FA disabled for {$admin->email}.");

        return 0;
    }
}

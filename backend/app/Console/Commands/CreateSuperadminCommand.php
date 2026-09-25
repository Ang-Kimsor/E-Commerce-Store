<?php

namespace App\Console\Commands;

use App\Rules\PasswordRule;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperadminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superadmin:create {name? : Full Name} {email? : Email Address} {password? : Account Password} {telegram_id? : Telegram User ID (for order notifications)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new superadmin account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');
        $telegramId = $this->argument('telegram_id');

        // Prompt interactively if missing arguments
        if (!$name) {
            $name = $this->ask('Enter Full Name');
            while (empty(trim((string)$name))) {
                $this->error('Name is required.');
                $name = $this->ask('Enter Full Name');
            }
        }

        if (!$email) {
            $email = $this->ask('Enter Email address');
            while (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Please enter a valid email address.');
                $email = $this->ask('Enter Email address');
            }
        }

        if (!$password) {
            $password = $this->secret('Enter Password (min 8 chars, 1 uppercase, 1 lowercase, 1 number)');
            while ($error = PasswordRule::getValidationError($password)) {
                $this->error($error);
                $password = $this->secret('Enter Password (min 8 chars, 1 uppercase, 1 lowercase, 1 number)');
            }
        } else {
            if ($error = PasswordRule::getValidationError($password)) {
                $this->error($error);
                return Command::FAILURE;
            }
        }

        if (!$telegramId) {
            $telegramId = $this->ask('Enter Telegram User ID (optional, press Enter to skip)');
            if ($telegramId && (!is_numeric($telegramId) || strlen($telegramId) < 9)) {
                $this->warn('Invalid Telegram User ID format. Skipping Telegram ID.');
                $telegramId = null;
            }
        }

        $user = User::withTrashed()->where('email', $email)->first();

        if ($user) {
            $this->error("Account creation failed: A user with email '{$email}' already exists.");
            $this->line("If you wish to activate an existing account, please use command: php artisan superadmin:active");
            return Command::FAILURE;
        }

        $superAdmin = User::create([
            'name'        => $name,
            'email'       => $email,
            'password'    => Hash::make($password),
            'role'        => UserRole::SuperAdmin,
            'is_active'   => true,
            'telegram_id' => $telegramId ? trim($telegramId) : null,
        ]);

        try {
            \App\Services\NotificationService::notifyAdmins(new \App\Notifications\NewSuperAdminNotification($superAdmin));
        } catch (\Throwable $e) {
            // Ignore notification error during command execution
        }

        $this->info("Superadmin {$name} ({$email}) created successfully!");
        if ($superAdmin->telegram_id) {
            $this->info("Telegram ID saved: {$superAdmin->telegram_id} — order notifications will be sent to this chat.");
        } else {
            $this->warn("No Telegram ID set. To receive order notifications, update it later via the admin profile or run this command again.");
        }

        return Command::SUCCESS;
    }
}

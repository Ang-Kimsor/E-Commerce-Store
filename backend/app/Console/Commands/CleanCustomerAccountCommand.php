<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanCustomerAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:clean-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process account deletions that have passed the 90-day grace period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find users who requested deletion more than 90 days ago and are inactive
        $users = User::where('is_active', false)
            ->where('role', 'customer')
            ->whereNotNull('account_deletion_requested_at')
            ->where('account_deletion_requested_at', '<=', now()->subDays(90))
            ->get();

        if ($users->isEmpty()) {
            $this->info('No accounts to process for deletion.');
            return;
        }

        foreach ($users as $user) {
            // Delete avatar if exists
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Delete addresses
            $user->addresses()->delete();

            // Set email to null so it can be reused
            $user->email = null;
            $user->save();

            // Finally, soft delete the user
            $user->delete();

            $this->info("Processed deletion for user ID: {$user->id}");
        }

        $this->info('Account deletion processing completed.');
    }
}

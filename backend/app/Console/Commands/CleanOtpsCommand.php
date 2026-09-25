<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOtpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:clean-otp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard delete old customer OTP verifications to free up DB space.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deletedOtps = \Illuminate\Support\Facades\DB::table('customer_otp_verifications')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("Cleaned up {$deletedOtps} OTP verification records (older than 30 days).");
    }
}

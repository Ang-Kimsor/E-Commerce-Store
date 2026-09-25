<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hard delete old notifications to free up DB space.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Delete notification_user records older than 90 days.
        $deletedNotificationUsers = \Illuminate\Support\Facades\DB::table('notification_user')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        // 2. Delete notifications older than 90 days.
        $deletedNotifications = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        $this->info("Cleaned up {$deletedNotificationUsers} notification_user records (older than 90 days).");
        $this->info("Cleaned up {$deletedNotifications} notifications (older than 90 days).");
    }
}

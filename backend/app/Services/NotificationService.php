<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Dispatch a notification to all admins, creating only a single notification row
     * and attaching it to all eligible admins via the pivot table.
     */
    public static function notifyAdmins(LaravelNotification $notificationClass)
    {
        $admins = User::whereIn('role', ['admin', 'superadmin'])->pluck('id');

        if ($admins->isEmpty()) {
            return;
        }

        // Generate ID manually since we bypass normal Laravel notification creation
        $id = $notificationClass->id ?? Str::uuid()->toString();
        
        $notifiable = new User();
        $data = method_exists($notificationClass, 'toDatabase') 
            ? $notificationClass->toDatabase($notifiable) 
            : (method_exists($notificationClass, 'toArray') ? $notificationClass->toArray($notifiable) : []);

        $notification = Notification::create([
            'id' => $id,
            'type' => get_class($notificationClass),
            'data' => $data,
        ]);

        $notification->users()->attach($admins);
    }
}

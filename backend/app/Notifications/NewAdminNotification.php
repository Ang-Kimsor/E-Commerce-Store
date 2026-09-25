<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewAdminNotification extends Notification
{
    use Queueable;

    public function __construct(public User $adminUser)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_admin',
            'title' => 'New Admin Created',
            'message' => 'Admin account "' . $this->adminUser->name . '" (' . $this->adminUser->email . ') has been created.',
            'icon' => 'ShieldIcon',
            'admin_id' => $this->adminUser->id,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSuperAdminNotification extends Notification
{
    use Queueable;

    public function __construct(public User $superAdminUser)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_superadmin',
            'title' => 'New SuperAdmin Created',
            'message' => 'SuperAdmin account "' . $this->superAdminUser->name . '" (' . $this->superAdminUser->email . ') has been created.',
            'icon' => 'ShieldIcon',
            'superadmin_id' => $this->superAdminUser->id,
        ];
    }
}

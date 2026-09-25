<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserLoginNotification extends Notification
{
    use Queueable;

    public function __construct(public User $user)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Extract role string safely (handle PHP Enums)
        $roleString = $this->user->role instanceof \UnitEnum 
            ? $this->user->role->value 
            : (string) $this->user->role;

        $isAdmin = in_array(strtolower($roleString), ['admin', 'superadmin']);
        $roleLabel = $isAdmin ? ucfirst(strtolower($roleString)) : 'Customer';
        $displayName = $this->user->name ?: $this->user->email ?: "Unknown User";
        $where = $isAdmin ? 'the admin panel' : 'the store';

        return [
            'type'    => 'user_login',
            'title'   => "{$roleLabel} Logged In",
            'message' => "{$displayName} just signed in to {$where}.",
            'icon'    => $isAdmin ? 'ShieldIcon' : 'LogInIcon',
            'user_id' => $this->user->id,
        ];
    }
}

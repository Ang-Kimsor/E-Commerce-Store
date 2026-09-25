<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewCustomerNotification extends Notification
{
    use Queueable;

    public function __construct(public User $customer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_customer',
            'title' => 'New Customer Registered',
            'message' => $this->customer->name . ' has joined.',
            'icon' => 'UsersIcon',
            'customer_id' => $this->customer->id,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order, public string $changedByName = 'Admin')
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $orderNumber = $this->order->order_number ?? ('#' . $this->order->id);
        return [
            'type' => 'order_updated',
            'title' => 'Order Updated',
            'message' => 'Order ' . $orderNumber . ' was updated by ' . $this->changedByName . '.',
            'icon' => 'PackageIcon',
            'order_id' => $this->order->id,
            'changed_by' => $this->changedByName,
        ];
    }
}

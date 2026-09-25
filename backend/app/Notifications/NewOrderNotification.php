<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'title' => 'New Order Received',
            'message' => 'Order #' . $this->order->id . ' has been placed for $' . number_format($this->order->total, 2),
            'icon' => 'PackageIcon',
            'order_id' => $this->order->id,
        ];
    }
}

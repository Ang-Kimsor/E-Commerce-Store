<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public Product $product)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => 'Product Out of Stock',
            'message' => $this->product->name . ' is now out of stock.',
            'icon' => 'AlertTriangleIcon',
            'product_id' => $this->product->id,
        ];
    }
}

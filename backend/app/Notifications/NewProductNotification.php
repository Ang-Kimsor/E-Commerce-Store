<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewProductNotification extends Notification
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
            'type' => 'new_product',
            'title' => 'New Product Added',
            'message' => 'Product "' . $this->product->name . '" has been added.',
            'icon' => 'PackageIcon',
            'product_id' => $this->product->id,
        ];
    }
}

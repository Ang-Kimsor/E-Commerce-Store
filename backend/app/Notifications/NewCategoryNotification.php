<?php

namespace App\Notifications;

use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewCategoryNotification extends Notification
{
    use Queueable;

    public function __construct(public Category $category)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_category',
            'title' => 'New Category Added',
            'message' => 'Category "' . $this->category->name . '" has been added.',
            'icon' => 'LayoutGridIcon',
            'category_id' => $this->category->id,
        ];
    }
}

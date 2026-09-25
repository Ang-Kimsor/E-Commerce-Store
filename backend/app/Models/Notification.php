<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_user')
                    ->withPivot('read_at')
                    ->withTimestamps();
    }
}

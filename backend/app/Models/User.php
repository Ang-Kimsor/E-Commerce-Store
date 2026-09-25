<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'role',
        'phone',
        'avatar_url',
        'telegram_id',
    ];

    protected $appends = ['status'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (User $user) {
            $user->deleteAvatarFile();
        });
    }

    public function getStatusAttribute()
    {
        if (method_exists($this, 'trashed') && $this->trashed()) {
            return 'deleted';
        }
        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Relationship: user orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Relationship: saved addresses.
     */
    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    public function isCustomer(): bool
    {
        return $this->role?->isCustomer() ?? false;
    }

    /**
     * Convert the model instance to an array.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // Convert enum to string for JSON serialization
        if (isset($array['role']) && $array['role'] instanceof UserRole) {
            $array['role'] = $array['role']->value;
        }

        // Add absolute URL for avatar
        if (!empty($array['avatar_url']) && !str_starts_with($array['avatar_url'], 'http')) {
            $array['avatar_url'] = \Illuminate\Support\Facades\Storage::disk('public')->url($array['avatar_url']);
        }

        return $array;
    }

    /**
     * Get the entity's notifications.
     */
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            ->withPivot('read_at')
            ->withTimestamps()
            ->orderByDesc('created_at'); // Notification creation time
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->wherePivotNull('read_at');
    }

    /**
     * Delete the user's avatar file from public storage if it exists.
     */
    public function deleteAvatarFile(): bool
    {
        $raw = $this->getRawOriginal('avatar_url') ?? $this->avatar_url;
        if (empty($raw)) {
            return false;
        }

        $parsed = parse_url($raw, PHP_URL_PATH) ?? $raw;
        $path = ltrim($parsed, '/');

        if (str_starts_with($path, 'api/storage/')) {
            $path = substr($path, strlen('api/storage/'));
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (!empty($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Log::info('Deleting user avatar file from storage', [
                'user_id' => $this->id,
                'path' => $path,
            ]);
            return \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        return false;
    }
}

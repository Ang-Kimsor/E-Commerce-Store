<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Address;
use App\Models\OrderProduct;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    use HasFactory;

    public $status_remark;
    public $payment_status_remark;

    protected $fillable = [
        'order_number',
        'customer_id',
        'address_id',
        'status',
        'payment_status',
        'subtotal',
        'discount',
        'shipping_cost',
        'total',
        'payment_method',
        'payment_receipt',
        'admin_note',
        'customer_note',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function address()
    {
        return $this->belongsTo(Address::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function paymentHistories()
    {
        return $this->hasMany(PaymentStatusHistory::class);
    }

    protected static function booted()
    {
        static::updated(function ($order) {
            $user = Auth::user();

            // If there's no authenticated user, we can't accurately track who changed it, 
            // but we might want to allow system changes. We'll use 1 (Super Admin) as fallback or skip.
            // For now, let's just skip if no user context to avoid constraint failures.
            if (!$user) return;

            if ($order->isDirty('status')) {
                $oldStatus = $order->getOriginal('status');
                $newStatus = $order->status;

                $order->statusHistories()->create([
                    'old_status' => $oldStatus instanceof \UnitEnum ? $oldStatus->value : $oldStatus,
                    'new_status' => $newStatus instanceof \UnitEnum ? $newStatus->value : $newStatus,
                    'changed_by' => $user->id,
                    'remarks' => $order->status_remark,
                    'created_at' => now(),
                ]);
            }

            if ($order->isDirty('payment_status')) {
                $oldStatus = $order->getOriginal('payment_status');
                $newStatus = $order->payment_status;

                $order->paymentHistories()->create([
                    'old_status' => $oldStatus instanceof \UnitEnum ? $oldStatus->value : $oldStatus,
                    'new_status' => $newStatus instanceof \UnitEnum ? $newStatus->value : $newStatus,
                    'payment_reference' => $order->payment_receipt,
                    'changed_by' => $user->id,
                    'remarks' => $order->payment_status_remark,
                    'created_at' => now(),
                ]);
            }
        });
    }
}

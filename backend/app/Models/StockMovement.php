<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'type'     => StockMovementType::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    protected static function booted()
    {
        static::created(function ($movement) {
            $product = $movement->product;
            if ($movement->type === StockMovementType::In) {
                $product->stock += $movement->quantity;
            } elseif ($movement->type === StockMovementType::Out) {
                $product->stock -= $movement->quantity;
            }
            $product->save();
        });
    }
}

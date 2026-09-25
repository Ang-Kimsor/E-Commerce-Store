<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'slug',
        'description',
        'price',
        'discount_percent',
        'stock',
        'is_active',
        'image',
        'category_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percent' => 'float',
        'stock' => 'integer',
    ];

    protected $appends = ['status'];

    protected static function booted(): void
    {
        static::forceDeleted(function (Product $product) {
            $product->deleteImageFile();
        });
    }

    public function getStatusAttribute()
    {
        if (method_exists($this, 'trashed') && $this->trashed()) {
            return 'deleted';
        }
        return $this->is_active ? 'active' : 'inactive';
    }

    public function getSoldUnitsAttribute(): int
    {
        return (int) $this->orderProducts()
            ->whereHas('order', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->sum('quantity');
    }



    public function getImageAttribute($value)
    {
        return self::normalizeStorageUrl($value);
    }

    /**
     * Normalize any storage URL to a root-relative /api/storage/ path.
     * Handles: relative paths, absolute URLs with /storage/, data URLs, etc.
     */
    private static function normalizeStorageUrl(?string $url): ?string
    {
        if (!$url) return null;

        // If it's a remote URL (starts with http/https) and doesn't contain '/storage/', return it directly
        if ((str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) && !str_contains($url, '/storage/')) {
            return $url;
        }

        // Already correct
        if (str_contains($url, '/api/storage/')) return $url;

        // Full URL with /storage/ but without /api/storage/ — extract the relative path
        if ((str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) && str_contains($url, '/storage/')) {
            $path = preg_replace('#^https?://[^/]+/storage/#', '', $url);
            return '/api/storage/' . $path;
        }

        // Relative path like "products/file.jpg" or "storage/products/file.jpg"
        $clean = ltrim($url, '/');
        if (str_starts_with($clean, 'storage/')) {
            $clean = substr($clean, strlen('storage/'));
        }

        return '/api/storage/' . $clean;
    }

    /**
     * Delete the product's image file from public storage if it exists.
     */
    public function deleteImageFile(): bool
    {
        $raw = $this->getRawOriginal('image') ?? $this->image;
        if (empty($raw) || str_starts_with($raw, 'data:')) return false;

        $parsed = parse_url($raw, PHP_URL_PATH) ?? $raw;
        $path = ltrim($parsed, '/');
        if (str_starts_with($path, 'api/storage/')) {
            $path = substr($path, strlen('api/storage/'));
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (!empty($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Log::info('Deleting product image file from storage', [
                'product_id' => $this->id,
                'path' => $path,
            ]);
            return \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        return false;
    }


    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}

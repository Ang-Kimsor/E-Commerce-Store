<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with(['category']);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category_ids')) {
            $raw = $request->input('category_ids');
            $ids = is_array($raw) ? $raw : explode(',', (string) $raw);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $query->whereIn('category_id', $ids);
            }
        } elseif ($request->filled('category_id')) {
            $raw = $request->input('category_id');
            $ids = is_array($raw) ? $raw : explode(',', (string) $raw);
            $ids = array_filter(array_map('intval', $ids));
            if (count($ids) > 1) {
                $query->whereIn('category_id', $ids);
            } elseif (count($ids) === 1) {
                $query->where('category_id', reset($ids));
            }
        }

        $query->active();

        $query->where(function ($q) {
            $q->whereNull('category_id')
              ->orWhereHas('category', function ($sub) {
                  $sub->where('is_active', true)->whereNull('categories.deleted_at');
              });
        });

        if ($request->filled('min_price')) {
            $query->whereRaw('price - (price * discount_percent / 100) >= ?', [$request->input('min_price')]);
        }

        if ($request->filled('max_price')) {
            $query->whereRaw('price - (price * discount_percent / 100) <= ?', [$request->input('max_price')]);
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->string('stock_status');
            if ($stockStatus === 'in_stock') {
                $query->where('stock', '>', 0);
            } elseif ($stockStatus === 'out_of_stock') {
                $query->where('stock', '<=', 0);
            }
        }

        // Get sorting preference from request or fallback to settings (default: id_desc = newest first)
        $sortBy = $request->input('sort_by', \App\Models\SiteSetting::get('product_sort_by', 'id_desc'));
        // Parse sort option: format is "column_direction" (e.g., "id_asc", "price_desc")
        $sortParts = explode('_', $sortBy);
        $sortColumn = $sortParts[0] ?? 'id';
        $sortDirection = $sortParts[1] ?? 'asc';

        // Validate column to prevent SQL injection
        $allowedColumns = ['id', 'name', 'price', 'created_at'];
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'id';
        }

        // Validate direction
        $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc';

        if ($sortColumn === 'price') {
            $query->orderByRaw('price - (price * discount_percent / 100) ' . $sortDirection);
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }
        $perPage = $request->integer('per_page', 12);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    public function show(string $identifier): JsonResponse
    {
        $query = Product::with(['category'])->active();
        
        $query->where(function ($q) {
            $q->whereNull('category_id')
              ->orWhereHas('category', function ($sub) {
                  $sub->where('is_active', true)->whereNull('categories.deleted_at');
              });
        });

        if (is_numeric($identifier)) {
            $query->where(function($q) use ($identifier) {
                $q->where('id', $identifier)
                  ->orWhere('slug', $identifier);
            });
        } else {
            $query->where('slug', $identifier);
        }

        $product = $query->firstOrFail();

        $relatedProducts = Product::with(['category'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->latest()
            ->take(4)
            ->get();

        return response()->json([
            'product' => $product,
            'related_products' => $relatedProducts,
        ]);
    }
}

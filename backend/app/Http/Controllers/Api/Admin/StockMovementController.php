<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockMovement\StoreStockMovementRequest;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        $query = $product->stockMovements()->with('user');

        // Filters
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Search (by reference or user name)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDesc = $request->boolean('sort_desc', true);
        
        $allowedSorts = ['created_at', 'type', 'quantity', 'reference'];
        if ($sortBy === 'quantity') {
            $query->orderByRaw("(CASE WHEN type = '" . StockMovementType::In->value . "' THEN quantity ELSE -quantity END) " . ($sortDesc ? 'desc' : 'asc'));
        } elseif (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $movements = $query->paginate(10);
        return response()->json($movements);
    }

    public function store(StoreStockMovementRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        $data['product_id'] = $product->id;
        $data['user_id'] = $request->user()?->id;

        if ($data['type'] === StockMovementType::Out->value && $data['quantity'] > $product->stock) {
            return response()->json([
                'message' => 'Insufficient stock. Cannot remove more than the current available stock.'
            ], 422);
        }

        $movement = StockMovement::create($data);

        // Product stock is automatically updated via StockMovement::created event
        $product->refresh();

        return response()->json([
            'movement' => $movement->load('user'),
            'product' => $product
        ], 201);
    }
}

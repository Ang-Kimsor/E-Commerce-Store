<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();
        $query->where('is_active', true);
        $query->withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }]);
        $query->orderBy('products_count', 'desc')->orderBy('name', 'asc');

        if ($perPage = $request->query('per_page')) {
            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }
}

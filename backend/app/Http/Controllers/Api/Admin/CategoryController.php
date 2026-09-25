<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exports\CategoriesExport;
use App\Exports\CategoryDetailExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Http\Requests\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\Category\UpdateCategoryRequest;
use App\Notifications\NewCategoryNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse|BinaryFileResponse
    {
        $status = $request->input('status', 'active');
        $query = Category::query()
            ->when($status !== 'all', function ($query) use ($status) {
                if ($status === 'deleted') {
                    $query->onlyTrashed();
                } elseif ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($status === 'all', function ($query) {
                $query->withTrashed();
            });

        if ($request->boolean('with_count', false)) {
            $query->withCount('products');
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($sortBy = $request->query('sort_by')) {
            $sortDesc = $request->boolean('sort_desc', false);
            $direction = $sortDesc ? 'desc' : 'asc';

            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $direction);
            } else if (in_array($sortBy, ['name', 'updated_at', 'id', 'created_at'])) {
                $query->orderBy($sortBy, $direction);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->query('export') === 'excel') {
            return $this->export($request);
        }

        if ($perPage = $request->query('per_page')) {
            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category->toArray());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = isset($data['status']) ? ($data['status'] === 'active') : true;
        unset($data['status']);

        $category = Category::create($data);

        NotificationService::notifyAdmins(new NewCategoryNotification($category));

        return response()->json($category, 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('status', $data)) {
            $data['is_active'] = $data['status'] === 'active';
            unset($data['status']);
        }

        $category->update($data);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }

    public function restore($id)
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();
        $category->update(['is_active' => false]); // By default restore to inactive for safety

        return response()->json(['message' => 'Category restored successfully']);
    }

    public function export(Request $request)
    {
        $status = $request->input('status', 'active');
        $query = Category::query()
            ->when($status !== 'all', function ($query) use ($status) {
                if ($status === 'deleted') {
                    $query->onlyTrashed();
                } elseif ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($status === 'all', function ($query) {
                $query->withTrashed();
            })
            ->withCount('products');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($sortBy = $request->query('sort_by')) {
            $sortDesc = $request->boolean('sort_desc', false);
            $direction = $sortDesc ? 'desc' : 'asc';

            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $direction);
            } else if (in_array($sortBy, ['name', 'updated_at', 'id', 'created_at'])) {
                $query->orderBy($sortBy, $direction);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $categories = $query->get();

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'Admin';

        $activeCount = Category::where('is_active', true)->count();
        $inactiveCount = Category::where('is_active', false)->count();
        $deletedCount = Category::onlyTrashed()->count();

        $exportData = $categories->map(function ($cat) {
            $statusStr = 'active';
            if ($cat->trashed()) {
                $statusStr = 'deleted';
            } elseif (!$cat->is_active) {
                $statusStr = 'inactive';
            }

            return [
                'created_at_formatted' => $cat->created_at ? $cat->created_at->format('Y-m-d H:i') : '—',
                'name' => $cat->name,
                'slug' => $cat->slug,
                'description' => $cat->description,
                'status' => $statusStr,
                'products_count' => $cat->products_count ?? 0,
            ];
        })->toArray();

        $activeCount = collect($exportData)->where('status', 'active')->count();
        $inactiveCount = collect($exportData)->where('status', 'inactive')->count();
        $deletedCount = collect($exportData)->where('status', 'deleted')->count();

        $exportPayload = [
            'generated_at' => Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'filters' => [
                'search' => $search ?: 'None',
                'status' => ucfirst($status),
            ],
            'categories' => $exportData,
            'summary' => [
                'total_categories' => count($exportData),
                'active_categories' => $activeCount,
                'inactive_categories' => $inactiveCount,
                'deleted_categories' => $deletedCount,
                'total_products' => array_sum(array_column($exportData, 'products_count')),
            ]
        ];

        return Excel::download(
            new CategoriesExport($exportPayload),
            'Categories_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportDetail(Request $request, Category $category)
    {
        $status = $request->input('status');
        $query = $category->products();

        if ($status && $status !== 'all') {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'deleted') {
                $query->onlyTrashed();
            }
        } elseif ($status === 'all') {
            $query->withTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->input('sortBy', $request->input('sort_by', 'created_at'));
        $sortDesc = filter_var($request->input('sortDesc', $request->input('sort_desc', true)), FILTER_VALIDATE_BOOLEAN);
        $allowedSorts = ['name', 'sku', 'price', 'stock', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $productsCollection = $query->get();

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'Admin';

        $exportData = $productsCollection->map(function ($product) {
            $price = (float) ($product->price ?? 0);
            $discount = (int) ($product->discount_percent ?? 0);
            $finalPrice = $price * (1 - ($discount / 100));

            $prodStatus = 'Active';
            if ($product->trashed()) {
                $prodStatus = 'Deleted';
            } elseif (!$product->is_active) {
                $prodStatus = 'Inactive';
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?? '-',
                'status' => $prodStatus,
                'original_price' => '$' . number_format($price, 2),
                'discount_percent' => $discount > 0 ? $discount . '%' : 'None',
                'final_price' => '$' . number_format($finalPrice, 2),
                'stock' => (int) ($product->stock ?? 0),
                'sold_units' => (int) ($product->sold_units ?? 0),
                'created_at' => $product->created_at ? $product->created_at->format('M j, Y') : '-',
            ];
        });

        $totalProducts = $productsCollection->count();
        $totalStock = $productsCollection->sum('stock');
        $totalSold = $productsCollection->sum('sold_units');
        $totalValue = $productsCollection->sum(function ($p) {
            $price = (float) ($p->price ?? 0);
            $discount = (int) ($p->discount_percent ?? 0);
            $finalPrice = $price * (1 - ($discount / 100));
            return $finalPrice * ((int) ($p->stock ?? 0));
        });

        $exportPayload = [
            'generated_at' => Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'category_details' => [
                'name' => $category->name,
                'slug' => $category->slug,
                'status' => $category->trashed() ? 'Deleted' : ($category->is_active ? 'Active' : 'Inactive'),
                'description' => $category->description ?? 'No description provided.',
                'created_at' => $category->created_at ? $category->created_at->format('M j, Y h:i A') : '-',
            ],
            'filters' => [
                'status' => $request->filled('status') ? ucfirst($request->input('status')) : 'All',
                'search' => $request->filled('search') ? $request->input('search') : 'None',
            ],
            'products' => $exportData->toArray(),
            'summary' => [
                'total_products' => $totalProducts,
                'total_stock' => $totalStock,
                'total_sold' => $totalSold,
                'total_inventory_value' => '$' . number_format($totalValue, 2),
            ]
        ];

        $slugStr = Str::slug($category->name) ?: $category->id;
        $filename = 'Category_Detail_Report_' . $slugStr . '_' . Carbon::today()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new CategoryDetailExport($exportPayload),
            $filename
        );
    }
}

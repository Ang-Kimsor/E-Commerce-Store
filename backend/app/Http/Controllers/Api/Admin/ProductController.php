<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Models\Category;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse|BinaryFileResponse
    {
        $query = Product::query()->with(['category' => fn($q) => $q->withTrashed()]);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $status = $request->input('status', 'active');
        $query->when($status !== 'all', function ($query) use ($status) {
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

        if ($sortBy = $request->query('sort_by')) {
            $sortDesc = $request->boolean('sort_desc', false);
            $direction = $sortDesc ? 'desc' : 'asc';

            if ($sortBy === 'category') {
                $query->join('categories', 'products.category_id', '=', 'categories.id')
                    ->orderBy('categories.name', $direction)
                    ->select('products.*');
            } elseif ($sortBy === 'price') {
                $query->orderByRaw('price - (price * discount_percent / 100) ' . $direction);
            } elseif (in_array($sortBy, ['name', 'sku', 'id', 'created_at', 'stock'])) {
                $query->orderBy('products.' . $sortBy, $direction);
            } elseif ($sortBy === 'status') {
                $query->orderBy('products.is_active', $direction);
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        if ($request->query('export') === 'excel') {
            return $this->export($request);
        }

        if ($perPage = $request->query('per_page')) {
            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['is_active'] = isset($data['status']) ? ($data['status'] === 'active') : true;
            unset($data['status']);

            // Process and save images before creating product
            try {
                // Handle image: if it's a data URL, save it to storage
                if (!empty($data['image']) && is_string($data['image']) && str_starts_with($data['image'], 'data:')) {
                    Log::info('Converting data URL image to file during creation', [
                        'user_id' => $request->user()?->id,
                    ]);

                    $savedUrl = $this->saveBase64Image($data['image'], 'products');
                    $data['image'] = $savedUrl;
                }
            } catch (\Throwable $e) {
                // If image processing fails, log error but don't fail the creation
                Log::error('Error during product creation image processing: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $initialStock = $data['stock'] ?? 0;
            $data['stock'] = 0; // Initialize with 0 so the movement can add it
            $product = Product::create($data);

            if ($initialStock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::In,
                    'quantity' => $initialStock,
                    'reference' => 'Initial Stock',
                    'user_id' => $request->user()?->id,
                ]);
                $product->refresh();
            }

            \App\Services\NotificationService::notifyAdmins(new \App\Notifications\NewProductNotification($product));

            return response()->json($product->toArray(), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Product creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Product $product): JsonResponse
    {
        $product->append('sold_units');
        return response()->json($product->load(['category' => fn($q) => $q->withTrashed()])->toArray());
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        Log::info('Product update method called', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'content_length' => $request->header('Content-Length'),
            'request_size_kb' => strlen($request->getContent()) / 1024,
            'headers' => $request->headers->all(),
            'body_keys' => array_keys($request->all()),
            'product_id' => $product->id,
        ]);

        try {
            $data = $request->validated();
            if (array_key_exists('status', $data)) {
                $data['is_active'] = $data['status'] === 'active';
                unset($data['status']);
            }

            $oldRawImage = $product->getRawOriginal('image');
            $oldImagePath = $this->extractRelativeStoragePath($oldRawImage);
            $imageProcessingFailed = false;

            // Process and save images
            try {
                // Handle image: if it's a data URL, save it to storage
                if (!empty($data['image']) && is_string($data['image']) && str_starts_with($data['image'], 'data:')) {
                    Log::info('Converting data URL image to file', [
                        'product_id' => $product->id,
                        'user_id' => $request->user()?->id,
                    ]);

                    $savedUrl = $this->saveBase64Image($data['image'], 'products');
                    $data['image'] = $savedUrl;
                } elseif (array_key_exists('image', $data) && (empty($data['image']) || $data['image'] === '')) {
                    $data['image'] = null;
                }
            } catch (\Throwable $e) {
                $imageProcessingFailed = true;
                // If image processing fails, log error but don't fail the update
                Log::error('Error during product update image processing: ' . $e->getMessage(), [
                    'product_id' => $product->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // If image is removed or replaced with a new one, delete the old image file from storage
            if (!$imageProcessingFailed && array_key_exists('image', $data)) {
                $newImagePath = $this->extractRelativeStoragePath($data['image']);
                if ($oldImagePath && $oldImagePath !== $newImagePath) {
                    $this->deleteProductImageFile($oldRawImage);
                }
            }

            unset($data['stock']); // Don't update stock directly
            $product->update($data);

            return response()->json($product->load(['category' => fn($q) => $q->withTrashed()])->toArray());
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in product update: ' . json_encode($e->errors()));
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Product update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
                'product_id' => $product->id,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function restore($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();
        $product->update(['status' => 'inactive']); // By default restore to inactive for safety
        return response()->json(['message' => 'Product restored successfully']);
    }

    public function export(Request $request)
    {
        $query = Product::query()->with(['category' => fn($q) => $q->withTrashed()]);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $status = $request->input('status', 'active');
        $query->when($status !== 'all', function ($query) use ($status) {
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

        if ($sortBy = $request->query('sort_by')) {
            $sortDesc = $request->boolean('sort_desc', false);
            $direction = $sortDesc ? 'desc' : 'asc';

            if ($sortBy === 'category') {
                $query->join('categories', 'products.category_id', '=', 'categories.id')
                    ->orderBy('categories.name', $direction)
                    ->select('products.*');
            } elseif ($sortBy === 'price') {
                $query->orderByRaw('price - (price * discount_percent / 100) ' . $direction);
            } elseif (in_array($sortBy, ['name', 'sku', 'id', 'created_at', 'stock'])) {
                $query->orderBy('products.' . $sortBy, $direction);
            } elseif ($sortBy === 'status') {
                $query->orderBy('products.is_active', $direction);
            }
        } else {
            $query->orderBy('products.id', 'desc');
        }

        $products = $query->get();

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'Admin';

        $categoryName = 'All Categories';
        if ($request->filled('category_id')) {
            $catObj = Category::withTrashed()->find($request->integer('category_id'));
            if ($catObj) {
                $categoryName = $catObj->name;
                if ($catObj->trashed()) {
                    $categoryName .= ' (Deleted)';
                } elseif (!$catObj->is_active) {
                    $categoryName .= ' (Inactive)';
                }
            }
        }

        $exportData = $products->map(function ($prod) {
            $statusStr = 'Active';
            if ($prod->trashed()) {
                $statusStr = 'Deleted';
            } elseif (!$prod->is_active) {
                $statusStr = 'Inactive';
            }

            $catName = 'Uncategorized';
            if ($prod->category) {
                $catName = $prod->category->name;
                if ($prod->category->trashed()) {
                    $catName .= ' (Deleted)';
                } elseif (!$prod->category->is_active) {
                    $catName .= ' (Inactive)';
                }
            }

            $discountPercent = (float) ($prod->discount_percent ?? 0);
            $origPrice = (float) $prod->price;
            $finalPrice = $discountPercent > 0 ? $origPrice * (1 - $discountPercent / 100) : $origPrice;

            return [
                'created_at_formatted' => $prod->created_at ? $prod->created_at->format('Y-m-d H:i') : '—',
                'name' => $prod->name,
                'sku' => $prod->sku ?: '—',
                'category' => $catName,
                'original_price' => $origPrice,
                'discount_percent' => $discountPercent,
                'final_price' => $finalPrice,
                'current_stock' => $prod->stock,
                'status' => $statusStr,
            ];
        })->toArray();

        $totalProds = count($exportData);
        $activeProds = collect($exportData)->where('status', 'Active')->count();
        $inactiveProds = collect($exportData)->where('status', 'Inactive')->count();
        $deletedProds = collect($exportData)->where('status', 'Deleted')->count();
        $lowStockCount = collect($exportData)->filter(fn($p) => $p['current_stock'] > 0 && $p['current_stock'] <= 10)->count();
        $outOfStockCount = collect($exportData)->filter(fn($p) => $p['current_stock'] <= 0)->count();

        $exportPayload = [
            'generated_at' => \Carbon\Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'filters' => [
                'category' => $categoryName,
                'status' => ucfirst($status),
                'product' => $request->query('search') ?: 'All Products',
                'discount' => 'All',
                'stock_status' => 'All',
                'date_range' => 'All',
            ],
            'products' => $exportData,
            'summary' => [
                'total_products' => $totalProds,
                'active_products' => $activeProds,
                'inactive_products' => $inactiveProds,
                'deleted_products' => $deletedProds,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
            ]
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductsExport($exportPayload),
            'Products_Report_' . \Carbon\Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportDetail(Request $request, Product $product)
    {
        $query = $product->stockMovements()->with(['user' => fn($q) => $q->withTrashed()]);

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
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $sortBy = $request->input('sortBy', 'created_at');
        $sortDesc = filter_var($request->input('sortDesc', true), FILTER_VALIDATE_BOOLEAN);
        $allowedSorts = ['created_at', 'type', 'quantity', 'reference'];
        if ($sortBy === 'quantity') {
            $query->orderByRaw("(CASE WHEN type = '" . StockMovementType::In->value . "' THEN quantity ELSE -quantity END) " . ($sortDesc ? 'desc' : 'asc'));
        } elseif (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        $movementsCollection = $query->get();

        $user = $request->user();
        $generatedBy = $user ? $user->name : 'Admin';

        $exportData = $movementsCollection->map(function ($item) use ($product) {
            $procBy = 'System';
            if ($item->user) {
                $procBy = $item->user->name;
                if ($item->user->trashed()) $procBy .= ' (Deleted)';
                elseif (!$item->user->is_active) $procBy .= ' (Inactive)';
            }
            return [
                'date' => $item->created_at ? $item->created_at->format('M j, Y, h:i A') : '-',
                'product' => $product->name,
                'sku' => $product->sku ?? '-',
                'type' => $item->type,
                'quantity' => $item->quantity,
                'reference' => $item->reference ?? '-',
                'processed_by' => $procBy,
            ];
        });

        $stockIn = $movementsCollection->where('type', StockMovementType::In)->sum('quantity');
        $stockOut = $movementsCollection->where('type', StockMovementType::Out)->sum('quantity');

        $startDateF = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date'))->format('d/m/Y') : 'All';
        $endDateF = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date'))->format('d/m/Y') : 'All';

        $userFilter = 'All';
        if ($request->filled('user_id')) {
            $u = \App\Models\User::withTrashed()->find($request->input('user_id'));
            if ($u) {
                $userFilter = $u->name;
                if ($u->trashed()) $userFilter .= ' (Deleted)';
                elseif (!$u->is_active) $userFilter .= ' (Inactive)';
            }
        }

        $prodCategory = 'Uncategorized';
        if ($product->category) {
            $prodCategory = $product->category->name;
            if ($product->category->trashed()) $prodCategory .= ' (Deleted)';
            elseif (!$product->category->is_active) $prodCategory .= ' (Inactive)';
        }

        $prodStatus = $product->trashed() ? 'Deleted' : ($product->is_active ? 'Active' : 'Inactive');

        $exportPayload = [
            'generated_at' => \Carbon\Carbon::now()->format('M j, Y h:i A'),
            'generated_by' => $generatedBy,
            'product_details' => [
                'name' => $product->name,
                'sku' => $product->sku ?? '-',
                'category' => $prodCategory,
                'status' => $prodStatus,
                'original_price' => '$' . number_format($product->price ?? 0, 2),
                'discount_percent' => $product->discount_percent ?? 0,
                'final_price' => '$' . number_format(($product->price ?? 0) * (1 - (($product->discount_percent ?? 0) / 100)), 2),
                'current_stock' => $product->stock ?? 0,
                'sold_units' => $product->sold_units ?? 0,
            ],
            'filters' => [
                'type' => $request->filled('type') ? $request->input('type') : 'All',
                'user' => $userFilter,
                'date_range' => $startDateF . ' - ' . $endDateF,
            ],
            'movements' => $exportData->toArray(),
            'summary' => [
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'net_movement' => $stockIn - $stockOut,
            ]
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ProductDetailExport($exportPayload),
            'Product_Detail_Report_' . ($product->sku ?: $product->id) . '_' . \Carbon\Carbon::today()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Save base64 image to storage and return public URL
     */
    protected function saveBase64Image(string $base64Data, string $folder = 'products'): string
    {
        // Extract image data from data URL
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64Data, $matches)) {
            $extension = $matches[1];
            $imageData = base64_decode($matches[2]);

            // Validate extension
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($extension), $allowedExtensions)) {
                throw new Exception('Invalid image type');
            }

            // Generate unique filename
            $filename = $folder . '/' . Str::random(40) . '.' . $extension;

            // Save to public disk
            Storage::disk('public')->put($filename, $imageData);

            // Return relative path (not full URL) so it works with any domain
            return '/storage/' . $filename;
        }

        throw new Exception('Invalid base64 image data');
    }

    /**
     * Extract the relative storage path on the public disk from a given URL or path.
     * e.g. "/storage/products/xyz.jpg" -> "products/xyz.jpg"
     *      "/api/storage/products/xyz.jpg" -> "products/xyz.jpg"
     *      "products/xyz.jpg" -> "products/xyz.jpg"
     */
    protected function extractRelativeStoragePath(?string $url): ?string
    {
        if (empty($url) || str_starts_with($url, 'data:')) {
            return null;
        }

        // If it's a full remote URL without /storage/, ignore it
        if ((str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) && !str_contains($url, '/storage/')) {
            return null;
        }

        $parsed = parse_url($url, PHP_URL_PATH) ?? $url;
        $path = ltrim($parsed, '/');

        if (str_starts_with($path, 'api/storage/')) {
            $path = substr($path, strlen('api/storage/'));
        } elseif (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return !empty($path) ? $path : null;
    }

    /**
     * Delete a product image file from the public storage disk if it exists.
     */
    protected function deleteProductImageFile(?string $imageUrl): void
    {
        $path = $this->extractRelativeStoragePath($imageUrl);

        if ($path && Storage::disk('public')->exists($path)) {
            Log::info('Deleting product image from storage', ['path' => $path]);
            Storage::disk('public')->delete($path);
        }
    }
}


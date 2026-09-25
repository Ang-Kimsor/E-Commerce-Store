<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Exports\CustomersExport;
use App\Exports\InventoryExport;
use App\Exports\ProductsExport;
use App\Exports\SalesExport;
use App\Models\Category;
use App\Models\StockMovement;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * 1. Sales Report Endpoint
     */
    public function sales(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::today()->subDays(29)->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();

        if ($request->filled('order_id')) {
            $orderDate = Order::where('id', $request->input('order_id'))->value('created_at');
            if ($orderDate) {
                $orderDate = Carbon::parse($orderDate);
                if ($orderDate < $startDate) $startDate = $orderDate->copy()->startOfDay();
                if ($orderDate > $endDate) $endDate = $orderDate->copy()->endOfDay();
            }
        } elseif ($request->filled('order_number')) {
            $orderDate = Order::where('order_number', $request->input('order_number'))->value('created_at');
            if ($orderDate) {
                $orderDate = Carbon::parse($orderDate);
                if ($orderDate < $startDate) $startDate = $orderDate->copy()->startOfDay();
                if ($orderDate > $endDate) $endDate = $orderDate->copy()->endOfDay();
            }
        }

        $query = Order::query()
            ->with(['user', 'address'])
            ->whereBetween('orders.created_at', [$startDate, $endDate]);

        if ($request->filled('status')) {
            $query->where('orders.status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('orders.payment_status', $request->input('payment_status'));
        }

        if ($request->filled('payment_method')) {
            $paymentMethod = $request->input('payment_method');
            if ($paymentMethod === 'blank') {
                $query->whereNull('orders.payment_method');
            } else {
                $query->where('orders.payment_method', $paymentMethod);
            }
        }

        if ($request->filled('customer_id')) {
            $query->where('orders.customer_id', $request->input('customer_id'));
        }

        if ($request->filled('province')) {
            $query->whereHas('address', function ($q) use ($request) {
                $q->where('province', $request->input('province'));
            });
        }

        if ($request->filled('order_number')) {
            $query->where('orders.order_number', 'like', '%' . $request->input('order_number') . '%');
        }

        if ($request->filled('order_id')) {
            $query->where('orders.id', $request->input('order_id'));
        }

        $paidQuery = (clone $query)->where('orders.payment_status', 'paid');

        // Summary Calculations
        $totalOrders = (clone $query)->count();

        $totalSales = (clone $paidQuery)->sum(DB::raw('orders.total - COALESCE(orders.shipping_cost, 0)'));
        $paidOrdersCount = (clone $paidQuery)->count();
        $averageOrderValue = $paidOrdersCount > 0 ? round($totalSales / $paidOrdersCount, 2) : 0;

        $productsSold = OrderProduct::whereIn('order_id', (clone $paidQuery)->select('orders.id'))->sum('quantity');

        // Charts Data
        // Daily Sales Trend (Line Chart)
        $salesTrend = (clone $paidQuery)
            ->select([
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total - COALESCE(orders.shipping_cost, 0)) as revenue'),
            ])
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dailySales = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $key = $currentDate->toDateString();
            $entry = $salesTrend->get($key);
            $dailySales[] = [
                'date' => $currentDate->format('Y-m-d'),
                'label' => $currentDate->format('M d'),
                'orders' => (int) ($entry->order_count ?? 0),
                'revenue' => round((float) ($entry->revenue ?? 0), 2),
            ];
            $currentDate->addDay();
        }

        // Monthly Sales (Bar Chart)
        $monthlySalesTrend = (clone $paidQuery)
            ->select([
                DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m') as month_key"),
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(orders.total - COALESCE(orders.shipping_cost, 0)) as revenue'),
            ])
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $monthlySales = [];
        $currentMonth = $startDate->copy()->startOfMonth();
        $endMonth = $endDate->copy()->startOfMonth();

        while ($currentMonth <= $endMonth) {
            $key = $currentMonth->format('Y-m');
            $entry = $monthlySalesTrend->get($key);
            $monthlySales[] = [
                'label' => $currentMonth->format('M Y'),
                'orders' => (int) ($entry->order_count ?? 0),
                'revenue' => round((float) ($entry->revenue ?? 0), 2),
            ];
            $currentMonth->addMonth();
        }

        // Order Status Breakdown (Pie Chart)
        $orderStatusCounts = (clone $query)
            ->select('orders.status', DB::raw('COUNT(orders.id) as count'))
            ->groupBy('orders.status')
            ->pluck('count', 'orders.status');

        $orderStatusChart = collect(OrderStatus::cases())->map(function ($enum) use ($orderStatusCounts) {
            return [
                'label' => ucfirst($this->formatEnum($enum->value)),
                'value' => (int) ($orderStatusCounts[$enum->value] ?? 0)
            ];
        })->values();

        // Payment Status Breakdown (Pie Chart)
        $paymentStatusCounts = (clone $query)
            ->select('orders.payment_status', DB::raw('COUNT(orders.id) as count'))
            ->groupBy('orders.payment_status')
            ->pluck('count', 'orders.payment_status');

        $paymentStatusChart = collect(PaymentStatus::cases())->map(function ($enum) use ($paymentStatusCounts) {
            $count = (int) ($paymentStatusCounts[$enum->value] ?? 0);
            if ($enum->value === 'unpaid') {
                $count += (int) ($paymentStatusCounts[''] ?? 0);
            }
            return [
                'label' => ucfirst($this->formatEnum($enum->value)),
                'value' => $count
            ];
        })->values();

        // Revenue by Province (Bar Chart)
        $revenueByProvinceTrend = (clone $paidQuery)
            ->join('addresses', 'orders.address_id', '=', 'addresses.id')
            ->select([
                'addresses.province as province',
                DB::raw('SUM(orders.total - COALESCE(orders.shipping_cost, 0)) as revenue'),
            ])
            ->whereNotNull('addresses.province')
            ->groupBy('addresses.province')
            ->orderByDesc('revenue')
            ->get();

        $revenueByProvince = $revenueByProvinceTrend->map(function ($item) {
            return [
                'label' => $item->province,
                'revenue' => round((float) $item->revenue, 2),
            ];
        })->toArray();

        // Paginated Orders Table
        $perPage = (int) $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'order_number' => 'order_number',
            'order_date' => 'created_at',
            'subtotal' => 'subtotal',
            'shipping_cost' => 'shipping_cost',
            'discount' => 'discount',
            'total_amount' => 'total',
            'payment_status' => 'payment_status',
            'order_status' => 'status',
        ];

        $orderCol = $sortMap[$sortBy] ?? 'created_at';

        $mapOrder = function ($order) {
            $customerName = $order->user?->name ?? 'Guest';
            if ($order->user && $order->user->trashed()) {
                $customerName .= ' (Deleted)';
            } elseif ($order->user && !$order->user->is_active) {
                $customerName .= ' (Inactive)';
            }
            return [
                'id' => $order->id,
                'order_number' => $order->order_number ?? ('#' . $order->id),
                'order_date' => $order->created_at->format('M j, Y, h:i A'),
                'customer' => $customerName,
                'total_items' => $order->items_count ?? 0,
                'subtotal' => round((float) ($order->subtotal ?? $order->total), 2),
                'shipping_cost' => round((float) ($order->shipping_cost ?? 0), 2),
                'discount' => round((float) ($order->discount ?? 0), 2),
                'total_amount' => round((float) $order->total, 2),
                'payment_status' => strtoupper($this->formatEnum($order->payment_status) ?: 'UNPAID'),
                'order_status' => strtoupper($this->formatEnum($order->status)),
                'payment_method' => $order->payment_method ? ucfirst($order->payment_method->value) : '—',
            ];
        };

        if ($request->input('export') === 'excel') {
            $ordersCollection = (clone $query)->with(['items.product.category', 'user'])->withCount('items')->orderBy($orderCol, $sortDir)->get();
            if ($ordersCollection->isEmpty()) {
                return response()->json([
                    'message' => 'No sales data found for selected filters.'
                ], 422);
            }

            $exportData = $ordersCollection->map($mapOrder)->toArray();

            $orderDetailsData = [];
            foreach ($ordersCollection as $order) {
                $custName = $order->user?->name ?? 'Guest';
                if ($order->user && $order->user->trashed()) {
                    $custName .= ' (Deleted)';
                } elseif ($order->user && !$order->user->is_active) {
                    $custName .= ' (Inactive)';
                }
                foreach ($order->items as $item) {
                    $prodName = $item->product_name ?? ($item->product?->name ?? 'Unknown');
                    if ($item->product && $item->product->trashed()) {
                        $prodName .= ' (Deleted)';
                    } elseif ($item->product && !$item->product->is_active) {
                        $prodName .= ' (Inactive)';
                    }
                    $catName = $item->product?->category?->name ?? 'Uncategorized';
                    if ($item->product?->category && $item->product->category->trashed()) {
                        $catName .= ' (Deleted)';
                    } elseif ($item->product?->category && !$item->product->category->is_active) {
                        $catName .= ' (Inactive)';
                    }
                    $orderDetailsData[] = [
                        'order_number' => $order->order_number ?? ('#' . $order->id),
                        'customer' => $custName,
                        'product' => $prodName,
                        'sku' => $item->product_sku ?? ($item->product?->sku ?? '-'),
                        'category' => $catName,
                        'full_price' => $item->product?->price ?? $item->unit_price,
                        'discount' => $item->product_discount ?? ($item->product?->discount_percent ?? 0),
                        'final_price' => $item->unit_price,
                        'quantity' => $item->quantity,
                        'item_total' => $item->total,
                    ];
                }
            }

            $statusFilter = $request->filled('status') ? ucfirst($this->formatEnum($request->input('status'))) : 'All';
            $paymentStatusFilter = $request->filled('payment_status') ? ucfirst($this->formatEnum($request->input('payment_status'))) : 'All';
            $paymentMethodFilter = $request->filled('payment_method') ? ucfirst($request->input('payment_method')) : 'All';

            $customerFilter = 'All';
            if ($request->filled('customer_id')) {
                $c = User::withTrashed()->find($request->input('customer_id'));
                if ($c) {
                    $cName = $c->name;
                    if ($c->trashed()) $cName .= ' (Deleted)';
                    elseif (!$c->is_active) $cName .= ' (Inactive)';
                    $customerFilter = $cName;
                }
            }

            $dateRangeFilter = 'All';
            if ($startDate && $endDate) {
                $dateRangeFilter = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
            }

            $orderFilter = 'All';
            if ($request->filled('order_id')) {
                $o = Order::find($request->input('order_id'));
                $orderFilter = $o ? $o->order_number : 'All';
            }

            $provinceFilter = $request->filled('province') ? $request->input('province') : 'All';

            $user = $request->user();
            $generatedBy = $user ? $user->name : 'Admin';

            $exportPayload = [
                'generated_at' => Carbon::now()->format('M j, Y h:i A'),
                'generated_by' => $generatedBy,
                'filters' => [
                    'status' => $statusFilter,
                    'payment_status' => $paymentStatusFilter,
                    'payment_method' => $paymentMethodFilter,
                    'customer' => $customerFilter,
                    'date_range' => $dateRangeFilter,
                    'order' => $orderFilter,
                    'province' => $provinceFilter,
                ],
                'orders' => $exportData,
                'order_details' => $orderDetailsData,
                'summary' => [
                    'revenue' => round($totalSales, 2),
                    'orders' => $totalOrders,
                    'products_sold' => (int) $productsSold,
                    'average_order_value' => $averageOrderValue,
                ]
            ];

            return Excel::download(
                new SalesExport($exportPayload),
                'Sales_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
            );
        }

        $ordersPaginated = (clone $query)
            ->withCount('items')
            ->orderBy($orderCol, $sortDir)
            ->paginate($perPage);

        $tableData = collect($ordersPaginated->items())->map($mapOrder);

        $data = [
            'title' => 'Sales Report',
            'period' => ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')],
            'summary' => [
                'total_orders' => $totalOrders,
                'total_sales' => round($totalSales, 2),
                'products_sold' => (int) $productsSold,
                'average_order_value' => $averageOrderValue,
            ],
            'charts' => [
                'daily_sales' => $dailySales,
                'monthly_sales' => $monthlySales,
                'order_status' => $orderStatusChart,
                'payment_status' => $paymentStatusChart,
                'revenue_by_province' => $revenueByProvince,
            ],
            'table' => [
                'data' => $tableData,
                'current_page' => $ordersPaginated->currentPage(),
                'last_page' => $ordersPaginated->lastPage(),
                'per_page' => $ordersPaginated->perPage(),
                'total' => $ordersPaginated->total(),
            ]
        ];

        return response()->json($data);
    }

    /**
     * 2. Product Report Endpoint
     */
    public function products(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::today()->subDays(29)->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();

        $query = Product::withTrashed()->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('id', $request->input('product_id'));
        }

        if ($request->filled('status')) {
            $statusInput = strtolower($request->input('status'));
            if ($statusInput === 'active') {
                $query->whereNull('deleted_at')->where('is_active', true);
            } elseif ($statusInput === 'inactive') {
                $query->whereNull('deleted_at')->where('is_active', false);
            } elseif ($statusInput === 'deleted' || $statusInput === 'trashed') {
                $query->onlyTrashed();
            }
        }

        if ($request->filled('discount')) {
            $discountFilter = $request->input('discount');
            if ($discountFilter === 'has_discount') {
                $query->where('discount_percent', '>', 0);
            } elseif ($discountFilter === 'no_discount') {
                $query->where(function ($q) {
                    $q->whereNull('discount_percent')
                        ->orWhere('discount_percent', '<=', 0);
                });
            }
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->input('stock_status');
            if ($stockStatus === 'in_stock') {
                $query->where('stock', '>', 10);
            } elseif ($stockStatus === 'low_stock') {
                $query->where('stock', '<=', 10)->where('stock', '>', 0);
            } elseif ($stockStatus === 'out_of_stock') {
                $query->where('stock', '<=', 0);
            }
        }


        $products = (clone $query)->get();

        $soldItems = OrderProduct::select([
            'product_id',
            DB::raw('SUM(quantity) as total_sold')
        ])
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $productList = $products->map(function ($product) use ($soldItems) {
            $discountPercent = (int) ($product->discount_percent ?? 0);
            $origPrice = round((float) $product->price, 2);
            $finalPrice = $discountPercent > 0 ? round($origPrice * (1 - $discountPercent / 100), 2) : $origPrice;

            $statusStr = $product->trashed() ? 'Deleted' : ($product->is_active ? 'Active' : 'Inactive');

            $catName = $product->category?->name ?? 'Uncategorized';
            if ($product->category && $product->category->trashed()) {
                $catName .= ' (Deleted)';
            } elseif ($product->category && !$product->category->is_active) {
                $catName .= ' (Inactive)';
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'created_at_formatted' => $product->created_at ? $product->created_at->format('M j, Y, h:i A') : 'N/A',
                'sku' => $product->sku ?: ('PRD-' . $product->id),
                'category' => $catName,
                'unit' => $product->unit ?? 'pcs',
                'original_price' => $origPrice,
                'final_price' => $finalPrice,
                'discount_percent' => $discountPercent,
                'current_stock' => (int) $product->stock,
                'sold_units' => (int) ($soldItems->get($product->id)?->total_sold ?? 0),
                'status' => $statusStr,
            ];
        });

        $addedTrend = (clone $query)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $productsAddedOverTime = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $key = $currentDate->toDateString();
            $entry = $addedTrend->get($key);
            $productsAddedOverTime[] = [
                'date' => $currentDate->format('Y-m-d'),
                'label' => $currentDate->format('M d'),
                'count' => (int) ($entry->count ?? 0),
            ];
            $currentDate->addDay();
        }

        // Summary Cards
        $activeProducts = $productList->where('status', 'Active')->count();
        $inactiveProducts = $productList->where('status', 'Inactive')->count();
        $deletedProducts = $productList->where('status', 'Deleted')->count();
        $totalProducts = $productList->count();
        $lowStockCount = $productList->filter(fn($p) => $p['current_stock'] <= 10 && $p['current_stock'] > 0)->count();
        $outOfStockCount = $productList->filter(fn($p) => $p['current_stock'] <= 0)->count();

        // Charts
        $inStockCount = $productList->filter(fn($p) => $p['current_stock'] > 10)->count();

        $productsByCategory = $productList->groupBy('category')->map(function ($items, $catName) {
            return [
                'label' => $catName,
                'value' => $items->count()
            ];
        })->sortByDesc('value')->take(10)->values();

        $stockByCategory = $productList->groupBy('category')->map(function ($items, $catName) {
            return [
                'label' => $catName,
                'value' => $items->sum('current_stock')
            ];
        })->sortByDesc('value')->take(10)->values();

        $p0_50 = $productList->filter(fn($p) => $p['final_price'] >= 0 && $p['final_price'] <= 50)->count();
        $p50_100 = $productList->filter(fn($p) => $p['final_price'] > 50 && $p['final_price'] <= 100)->count();
        $p100_500 = $productList->filter(fn($p) => $p['final_price'] > 100 && $p['final_price'] <= 500)->count();
        $p500_plus = $productList->filter(fn($p) => $p['final_price'] > 500)->count();

        $priceRangeDistribution = [
            ['label' => '$0 - $50', 'value' => $p0_50],
            ['label' => '$50 - $100', 'value' => $p50_100],
            ['label' => '$100 - $500', 'value' => $p100_500],
            ['label' => '$500+', 'value' => $p500_plus],
        ];

        $productStatusChart = [
            ['label' => 'Active Products', 'value' => $activeProducts],
            ['label' => 'Inactive Products', 'value' => $inactiveProducts],
            ['label' => 'Deleted Products', 'value' => $deletedProducts],
        ];

        // Paginated Table
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'name');
        $sortDesc = strtolower($request->input('sort_dir', 'asc')) === 'desc';

        $sortedList = $productList->sortBy($sortBy, SORT_REGULAR, $sortDesc)->values();

        if ($request->input('export') === 'excel') {
            if ($sortedList->isEmpty()) {
                return response()->json([
                    'message' => 'No product data found for selected filters.'
                ], 422);
            }

            $catFilter = 'All';
            if ($request->filled('category_id')) {
                $c = Category::withTrashed()->find($request->input('category_id'));
                if ($c) {
                    $cName = $c->name;
                    if ($c->trashed()) $cName .= ' (Deleted)';
                    elseif (!$c->is_active) $cName .= ' (Inactive)';
                    $catFilter = $cName;
                }
            }

            $statusFilter = 'All';
            if ($request->filled('status')) {
                $statusFilter = ucfirst($request->input('status'));
            }

            $prodFilter = 'All Products';
            if ($request->filled('product_id')) {
                $p = Product::withTrashed()->find($request->input('product_id'));
                if ($p) {
                    $pName = $p->sku ? "{$p->name} ({$p->sku})" : $p->name;
                    if ($p->trashed()) $pName .= ' (Deleted)';
                    elseif (!$p->is_active) $pName .= ' (Inactive)';
                    $prodFilter = $pName;
                }
            }

            $discFilter = 'All';
            if ($request->filled('discount')) {
                $discFilter = $request->input('discount') === 'has_discount' ? 'Has Discount' : ($request->input('discount') === 'no_discount' ? 'No Discount' : 'All');
            }

            $stockFilter = 'All';
            if ($request->filled('stock_status')) {
                $st = $request->input('stock_status');
                if ($st === 'in_stock') $stockFilter = 'In Stock';
                elseif ($st === 'low_stock') $stockFilter = 'Low Stock';
                elseif ($st === 'out_of_stock') $stockFilter = 'Out of Stock';
            }

            $dateRangeFilter = 'All';
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $dateRangeFilter = Carbon::parse($request->input('start_date'))->format('d/m/Y') . ' - ' . Carbon::parse($request->input('end_date'))->format('d/m/Y');
            }


            $user = $request->user();
            $generatedBy = $user ? $user->name : 'Admin';

            $exportPayload = [
                'generated_at' => Carbon::now()->format('M j, Y h:i A'),
                'generated_by' => $generatedBy,
                'filters' => [
                    'category' => $catFilter,
                    'status' => $statusFilter,
                    'product' => $prodFilter,
                    'discount' => $discFilter,
                    'stock_status' => $stockFilter,
                    'date_range' => $dateRangeFilter,
                ],
                'products' => $sortedList->toArray(),
                'summary' => [
                    'total_products' => $totalProducts,
                    'active_products' => $activeProducts,
                    'inactive_products' => $inactiveProducts,
                    'deleted_products' => $deletedProducts,
                    'low_stock' => $lowStockCount,
                    'out_of_stock' => $outOfStockCount,
                ]
            ];

            return Excel::download(
                new ProductsExport($exportPayload),
                'Product_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
            );
        }

        $sliced = $sortedList->slice(($page - 1) * $perPage, $perPage)->values();

        $data = [
            'title' => 'Product Report',
            'period' => ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')],
            'summary' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'inactive_products' => $inactiveProducts,
                'deleted_products' => $deletedProducts,
            ],
            'charts' => [
                'products_added_over_time' => $productsAddedOverTime,
                'products_by_category' => $productsByCategory,
                'price_range_distribution' => $priceRangeDistribution,
            ],
            'table' => [
                'data' => $sliced,
                'current_page' => $page,
                'last_page' => ceil($totalProducts / max($perPage, 1)),
                'per_page' => $perPage,
                'total' => $totalProducts,
            ]
        ];

        return response()->json($data);
    }

    /**
     * 3. Inventory Report Endpoint
     */
    public function inventory(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id');
        $type = $request->input('stock_status') ?: $request->input('type');

        $query = StockMovement::with(['product.category', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($categoryId) {
            $query->whereHas('product', fn($pq) => $pq->where('category_id', $categoryId));
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($type) {
            $query->where('type', strtoupper($type));
        }

        // Summary Calculations across filtered products
        $productsQuery = Product::withTrashed();
        if ($categoryId) {
            $productsQuery->where('category_id', $categoryId);
        }
        if ($productId) {
            $productsQuery->where('id', $productId);
        }
        $allProducts = $productsQuery->get();
        $movements = (clone $query)->get();
        $totalStockIn = $movements->where('type', StockMovementType::In)->sum('quantity');
        $totalStockOut = $movements->where('type', StockMovementType::Out)->sum('quantity');
        $netMovement = $totalStockIn - $totalStockOut;

        $inStockCount = $allProducts->filter(fn($p) => $p->stock > 0)->count();
        $lowStockCount = $allProducts->filter(fn($p) => $p->stock <= 10 && $p->stock > 0)->count();
        $outOfStockCount = $allProducts->filter(fn($p) => $p->stock <= 0)->count();

        $stockStatusDistribution = [
            ['label' => 'In Stock', 'value' => $inStockCount],
            ['label' => 'Low Stock', 'value' => $lowStockCount],
            ['label' => 'Out of Stock', 'value' => $outOfStockCount],
        ];

        // Charts
        // 1. Stock Movement Over Time (Line Chart IN vs OUT)
        $movementsByDate = $movements->groupBy(function ($m) {
            return $m->created_at->format('Y-m-d');
        });

        $movementOverTime = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $key = $currentDate->format('Y-m-d');
            $dayMovements = $movementsByDate->get($key, collect([]));
            $movementOverTime[] = [
                'date' => $currentDate->format('M j'),
                'in' => $dayMovements->where('type', StockMovementType::In)->sum('quantity'),
                'out' => $dayMovements->where('type', StockMovementType::Out)->sum('quantity'),
            ];
            $currentDate->addDay();
        }

        // 2. Stock Movement by Product (Bar Chart)
        $movementByProduct = $movements->groupBy('product_id')->map(function ($productMovements) {
            $first = $productMovements->first();
            $productName = $first->product?->name ?? 'Unknown';
            if ($first->product && $first->product->trashed()) {
                $productName .= ' (Deleted)';
            } elseif ($first->product && !$first->product->is_active) {
                $productName .= ' (Inactive)';
            }
            $totalMovement = $productMovements->sum('quantity');
            return [
                'label' => $productName,
                'value' => $totalMovement
            ];
        })->sortByDesc('value')->take(10)->values();

        // Paginated Stock Movements Table
        $perPage = (int) $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'date' => 'created_at',
            'created_at' => 'created_at',
            'type' => 'type',
            'quantity' => 'quantity',
            'reference' => 'reference',
        ];
        $orderCol = $sortMap[$sortBy] ?? 'created_at';

        $mapMovement = function ($m) {
            $prodName = $m->product?->name ?? 'Deleted Product';
            if ($m->product && $m->product->trashed()) {
                $prodName .= ' (Deleted)';
            } elseif ($m->product && !$m->product->is_active) {
                $prodName .= ' (Inactive)';
            }

            $userName = $m->user?->name ?? 'System';
            if ($m->user && $m->user->trashed()) {
                $userName .= ' (Deleted)';
            } elseif ($m->user && !$m->user->is_active) {
                $userName .= ' (Inactive)';
            }

            return [
                'id' => $m->id,
                'date' => $m->created_at ? $m->created_at->format('M j, Y, g:i A') : '—',
                'product' => $prodName,
                'sku' => $m->product?->sku ?? '—',
                'type' => $m->type,
                'quantity' => (int) $m->quantity,
                'reference' => $m->reference ?: '—',
                'processed_by' => $userName,
            ];
        };

        if ($request->input('export') === 'excel') {
            $movementsCollection = (clone $query)->orderBy($orderCol, $sortDir)->get();
            $exportData = $movementsCollection->map($mapMovement)->toArray();

            $user = $request->user();
            $generatedBy = $user ? $user->name : 'Admin';

            $catFilter = 'All';
            if ($categoryId) {
                $c = Category::withTrashed()->find($categoryId);
                if ($c) {
                    $cName = $c->name;
                    if ($c->trashed()) $cName .= ' (Deleted)';
                    elseif (!$c->is_active) $cName .= ' (Inactive)';
                    $catFilter = $cName;
                }
            }

            $prodFilter = 'All';
            if ($productId) {
                $p = Product::withTrashed()->find($productId);
                if ($p) {
                    $pName = $p->sku ? "{$p->name} ({$p->sku})" : $p->name;
                    if ($p->trashed()) $pName .= ' (Deleted)';
                    elseif (!$p->is_active) $pName .= ' (Inactive)';
                    $prodFilter = $pName;
                }
            }
            $typeFilter = $type ? ucfirst(strtolower($type)) : 'All';

            $userFilter = 'All';
            if ($userId) {
                $u = User::withTrashed()->find($userId);
                if ($u) {
                    $uName = $u->name;
                    if ($u->trashed()) $uName .= ' (Deleted)';
                    elseif (!$u->is_active) $uName .= ' (Inactive)';
                    $userFilter = $uName;
                }
            }

            $exportPayload = [
                'generated_at' => Carbon::now()->format('M j, Y h:i A'),
                'generated_by' => $generatedBy,
                'filters' => [
                    'category' => $catFilter,
                    'product' => $prodFilter,
                    'type' => $typeFilter,
                    'user' => $userFilter,
                    'date_range' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
                ],
                'inventory' => $exportData,
                'summary' => [
                    'in_stock' => $inStockCount,
                    'low_stock' => $lowStockCount,
                    'out_of_stock' => $outOfStockCount,
                    'stock_in' => $totalStockIn,
                    'stock_out' => $totalStockOut,
                    'net_movement' => $netMovement,
                ]
            ];

            return Excel::download(
                new InventoryExport($exportPayload),
                'Inventory_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
            );
        }

        $paginated = (clone $query)->orderBy($orderCol, $sortDir)->paginate($perPage);
        $tableData = collect($paginated->items())->map($mapMovement);

        $data = [
            'title' => 'Inventory Report',
            'period' => ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')],
            'summary' => [
                'in_stock' => $inStockCount,
                'low_stock' => $lowStockCount,
                'out_of_stock' => $outOfStockCount,
                'stock_in' => $totalStockIn,
                'stock_out' => $totalStockOut,
                'net_movement' => $netMovement,
            ],
            'charts' => [
                'movement_over_time' => $movementOverTime,
                'movement_by_product' => $movementByProduct,
                'stock_status_distribution' => $stockStatusDistribution,
            ],
            'table' => [
                'data' => $tableData,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ];

        return response()->json($data);
    }

    /**
     * 4. Customer Report Endpoint
     */
    public function customers(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::today()->subDays(29)->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::today()->endOfDay();

        $query = User::withTrashed()->where('role', UserRole::Customer)
            ->where('name', '!=', 'Test User');

        if ($request->filled('customer_id')) {
            $query->where('id', $request->input('customer_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = strtolower($request->input('status'));
            if ($status === 'active') {
                $query->whereNull('deleted_at')->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->whereNull('deleted_at')->where('is_active', false);
            } elseif ($status === 'deleted' || $status === 'trashed') {
                $query->onlyTrashed();
            }
        }

        $customers = $query->get();

        // Customer order aggregates
        $customerStats = Order::select([
            'customer_id',
            DB::raw('COUNT(*) as orders_count'),
            DB::raw('SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed_orders_count'),
            DB::raw('SUM(total - COALESCE(shipping_cost, 0)) as total_spent'),
            DB::raw('MAX(created_at) as last_order_date'),
        ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNot('status', OrderStatus::Cancelled)
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        // Customer items purchased
        $customerItems = OrderProduct::select([
            'orders.customer_id',
            DB::raw('SUM(order_products.quantity) as products_purchased')
        ])
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNot('orders.status', OrderStatus::Cancelled)
            ->groupBy('orders.customer_id')
            ->get()
            ->keyBy('customer_id');

        $customerList = $customers->map(function ($customer) use ($customerStats, $customerItems) {
            $stats = $customerStats->get($customer->id);
            $items = $customerItems->get($customer->id);

            $ordersCount = (int) ($stats->orders_count ?? 0);
            $completedOrdersCount = (int) ($stats->completed_orders_count ?? 0);
            $totalSpent = round((float) ($stats->total_spent ?? 0), 2);
            $purchased = (int) ($items->products_purchased ?? 0);
            $avgOrderValue = $ordersCount > 0 ? round($totalSpent / $ordersCount, 2) : 0;

            $statusStr = $customer->trashed() ? 'Deleted' : ($customer->is_active ? 'Active' : 'Inactive');

            return [
                'id' => $customer->id,
                'registered_date' => Carbon::parse($customer->created_at)->format('M j, Y, h:i A'),
                'customer' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone ?? '—',
                'orders' => $ordersCount,
                'completed_orders' => $completedOrdersCount,
                'products_purchased' => $purchased,
                'total_spent' => $totalSpent,
                'avg_order_value' => $avgOrderValue,
                'last_purchase' => $stats?->last_order_date ? Carbon::parse($stats->last_order_date)->format('M j, Y, h:i A') : 'Never',
                'status' => $statusStr,
                'is_active' => $customer->is_active,
                'is_deleted' => $customer->trashed(),
            ];
        });

        // Summary Cards
        $totalCustomers = $customerList->count();
        $activeCustomers = $customerList->where('status', 'Active')->count();
        $inactiveCustomers = $customerList->where('status', 'Inactive')->count();
        $deletedCustomers = $customerList->where('status', 'Deleted')->count();
        $customersWithOrders = $customerList->where('orders', '>', 0)->count();
        $totalRevenue = $customerList->sum('total_spent');
        $avgSpending = $customersWithOrders > 0 ? $totalRevenue / $customersWithOrders : 0;
        $totalOrders = $customerList->sum('orders');



        $topSpendingChart = $customerList->sortByDesc('total_spent')->take(10)->values()->map(fn($c) => [
            'label' => $c['customer'],
            'value' => $c['total_spent']
        ]);

        $topOrdersChart = $customerList->sortByDesc('orders')->take(10)->values()->map(fn($c) => [
            'label' => $c['customer'],
            'value' => $c['orders']
        ]);

        $customersByDate = User::withTrashed()->select([
            DB::raw("DATE(created_at) as date_key"),
            DB::raw('COUNT(*) as count')
        ])
            ->where('role', UserRole::Customer)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date_key')
            ->get()
            ->keyBy('date_key');

        $customerGrowthChart = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $key = $currentDate->format('Y-m-d');
            $count = $customersByDate->get($key)?->count ?? 0;
            $customerGrowthChart[] = [
                'label' => $currentDate->format('M j'),
                'value' => (int) $count
            ];
            $currentDate->addDay();
        }

        // Paginated Table
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'customer');
        $sortDesc = strtolower($request->input('sort_dir', 'asc')) === 'desc';

        $sortedList = $customerList->sortBy($sortBy, SORT_REGULAR, $sortDesc)->values();

        if ($request->input('export') === 'excel') {
            $user = $request->user();
            $generatedBy = $user ? $user->name : 'Admin';

            $custFilter = 'All';
            if ($request->filled('customer_id')) {
                $c = User::withTrashed()->find($request->input('customer_id'));
                if ($c) {
                    $cName = $c->name;
                    if ($c->trashed()) $cName .= ' (Deleted)';
                    elseif (!$c->is_active) $cName .= ' (Inactive)';
                    $custFilter = $cName;
                }
            }

            $exportPayload = [
                'generated_at' => Carbon::now()->format('M j, Y h:i A'),
                'generated_by' => $generatedBy,
                'filters' => [
                    'status' => $request->filled('status') ? ucfirst($request->input('status')) : 'All',
                    'customer' => $custFilter,
                    'date_range' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
                ],
                'customers' => $sortedList->toArray(),
                'summary' => [
                    'total_customers' => $totalCustomers,
                    'active_customers' => $activeCustomers,
                    'inactive_customers' => $inactiveCustomers,
                    'deleted_customers' => $deletedCustomers,
                    'customers_with_orders' => $customersWithOrders,
                    'total_orders' => $totalOrders,
                    'revenue' => round($totalRevenue, 2),
                    'avg_spending' => round($avgSpending, 2),
                ]
            ];

            return Excel::download(
                new CustomersExport($exportPayload),
                'Customers_Report_' . Carbon::today()->format('Y-m-d') . '.xlsx'
            );
        }

        $sliced = $sortedList->slice(($page - 1) * $perPage, $perPage)->values();

        $data = [
            'title' => 'Customer Report',
            'period' => ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')],
            'summary' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'inactive_customers' => $inactiveCustomers,
                'deleted_customers' => $deletedCustomers,
                'customers_with_orders' => $customersWithOrders,
                'revenue' => round($totalRevenue, 2),
                'avg_spending' => round($avgSpending, 2),
            ],
            'charts' => [
                'top_spending' => $topSpendingChart,
                'top_orders' => $topOrdersChart,
                'customer_growth' => $customerGrowthChart,
            ],
            'table' => [
                'data' => $sliced,
                'current_page' => $page,
                'last_page' => ceil($totalCustomers / max($perPage, 1)),
                'per_page' => $perPage,
                'total' => $totalCustomers,
            ]
        ];

        return response()->json($data);
    }

    private function formatEnum($val): string
    {
        if (is_null($val)) return '';
        if (is_object($val)) {
            return (string) ($val->value ?? $val->name ?? '');
        }
        return (string) $val;
    }
}

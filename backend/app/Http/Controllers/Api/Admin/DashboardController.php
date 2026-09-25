<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        return $this->index();
    }

    public function index(): JsonResponse
    {
        // --- 1. Top Level KPI Cards (Totals) ---
        $today = Carbon::today();

        $ordersQuery = Order::query()->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned]);
        $ordersCount = (clone $ordersQuery)->count();
        $revenue = Order::query()->where('payment_status', PaymentStatus::Paid)->sum(DB::raw('total - COALESCE(shipping_cost, 0)'));

        $ordersTodayQuery = Order::query()
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])
            ->whereDate('created_at', $today);
        $ordersTodayCount = (clone $ordersTodayQuery)->count();

        $revenueToday = round(Order::query()->where('payment_status', PaymentStatus::Paid)->whereDate('created_at', $today)->sum(DB::raw('total - COALESCE(shipping_cost, 0)')), 2);

        $customerQuery = User::query()->where('role', UserRole::Customer)->where('is_active', true);

        $totalCustomers = (clone $customerQuery)->count();
        $newCustomersToday = (clone $customerQuery)->whereDate('created_at', $today)->count();

        $totalProducts = Product::where('is_active', true)->count();
        $outOfStockProducts = Product::where('is_active', true)->where('stock', '<=', 0)->count();
        $lowStockCount = Product::where('is_active', true)->where('stock', '<=', 10)->where('stock', '>', 0)->count();

        // --- 2. Trend Data (Revenue by Day and Year) ---
        // 2a. Weekly Trend (Last 7 Days)
        $weekStartDate = Carbon::today()->subDays(6);
        $weekSalesTrend = Order::select([
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN (total - COALESCE(shipping_cost, 0)) ELSE 0 END) as revenue"),
        ])
            ->where('created_at', '>=', $weekStartDate->startOfDay())
            ->whereNot('status', OrderStatus::Cancelled)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trendWeek = collect(range(0, 6))->map(function (int $offset) use ($weekStartDate, $weekSalesTrend) {
            $date = $weekStartDate->copy()->addDays($offset);
            $key = $date->toDateString();
            $entry = $weekSalesTrend->get($key);

            return [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('M d'),
                'orders' => (int) ($entry->order_count ?? 0),
                'revenue' => round((float) ($entry->revenue ?? 0), 2),
            ];
        });

        // 2b. Yearly Trend (Last 12 Months)
        $yearStartDate = Carbon::today()->startOfYear();
        $yearSalesTrend = Order::select([
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN (total - COALESCE(shipping_cost, 0)) ELSE 0 END) as revenue"),
        ])
            ->where('created_at', '>=', $yearStartDate->startOfDay())
            ->whereNot('status', OrderStatus::Cancelled)
            ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        $trendYear = collect(range(0, 11))->map(function (int $offset) use ($yearStartDate, $yearSalesTrend) {
            $date = $yearStartDate->copy()->addMonths($offset);
            $key = $date->format('Y-m');
            $entry = $yearSalesTrend->get($key);

            return [
                'date' => $date->format('Y-m-01'),
                'label' => $date->format('M Y'),
                'orders' => (int) ($entry->order_count ?? 0),
                'revenue' => round((float) ($entry->revenue ?? 0), 2),
            ];
        });

        // --- 3. Order Statuses (Doughnut Chart) ---
        $statusBreakdown = Order::select([
            DB::raw('status'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total - COALESCE(shipping_cost, 0)) as revenue'),
        ])
            ->groupBy('status')
            ->get()
            ->map(function ($row) {
                $status = $row->status instanceof OrderStatus ? $row->status->value : (string) $row->status;

                return [
                    'status' => $status,
                    'count' => (int) $row->count,
                    'revenue' => round((float) $row->revenue, 2),
                ];
            });

        // --- 4. Top Categories (Doughnut Chart) ---
        $categoryBreakdown = Category::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->limit(5)
            ->get()
            ->map(fn($cat) => [
                'name'  => $cat->name,
                'count' => $cat->products_count,
            ]);

        // --- 5. Top Selling Products (Table) ---
        $topProducts = OrderProduct::select([
            'product_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(total) as total_revenue'),
        ])
            ->with('product:id,name,price,image')
            ->whereHas('order', function ($query) {
                $query->whereNot('status', OrderStatus::Cancelled);
            })
            ->whereHas('product', function ($query) {
                $query->where('is_active', true);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function (OrderProduct $item) {
                return [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name ?? $item->product_name ?? 'Unknown',
                    'image' => $item->product?->image,
                    'quantity' => (int) $item->total_quantity,
                    'revenue' => round((float) $item->total_revenue, 2),
                ];
            });

        // --- 6. Low Stock Warning (Table) ---
        $lowStockProducts = Product::select(['id', 'name', 'stock', 'image'])
            ->where('is_active', true)
            ->where('stock', '<=', 10)
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        // --- 7. Recent Customers (Table) ---
        $recentCustomers = User::query()
            ->where('role', UserRole::Customer)
            ->where('is_active', true)
            ->whereNotNull('name')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'email', 'avatar_url', 'created_at']);

        // --- 8. Recent Orders (Table) ---

        $recentOrdersList = Order::with('user:id,name')
            ->latest()
            ->limit(10)
            ->get();

        // --- Revenue by Province ---
        $revenueByProvince = Order::query()
            ->join('addresses', 'orders.address_id', '=', 'addresses.id')
            ->select([
                'addresses.province as province',
                DB::raw("SUM(CASE WHEN orders.payment_status = 'paid' THEN (orders.total - COALESCE(orders.shipping_cost, 0)) ELSE 0 END) as revenue"),
            ])
            ->whereNotNull('addresses.province')
            ->groupBy('addresses.province')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->province,
                    'revenue' => round((float) $item->revenue, 2),
                ];
            })->toArray();

        return response()->json([
            'generated_at' => Carbon::now()->toIso8601String(),
            'period' => [
                'start' => $weekStartDate->format('Y-m-d'),
                'end' => $today->format('Y-m-d'),
            ],
            'totals' => [
                'orders_today' => $ordersTodayCount,
                'revenue_today' => $revenueToday,
                'new_customers_today' => $newCustomersToday,
                'products' => $totalProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'low_stock_count' => $lowStockCount,
                'orders' => $ordersCount,
                'revenue' => round($revenue, 2),
                'customers' => $totalCustomers,
            ],
            'trend_week' => $trendWeek,
            'trend_year' => $trendYear,
            'status_breakdown' => $statusBreakdown,
            'category_breakdown' => $categoryBreakdown,
            'revenue_by_province' => $revenueByProvince,
            'top_products' => $topProducts,
            'low_stock_products' => $lowStockProducts,
            'recent_customers' => $recentCustomers->map(function (User $customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'avatar_url' => $customer->avatar_url,
                    'joined_at' => $customer->created_at?->toIso8601String(),
                ];
            }),
            'recent_orders' => $recentOrdersList,
        ]);
    }
}

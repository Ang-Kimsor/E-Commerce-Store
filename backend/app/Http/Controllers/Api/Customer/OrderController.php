<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Order\StoreOrderRequest;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderStatusHistory;
use App\Models\PaymentStatusHistory;
use App\Models\Product;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['items.product', 'address', 'user'])
            ->withCount('items')
            ->latest();

        $user = $request->user();

        $query->where('customer_id', $user->id);

        return response()->json(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        abort_unless($order->customer_id === $user->id, 403);

        $order->load(['items.product', 'address', 'statusHistories']);

        // Filter status histories for customer (remove changedBy and remarks)
        $statusHistory = $order->statusHistories->map(function ($history) {
            return [
                'old_status' => $history->old_status,
                'new_status' => $history->new_status,
                'created_at' => $history->created_at,
            ];
        });

        $orderData = $order->toArray();
        unset($orderData['items'], $orderData['status_histories']);

        return response()->json([
            'order' => $orderData,
            'items' => $order->items,
            'status_history' => $statusHistory,
        ]);
    }

    public function invoice(Request $request, Order $order)
    {
        $user = $request->user();

        abort_unless($order->customer_id === $user->id, 403);

        $order->load(['items.product', 'address', 'user']);

        $company = [
            'name' => SiteSetting::get('site_name', 'Unknown'),
            'email' => SiteSetting::get('contact_email', ''),
            'phone' => SiteSetting::get('contact_phone', ''),
            'address_line1' => SiteSetting::get('contact_address', ''),
            'address_line2' => null,
        ];

        $pdf = Pdf::loadView('invoices.order', [
            'order' => $order,
            'company' => array_filter($company),
        ])->setPaper('A4');

        return $pdf->download($order->order_number . '-receipt-a4.pdf');
    }



    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();

        $shippingCost = (float) ($data['shipping_cost'] ?? 0);

        // For guests, pass null as user_id
        $userId = $user ? $user->id : null;
        $address = $userId ? $this->resolveAddress($userId, $data) : null;

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))

            ->get()
            ->keyBy('id');

        $items = collect($data['items'])->map(function ($item) use ($products) {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $originalPrice = (float) $product->price;
            
            $discountPercent = (float) ($product->discount_percent ?? 0);
            $discountAmountPerUnit = $originalPrice * ($discountPercent / 100);
            $discountedPrice = $originalPrice - $discountAmountPerUnit;

            return [
                'product' => $product,
                'quantity' => $quantity,
                'original_price' => $originalPrice,
                'product_discount' => $discountPercent,
                'unit_price' => $discountedPrice,
                'item_discount' => $discountAmountPerUnit * $quantity,
                'total' => $discountedPrice * $quantity,
            ];
        });

        $order = DB::transaction(function () use ($userId, $address, $items, $shippingCost, $data) {
            // Re-verify stock inside transaction with locking
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item['product']->id);

                if (!$product || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => [sprintf('Insufficient stock for %s. It may have been purchased by another user.', $item['product']->name)]
                    ]);
                }
            }

            $subtotal = $items->sum(function ($item) {
                return $item['original_price'] * $item['quantity'];
            });
            $totalDiscount = $items->sum('item_discount');

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $userId,
                'address_id' => $address?->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'subtotal' => $subtotal,
                'discount' => $totalDiscount,
                'shipping_cost' => $shippingCost,
                'total' => $subtotal - $totalDiscount + $shippingCost,
                'customer_note' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => null,
                'new_status' => $order->status,
                'changed_by' => $userId,
                'remarks' => 'Order Created By Customer: Pending'
            ]);

            PaymentStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => null,
                'new_status' => $order->payment_status,
                'changed_by' => $userId,
                'remarks' => 'Order Created By Customer: Unpaid'
            ]);

            foreach ($items as $item) {
                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'product_sku' => $item['product']->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'product_discount' => $item['product_discount'],
                    'total' => $item['total'],
                ]);

                $product = Product::lockForUpdate()->find($item['product']->id);

                if ($product->stock <= 0) {
                    \App\Services\NotificationService::notifyAdmins(new \App\Notifications\LowStockNotification($product));
                }
            }

            return $order;
        });

        $order = $order->fresh()->load(['items.product', 'address', 'user']);

        // Send notification to admin via Telegram
        \App\Services\TelegramService::notifyAdminNewOrder($order);

        \App\Services\NotificationService::notifyAdmins(new \App\Notifications\NewOrderNotification($order));

        // Use toArray() so Enum casts and decimal types are cleanly serialized as strings/scalars
        return response()->json($order->toArray(), 201);
    }



    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (\App\Models\Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    protected function resolveAddress(int $userId, array $data): ?Address
    {
        if (! empty($data['address_id'])) {
            return Address::where('customer_id', $userId)
                ->whereKey($data['address_id'])
                ->firstOrFail();
        }

        if (empty($data['address'])) {
            return null;
        }

        return Address::create([
            'customer_id' => $userId,
            'label' => $data['address']['label'] ?? null,
            'name' => $data['address']['name'],
            'phone' => $data['address']['phone'] ?? null,
            'address_line_1' => $data['address']['address_line_1'] ?? null,
            'address_line_2' => $data['address']['address_line_2'] ?? null,
            'village' => $data['address']['village'] ?? null,
            'commune' => $data['address']['commune'] ?? null,
            'district' => $data['address']['district'] ?? null,
            'province' => $data['address']['province'] ?? null,
            'notes' => $data['address']['notes'] ?? null,
            'latitude' => $data['address']['latitude'] ?? null,
            'longitude' => $data['address']['longitude'] ?? null,
            'is_default' => false,
        ]);
    }
}

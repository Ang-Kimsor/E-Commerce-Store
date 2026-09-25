<?php

namespace App\Observers;

use App\Models\Order;
use App\Enums\StockMovementType;
use Illuminate\Support\Facades\Auth;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
    }

    public function updated(Order $order): void
    {
        // Only trigger if status or payment_status was changed
        if (!$order->wasChanged(['status', 'payment_status'])) {
            return;
        }

        $isReducing = $this->isReducingState($order);
        $isRestoring = $this->isRestoringState($order);
        $netStockDeducted = $this->getNetStockMovement($order);

        // If it should reduce stock and hasn't yet
        if ($isReducing && $netStockDeducted <= 0) {
            $this->recordStockMovement($order, StockMovementType::Out);
        }

        // If it should restore stock and it currently holds deducted stock
        if ($isRestoring && $netStockDeducted > 0) {
            $this->recordStockMovement($order, StockMovementType::In);
        }
    }

    protected function isReducingState(Order $order): bool
    {
        $status = $order->status;
        $payment = $order->payment_status;
        $isCash = $order->payment_method === \App\Enums\PaymentMethod::Cash;

        $validStatus = in_array($status, [
            \App\Enums\OrderStatus::Processing,
            \App\Enums\OrderStatus::Shipped,
            \App\Enums\OrderStatus::Delivered,
            \App\Enums\OrderStatus::Completed
        ]);
        $validPayment = ($payment === \App\Enums\PaymentStatus::Paid || $isCash);

        return $validStatus && $validPayment;
    }

    protected function isRestoringState(Order $order): bool
    {
        $status = $order->status;
        return in_array($status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Returned]);
    }

    protected function getNetStockMovement(Order $order): int
    {
        $outs = \App\Models\StockMovement::where('reference', 'like', 'Order #' . $order->order_number . '%')
            ->where('type', StockMovementType::Out->value)
            ->sum('quantity');

        $ins = \App\Models\StockMovement::where('reference', 'like', 'Order #' . $order->order_number . '%')
            ->where('type', StockMovementType::In->value)
            ->sum('quantity');

        return (int) $outs - (int) $ins;
    }

    protected function recordStockMovement(Order $order, StockMovementType $type): void
    {
        // Load items if not loaded
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            \App\Models\StockMovement::create([
                'product_id' => $item->product_id,
                'type' => $type,
                'quantity' => $item->quantity,
                'reference' => 'Order #' . $order->order_number . ' (' . $order->status->value . ')',
                'user_id' => Auth::id(),
            ]);
        }
    }
}

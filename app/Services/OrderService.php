<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Confirm an order (admin action).
     */
    public function confirmOrder(Order $order): void
    {
        if ($order->status !== 'pending') {
            throw new \RuntimeException('Order can only be confirmed from pending status.');
        }

        DB::transaction(function () use ($order) {
            // Reserve stock for each item
            foreach ($order->items as $item) {
                $this->inventoryService->reserveStock(
                    $item->product_variant_id,
                    $item->base_quantity
                );
            }

            $this->changeStatus($order, 'confirmed', 'Order confirmed by admin');
            $order->update(['confirmed_at' => now()]);
        });
    }

    /**
     * Mark order as processing.
     */
    public function processOrder(Order $order): void
    {
        if (!in_array($order->status, ['confirmed'])) {
            throw new \RuntimeException('Order can only be processed from confirmed status.');
        }

        $this->changeStatus($order, 'processing', 'Order is being processed');
    }

    /**
     * Mark order as packed.
     */
    public function packOrder(Order $order): void
    {
        if (!in_array($order->status, ['confirmed', 'processing'])) {
            throw new \RuntimeException('Order can only be packed from confirmed or processing status.');
        }

        $this->changeStatus($order, 'packed', 'Order packed and ready for dispatch');
        $order->update(['packed_at' => now()]);
    }

    /**
     * Ship the order — assign courier + tracking.
     */
    public function shipOrder(Order $order, ?string $courierName = null, ?string $trackingNumber = null): void
    {
        if (!in_array($order->status, ['packed', 'confirmed', 'processing'])) {
            throw new \RuntimeException('Order can only be shipped from packed status.');
        }

        DB::transaction(function () use ($order, $courierName, $trackingNumber) {
            $order->update([
                'courier_name' => $courierName,
                'tracking_number' => $trackingNumber,
                'shipped_at' => now(),
            ]);

            $this->changeStatus($order, 'shipped', 'Order shipped' . ($trackingNumber ? " — Tracking: {$trackingNumber}" : ''));
        });
    }

    /**
     * Mark order as delivered → deduct stock + release reserved.
     */
    public function deliverOrder(Order $order): void
    {
        if (!in_array($order->status, ['shipped', 'packed', 'confirmed', 'processing'])) {
            throw new \RuntimeException('Order cannot be delivered from current status.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // Create stock OUT movement for delivery
                StockMovement::create([
                    'product_variant_id' => $item->product_variant_id,
                    'type' => 'sale',
                    'direction' => 'out',
                    'quantity' => $item->base_quantity,
                    'unit_id' => $item->unit_id,
                    'original_quantity' => $item->quantity,
                    'reference_type' => 'order_item',
                    'reference_id' => $item->id,
                    'note' => "Order #{$order->order_number} delivered",
                    'created_by' => Auth::id(),
                ]);

                // Release the reserved stock
                $this->inventoryService->releaseReservedStock(
                    $item->product_variant_id,
                    $item->base_quantity
                );
            }

            $order->update(['delivered_at' => now()]);
            $this->changeStatus($order, 'delivered', 'Order delivered successfully');

            // Update customer financials if COD
            if ($order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
                $order->update([
                    'payment_status' => 'paid',
                    'paid_amount' => $order->grand_total,
                ]);

                if ($order->customer->detail) {
                    $order->customer->detail->decrement('total_due', $order->grand_total);
                }
            }
        });
    }

    /**
     * Cancel the order → release reserved stock.
     */
    public function cancelOrder(Order $order, string $reason = 'Cancelled by admin'): void
    {
        if (in_array($order->status, ['delivered', 'cancelled', 'returned'])) {
            throw new \RuntimeException('Order cannot be cancelled from current status.');
        }

        DB::transaction(function () use ($order, $reason) {
            // Release reserved stock if it was reserved (confirmed+)
            if (in_array($order->status, ['confirmed', 'processing', 'packed', 'shipped'])) {
                foreach ($order->items as $item) {
                    $this->inventoryService->releaseReservedStock(
                        $item->product_variant_id,
                        $item->base_quantity
                    );
                }
            }

            $order->update(['cancelled_at' => now()]);
            $this->changeStatus($order, 'cancelled', $reason);

            // Reverse customer financials
            if ($order->payment_status === 'unpaid' && $order->customer->detail) {
                $order->customer->detail->decrement('total_due', $order->grand_total);
                $order->customer->detail->decrement('total_purchase', $order->grand_total);
            }
        });
    }

    /**
     * Process a return → add stock back.
     */
    public function returnOrder(Order $order, string $reason = 'Returned'): void
    {
        if ($order->status !== 'delivered') {
            throw new \RuntimeException('Only delivered orders can be returned.');
        }

        DB::transaction(function () use ($order, $reason) {
            foreach ($order->items as $item) {
                // Create stock IN movement for return
                StockMovement::create([
                    'product_variant_id' => $item->product_variant_id,
                    'type' => 'return_in',
                    'direction' => 'in',
                    'quantity' => $item->base_quantity,
                    'unit_id' => $item->unit_id,
                    'original_quantity' => $item->quantity,
                    'reference_type' => 'order_item',
                    'reference_id' => $item->id,
                    'note' => "Return from Order #{$order->order_number}: {$reason}",
                    'created_by' => Auth::id(),
                ]);
            }

            $this->changeStatus($order, 'returned', $reason);

            // Set payment to refunded
            if ($order->payment_status === 'paid') {
                $order->update(['payment_status' => 'refunded']);
            }

            // Reverse customer financials
            if ($order->customer->detail) {
                $order->customer->detail->decrement('total_purchase', $order->grand_total);
            }
        });
    }

    /**
     * Mark payment as paid (admin action).
     */
    public function markAsPaid(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update([
                'payment_status' => 'paid',
                'paid_amount' => $order->grand_total,
            ]);

            if ($order->customer->detail) {
                $order->customer->detail->decrement('total_due', $order->grand_total);
            }
        });
    }

    /**
     * Log a status change.
     */
    public function changeStatus(Order $order, string $newStatus, ?string $note = null): void
    {
        $oldStatus = $order->status;

        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'note' => $note,
            'changed_by' => Auth::id(),
        ]);

        $order->update(['status' => $newStatus]);
    }
}

<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesOrderService
{
    public function __construct(
        protected StockService $stock,
        protected DiscountService $discounts,
    ) {}

    public function recalculateTotals(SalesOrder $order): SalesOrder
    {
        $items = $order->items()->get();

        $subtotal = $items->sum(fn ($item) => (float) $item->rate_per_bag * $item->bag_count);
        $discountTotal = $items->sum(fn ($item) => (float) $item->discount_per_bag * $item->bag_count);

        $order->update([
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total_amount' => $subtotal - $discountTotal,
        ]);

        return $order->fresh();
    }

    public function confirm(SalesOrder $order, User $user): SalesOrder
    {
        if ($order->status !== 'draft') {
            throw new RuntimeException('Only draft orders can be confirmed.');
        }

        if ($overage = $order->customer->creditLimitExceededBy((float) $order->total_amount)) {
            $message = "Confirming order {$order->order_no} would put {$order->customer->name} LKR ".number_format($overage, 2).' over their credit limit.';

            Notification::make()
                ->title('Credit limit exceeded')
                ->body($message)
                ->danger()
                ->sendToDatabase(User::role('Admin')->get());

            throw new RuntimeException($message);
        }

        return DB::transaction(function () use ($order, $user) {
            $items = $order->items()->with('product')->get();

            foreach ($items as $item) {
                if (! $this->stock->hasSufficientStock($order->warehouse, $item->product, $item->bag_count)) {
                    throw new RuntimeException("Insufficient stock for {$item->product->name}: available {$this->stock->quantityOnHand($order->warehouse, $item->product)}, requested {$item->bag_count}.");
                }
            }

            foreach ($items as $item) {
                $this->stock->adjust(
                    warehouse: $order->warehouse,
                    product: $item->product,
                    delta: -$item->bag_count,
                    type: 'sale',
                    user: $user,
                    reference: $order,
                    date: $order->order_date,
                );
            }

            $order->update(['status' => 'confirmed', 'confirmed_at' => now()]);

            return $order->fresh();
        });
    }

    public function deliver(SalesOrder $order): SalesOrder
    {
        if ($order->status !== 'confirmed') {
            throw new RuntimeException('Only confirmed orders can be marked delivered.');
        }

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        return $order->fresh();
    }

    public function invoice(SalesOrder $order): SalesOrder
    {
        if (! in_array($order->status, ['confirmed', 'delivered'])) {
            throw new RuntimeException('Only confirmed or delivered orders can be invoiced.');
        }

        $order->update(['status' => 'invoiced', 'invoiced_at' => now()]);

        return $order->fresh();
    }

    public function cancel(SalesOrder $order, User $user): SalesOrder
    {
        if ($order->status === 'cancelled') {
            throw new RuntimeException('Order is already cancelled.');
        }

        return DB::transaction(function () use ($order, $user) {
            if (in_array($order->status, ['confirmed', 'delivered', 'invoiced'])) {
                foreach ($order->items()->with('product')->get() as $item) {
                    $this->stock->adjust(
                        warehouse: $order->warehouse,
                        product: $item->product,
                        delta: $item->bag_count,
                        type: 'cancellation',
                        user: $user,
                        reference: $order,
                        date: now(),
                    );
                }
            }

            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return $order->fresh();
        });
    }

    public function buildItemData(int $productId, int $bagCount): array
    {
        $product = \App\Models\Product::findOrFail($productId);
        $rate = (float) $product->base_price_per_bag;
        $discount = $this->discounts->resolveDiscountPerBag($product, $bagCount);

        return [
            'product_id' => $productId,
            'bag_count' => $bagCount,
            'rate_per_bag' => $rate,
            'discount_per_bag' => $discount,
            'line_total' => ($rate - $discount) * $bagCount,
        ];
    }
}

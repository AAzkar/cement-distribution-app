<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesOrderMonthlyReportService
{
    public function monthlyBreakdown(int $year, ?int $warehouseId = null): Collection
    {
        return collect(range(1, 12))->map(function (int $month) use ($year, $warehouseId) {
            $ordersQuery = SalesOrder::query()
                ->whereYear('order_date', $year)
                ->whereMonth('order_date', $month)
                ->whereIn('status', ['confirmed', 'delivered', 'invoiced'])
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId));

            $orderIds = (clone $ordersQuery)->pluck('id');
            $totalBags = $orderIds->isEmpty() ? 0 : SalesOrderItem::whereIn('sales_order_id', $orderIds)->sum('bag_count');

            return [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('F'),
                'orders_count' => $orderIds->count(),
                'total_bags' => (int) $totalBags,
                'total_amount' => (float) (clone $ordersQuery)->sum('total_amount'),
            ];
        });
    }
}

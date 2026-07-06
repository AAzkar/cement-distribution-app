<?php

namespace App\Services;

use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class WarehouseReportService
{
    public function query(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Warehouse::query()
            ->withCount(['salesOrders as orders_count' => function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from, $to])
                    ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
            }])
            ->withSum(['salesOrders as orders_total' => function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from, $to])
                    ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
            }], 'total_amount')
            ->withSum(['cashbookEntries as inflow_total' => function ($q) use ($from, $to) {
                $q->where('direction', 'inflow')
                    ->whereIn('status', ['approved', 'locked'])
                    ->whereBetween('entry_date', [$from, $to]);
            }], 'amount')
            ->withSum(['cashbookEntries as outflow_total' => function ($q) use ($from, $to) {
                $q->where('direction', 'outflow')
                    ->whereIn('status', ['approved', 'locked'])
                    ->whereBetween('entry_date', [$from, $to]);
            }], 'amount')
            ->withSum('stocks as stock_on_hand', 'quantity');
    }

    public function bagsSold(Warehouse $warehouse, CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) SalesOrderItem::whereHas('salesOrder', function ($q) use ($warehouse, $from, $to) {
            $q->where('warehouse_id', $warehouse->id)
                ->whereBetween('order_date', [$from, $to])
                ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
        })->sum('bag_count');
    }
}

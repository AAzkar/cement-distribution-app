<?php

namespace App\Services;

use App\Models\SalesOrderItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class SalesRepPerformanceReportService
{
    public function query(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return User::query()
            ->role('Sales Representative')
            ->withCount(['salesOrders as orders_count' => function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from, $to])
                    ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
            }])
            ->withSum(['salesOrders as orders_total' => function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from, $to])
                    ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
            }], 'total_amount')
            ->withSum(['repCollections as collections_total' => function ($q) use ($from, $to) {
                $q->whereBetween('entry_date', [$from, $to]);
            }], 'amount')
            ->withSum(['incentiveRecords as incentives_total' => function ($q) use ($from, $to) {
                $q->whereBetween('record_date', [$from, $to])->where('status', 'approved');
            }], 'final_amount');
    }

    public function bagsSold(User $rep, CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) SalesOrderItem::whereHas('salesOrder', function ($q) use ($rep, $from, $to) {
            $q->where('sales_rep_id', $rep->id)
                ->whereBetween('order_date', [$from, $to])
                ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
        })->sum('bag_count');
    }
}

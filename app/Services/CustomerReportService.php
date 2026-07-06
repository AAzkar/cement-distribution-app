<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class CustomerReportService
{
    public function query(CarbonInterface $from, CarbonInterface $to, ?int $warehouseId = null): Builder
    {
        return Customer::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->withCount(['salesOrders as orders_count' => function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from, $to])
                    ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
            }])
            ->withSum(['salesOrders as orders_total' => function ($q) use ($from, $to) {
                $q->whereBetween('order_date', [$from, $to])
                    ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
            }], 'total_amount')
            ->withSum(['cashbookEntries as cash_collections_total' => function ($q) use ($from, $to) {
                $q->where('direction', 'inflow')
                    ->whereIn('status', ['approved', 'locked'])
                    ->whereBetween('entry_date', [$from, $to]);
            }], 'amount')
            ->withSum(['chequesReceived as cheque_collections_total' => function ($q) use ($from, $to) {
                $q->where('status', 'cleared')
                    ->whereBetween('received_date', [$from, $to]);
            }], 'amount');
    }
}

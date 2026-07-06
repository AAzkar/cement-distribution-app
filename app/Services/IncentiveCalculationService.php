<?php

namespace App\Services;

use App\Models\IncentiveRecord;
use App\Models\IncentiveRule;
use App\Models\RepCollection;
use App\Models\SalesOrder;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class IncentiveCalculationService
{
    public function calculateForDate(CarbonInterface $date, ?User $onlyRep = null): Collection
    {
        $reps = $onlyRep
            ? collect([$onlyRep])
            : User::role('Sales Representative')->where('is_active', true)->get();

        $rules = IncentiveRule::where('is_active', true)->get();

        $records = collect();

        foreach ($reps as $rep) {
            $repWarehouseIds = $rep->warehouses()->pluck('warehouses.id');
            $repZoneIds = $rep->zones()->pluck('zones.id');

            $confirmedOrders = SalesOrder::where('sales_rep_id', $rep->id)
                ->whereDate('order_date', $date)
                ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);

            $salesTotal = (clone $confirmedOrders)->sum('total_amount');
            $invoiceCount = (clone $confirmedOrders)->count();
            $collectionsTotal = RepCollection::where('sales_rep_id', $rep->id)->whereDate('entry_date', $date)->sum('amount');

            foreach ($rules as $rule) {
                if ($rule->warehouse_id && ! $repWarehouseIds->contains($rule->warehouse_id)) {
                    continue;
                }

                if ($rule->zone_id && ! $repZoneIds->contains($rule->zone_id)) {
                    continue;
                }

                $metricValue = match ($rule->metric) {
                    'sales' => $salesTotal,
                    'collections' => $collectionsTotal,
                    'invoice_count' => $invoiceCount,
                };

                $calculatedAmount = $this->calculateAmount($rule, (float) $metricValue);

                $existing = IncentiveRecord::firstOrNew([
                    'sales_rep_id' => $rep->id,
                    'incentive_rule_id' => $rule->id,
                    'record_date' => $date->toDateString(),
                ]);

                if ($existing->exists && $existing->status !== 'pending') {
                    continue;
                }

                $existing->fill([
                    'metric_value' => $metricValue,
                    'calculated_amount' => $calculatedAmount,
                    'final_amount' => $existing->override_amount ?? $calculatedAmount,
                    'status' => 'pending',
                ])->save();

                $records->push($existing);
            }
        }

        return $records;
    }

    protected function calculateAmount(IncentiveRule $rule, float $metricValue): float
    {
        return match ($rule->rule_type) {
            'fixed' => $metricValue >= (float) ($rule->min_target ?? 0) ? (float) $rule->fixed_amount : 0.0,
            'percentage' => $metricValue >= (float) ($rule->min_target ?? 0) ? $metricValue * ((float) $rule->percentage / 100) : 0.0,
            'slab' => $this->resolveSlabAmount($rule->slabs ?? [], $metricValue),
        };
    }

    protected function resolveSlabAmount(array $slabs, float $metricValue): float
    {
        $matching = collect($slabs)
            ->filter(fn ($slab) => $metricValue >= (float) ($slab['min_value'] ?? 0))
            ->sortByDesc(fn ($slab) => (float) $slab['min_value'])
            ->first();

        return $matching ? (float) $matching['amount'] : 0.0;
    }
}

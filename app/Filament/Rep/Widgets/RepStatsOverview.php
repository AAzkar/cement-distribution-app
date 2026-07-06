<?php

namespace App\Filament\Rep\Widgets;

use App\Models\Handover;
use App\Models\IncentiveRecord;
use App\Models\RepCollection;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RepStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $repId = Auth::id();
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $salesTotal = SalesOrder::where('sales_rep_id', $repId)
            ->whereBetween('order_date', [$from, $to])
            ->whereIn('status', ['confirmed', 'delivered', 'invoiced'])
            ->sum('total_amount');

        $collectionsTotal = RepCollection::where('sales_rep_id', $repId)
            ->whereBetween('entry_date', [$from, $to])
            ->sum('amount');

        $incentivesTotal = IncentiveRecord::where('sales_rep_id', $repId)
            ->whereBetween('record_date', [$from, $to])
            ->where('status', 'approved')
            ->sum('final_amount');

        $pendingHandovers = Handover::where('sales_rep_id', $repId)
            ->whereIn('status', ['draft', 'submitted'])
            ->count();

        return [
            Stat::make('My Sales This Month', 'LKR '.number_format($salesTotal, 2))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
            Stat::make('My Collections This Month', 'LKR '.number_format($collectionsTotal, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('My Incentives This Month', 'LKR '.number_format($incentivesTotal, 2))
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),
            Stat::make('Pending Handovers', (string) $pendingHandovers)
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($pendingHandovers > 0 ? 'warning' : 'success'),
        ];
    }
}

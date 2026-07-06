<?php

namespace App\Filament\Widgets;

use App\Models\CashbookEntry;
use App\Models\ChequeReceived;
use App\Models\Customer;
use App\Models\RepCollection;
use App\Models\SalesOrder;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $salesTotal = SalesOrder::whereBetween('order_date', [$from, $to])
            ->whereIn('status', ['confirmed', 'delivered', 'invoiced'])
            ->sum('total_amount');

        $collectionsTotal = CashbookEntry::where('direction', 'inflow')
            ->whereIn('status', ['approved', 'locked'])
            ->whereBetween('entry_date', [$from, $to])
            ->sum('amount');

        $outstanding = $this->outstandingReceivables();

        $pendingApprovals = RepCollection::where('status', 'pending')->count()
            + CashbookEntry::where('status', 'pending_approval')->count()
            + ChequeReceived::where('status', 'received')->count();

        $dailySales = collect(range(6, 0))->map(
            fn ($i) => (float) SalesOrder::whereDate('order_date', now()->subDays($i))
                ->whereIn('status', ['confirmed', 'delivered', 'invoiced'])
                ->sum('total_amount')
        )->toArray();

        return [
            Stat::make('Sales This Month', 'LKR '.number_format($salesTotal, 2))
                ->description('Confirmed, delivered & invoiced orders')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart($dailySales)
                ->color('primary'),
            Stat::make('Collections This Month', 'LKR '.number_format($collectionsTotal, 2))
                ->description('Approved cashbook inflows')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Outstanding Receivables', 'LKR '.number_format($outstanding, 2))
                ->description('Across all customers')
                ->descriptionIcon('heroicon-m-scale')
                ->color($outstanding > 0 ? 'danger' : 'success'),
            Stat::make('Pending Approvals', (string) $pendingApprovals)
                ->description('Collections, vouchers & cheques awaiting action')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingApprovals > 0 ? 'warning' : 'success'),
        ];
    }

    protected function outstandingReceivables(): float
    {
        $totalOrders = SalesOrder::whereIn('status', ['confirmed', 'delivered', 'invoiced'])->sum('total_amount');
        $totalOpeningBalances = Customer::sum('opening_balance');
        $totalPaidCashbook = CashbookEntry::where('direction', 'inflow')
            ->whereIn('status', ['approved', 'locked'])
            ->sum('amount');
        $totalPaidCheques = ChequeReceived::where('status', 'cleared')->sum('amount');

        return (float) $totalOpeningBalances + (float) $totalOrders - (float) $totalPaidCashbook - (float) $totalPaidCheques;
    }
}

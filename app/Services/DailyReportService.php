<?php

namespace App\Services;

use App\Models\CashbookEntry;
use App\Models\ChequeIssued;
use App\Models\ChequeReceived;
use App\Models\DailyReport;
use Carbon\CarbonInterface;

class DailyReportService
{
    public function generate(CarbonInterface $date, ?int $warehouseId = null): DailyReport
    {
        $previous = DailyReport::query()
            ->where('warehouse_id', $warehouseId)
            ->whereDate('report_date', '<', $date)
            ->orderByDesc('report_date')
            ->first();

        $openingBalance = $previous?->closing_balance ?? 0;

        $cashbookQuery = fn (string $direction) => CashbookEntry::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereDate('entry_date', $date)
            ->whereIn('status', ['approved', 'locked'])
            ->where('direction', $direction);

        $totalInflows = $cashbookQuery('inflow')->sum('amount');
        $totalOutflows = $cashbookQuery('outflow')->sum('amount');
        $closingBalance = $openingBalance + $totalInflows - $totalOutflows;

        $chequesReceivedQuery = ChequeReceived::query()->whereDate('received_date', $date);
        $chequesIssuedQuery = ChequeIssued::query()->whereDate('issue_date', $date);

        if ($warehouseId) {
            $chequesReceivedQuery->whereHas('customer', fn ($q) => $q->where('warehouse_id', $warehouseId));
        }

        $chequesSummary = [
            'received' => [
                'count' => (clone $chequesReceivedQuery)->count(),
                'amount' => (clone $chequesReceivedQuery)->sum('amount'),
            ],
            'deposited' => (clone $chequesReceivedQuery)->where('status', 'deposited')->count(),
            'cleared' => (clone $chequesReceivedQuery)->where('status', 'cleared')->count(),
            'returned' => (clone $chequesReceivedQuery)->where('status', 'returned')->count(),
            'issued' => [
                'count' => (clone $chequesIssuedQuery)->count(),
                'amount' => (clone $chequesIssuedQuery)->sum('amount'),
            ],
            'issued_cleared' => (clone $chequesIssuedQuery)->where('status', 'cleared')->count(),
            'issued_bounced' => (clone $chequesIssuedQuery)->where('status', 'bounced')->count(),
        ];

        return DailyReport::updateOrCreate(
            ['report_date' => $date->toDateString(), 'warehouse_id' => $warehouseId],
            [
                'opening_balance' => $openingBalance,
                'total_inflows' => $totalInflows,
                'total_outflows' => $totalOutflows,
                'closing_balance' => $closingBalance,
                'cheques_summary' => $chequesSummary,
                'status' => 'draft',
            ]
        );
    }
}

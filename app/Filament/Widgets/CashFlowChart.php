<?php

namespace App\Filament\Widgets;

use App\Models\CashbookEntry;
use Filament\Widgets\ChartWidget;

class CashFlowChart extends ChartWidget
{
    protected static ?string $heading = 'Cash Flow — Last 6 Months';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i)->startOfMonth());

        $entries = CashbookEntry::whereIn('status', ['approved', 'locked'])
            ->whereBetween('entry_date', [$months->first(), now()->endOfMonth()])
            ->get(['entry_date', 'direction', 'amount']);

        $inflows = $months->map(
            fn ($month) => (float) $entries
                ->filter(fn ($e) => $e->direction === 'inflow' && $e->entry_date->isSameMonth($month))
                ->sum('amount')
        );

        $outflows = $months->map(
            fn ($month) => (float) $entries
                ->filter(fn ($e) => $e->direction === 'outflow' && $e->entry_date->isSameMonth($month))
                ->sum('amount')
        );

        return [
            'datasets' => [
                [
                    'label' => 'Inflows',
                    'data' => $inflows->values()->toArray(),
                    'backgroundColor' => '#2a78d6',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Outflows',
                    'data' => $outflows->values()->toArray(),
                    'backgroundColor' => '#1baf7a',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $months->map(fn ($month) => $month->format('M Y'))->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => '#e1e0d9'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}

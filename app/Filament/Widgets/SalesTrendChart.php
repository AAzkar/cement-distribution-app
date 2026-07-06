<?php

namespace App\Filament\Widgets;

use App\Models\SalesOrder;
use Filament\Widgets\ChartWidget;

class SalesTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Trend — Last 30 Days';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        $orders = SalesOrder::whereBetween('order_date', [$from, $to])
            ->whereIn('status', ['confirmed', 'delivered', 'invoiced'])
            ->get(['order_date', 'total_amount']);

        $byDate = $orders->groupBy(fn (SalesOrder $order) => $order->order_date->toDateString());

        $days = collect(range(29, 0))->map(fn (int $i) => now()->subDays($i));

        $values = $days->map(
            fn ($day) => (float) ($byDate->get($day->toDateString())?->sum('total_amount') ?? 0)
        );

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $values->values()->toArray(),
                    'borderColor' => '#2a78d6',
                    'backgroundColor' => 'rgba(42, 120, 214, 0.12)',
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('M j'))->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
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

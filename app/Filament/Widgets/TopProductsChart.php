<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\SalesOrderItem;
use Filament\Widgets\ChartWidget;

class TopProductsChart extends ChartWidget
{
    protected static ?string $heading = 'Bags Sold by Product — This Month';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected const PALETTE = ['#2a78d6', '#1baf7a', '#eda100', '#008300', '#4a3aa7', '#e34948', '#e87ba4', '#eb6834'];

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $products = Product::where('is_active', true)->orderBy('name')->get();

        $bagsByProduct = $products->map(function (Product $product) use ($from, $to) {
            return (int) SalesOrderItem::where('product_id', $product->id)
                ->whereHas('salesOrder', function ($q) use ($from, $to) {
                    $q->whereBetween('order_date', [$from, $to])
                        ->whereIn('status', ['confirmed', 'delivered', 'invoiced']);
                })
                ->sum('bag_count');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Bags Sold',
                    'data' => $bagsByProduct->values()->toArray(),
                    'backgroundColor' => array_slice(self::PALETTE, 0, max($products->count(), 1)),
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $products->pluck('name')->toArray(),
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

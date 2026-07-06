<?php

namespace App\Filament\Pages\Reports;

use App\Models\Warehouse;
use App\Services\SalesOrderMonthlyReportExportService;
use App\Services\SalesOrderMonthlyReportService;
use Filament\Actions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SalesOrdersByMonthReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Orders by Month';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.reports.sales-orders-by-month-report';

    public int $year;

    public ?int $warehouseId = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('sales_orders.view') ?? false;
    }

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    public function years(): array
    {
        $current = (int) now()->year;

        return range($current, $current - 4);
    }

    public function warehouses(): \Illuminate\Support\Collection
    {
        return Warehouse::orderBy('name')->pluck('name', 'id');
    }

    public function rows(): \Illuminate\Support\Collection
    {
        return app(SalesOrderMonthlyReportService::class)->monthlyBreakdown($this->year, $this->warehouseId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => app(SalesOrderMonthlyReportExportService::class)->toPdf($this->rows(), $this->year)),
            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->action(fn () => app(SalesOrderMonthlyReportExportService::class)->toExcel($this->rows(), $this->year)),
        ];
    }
}

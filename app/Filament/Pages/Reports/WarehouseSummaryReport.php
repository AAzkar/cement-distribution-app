<?php

namespace App\Filament\Pages\Reports;

use App\Models\Warehouse;
use App\Services\WarehouseReportExportService;
use App\Services\WarehouseReportService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WarehouseSummaryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Warehouse Summary';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.reports.warehouse-summary-report';

    public string $periodType = 'month';

    public int $month;

    public int $year;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('warehouses.view') ?? false;
    }

    public function mount(): void
    {
        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function years(): array
    {
        $current = (int) now()->year;

        return range($current, $current - 4);
    }

    public function resolveRange(): array
    {
        return match ($this->periodType) {
            'year' => [
                Carbon::create($this->year, 1, 1)->startOfYear(),
                Carbon::create($this->year, 12, 31)->endOfYear(),
            ],
            'custom' => [
                Carbon::parse($this->dateFrom ?: now()->startOfMonth()->toDateString())->startOfDay(),
                Carbon::parse($this->dateTo ?: now()->toDateString())->endOfDay(),
            ],
            default => [
                Carbon::create($this->year, $this->month, 1)->startOfMonth(),
                Carbon::create($this->year, $this->month, 1)->endOfMonth(),
            ],
        };
    }

    public function table(Table $table): Table
    {
        [$from, $to] = $this->resolveRange();

        return $table
            ->query(app(WarehouseReportService::class)->query($from, $to))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
                Tables\Columns\TextColumn::make('orders_total')
                    ->label('Orders Total')
                    ->money('lkr')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('lkr')),
                Tables\Columns\TextColumn::make('inflow_total')
                    ->label('Inflows')
                    ->money('lkr')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('lkr')),
                Tables\Columns\TextColumn::make('outflow_total')
                    ->label('Outflows')
                    ->money('lkr')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('lkr')),
                Tables\Columns\TextColumn::make('net_cash_flow')
                    ->label('Net Cash Flow')
                    ->state(fn (Warehouse $record) => (float) ($record->inflow_total ?? 0) - (float) ($record->outflow_total ?? 0))
                    ->money('lkr')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('bags_sold')
                    ->label('Bags Sold')
                    ->state(fn (Warehouse $record) => app(WarehouseReportService::class)->bagsSold($record, ...$this->resolveRange())),
                Tables\Columns\TextColumn::make('stock_on_hand')
                    ->label('Stock On Hand'),
            ])
            ->defaultSort('orders_total', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    [$from, $to] = $this->resolveRange();
                    $rows = app(WarehouseReportService::class)->query($from, $to)->get();

                    return app(WarehouseReportExportService::class)->toPdf($rows, $from, $to);
                }),
            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->action(function () {
                    [$from, $to] = $this->resolveRange();
                    $rows = app(WarehouseReportService::class)->query($from, $to)->get();

                    return app(WarehouseReportExportService::class)->toExcel($rows, $from, $to);
                }),
        ];
    }
}

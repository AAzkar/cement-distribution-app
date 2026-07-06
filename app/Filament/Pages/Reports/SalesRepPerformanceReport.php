<?php

namespace App\Filament\Pages\Reports;

use App\Models\User;
use App\Services\SalesRepPerformanceReportExportService;
use App\Services\SalesRepPerformanceReportService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SalesRepPerformanceReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Sales Rep Performance';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.reports.sales-rep-performance-report';

    public string $periodType = 'month';

    public int $month;

    public int $year;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('sales_orders.view') ?? false;
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
            ->query(app(SalesRepPerformanceReportService::class)->query($from, $to))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('employee_code')->label('Employee Code'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
                Tables\Columns\TextColumn::make('orders_total')
                    ->label('Orders Total')
                    ->money('lkr')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('lkr')),
                Tables\Columns\TextColumn::make('bags_sold')
                    ->label('Bags Sold')
                    ->state(fn (User $record) => app(SalesRepPerformanceReportService::class)->bagsSold($record, ...$this->resolveRange())),
                Tables\Columns\TextColumn::make('collections_total')
                    ->label('Collections')
                    ->money('lkr')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('lkr')),
                Tables\Columns\TextColumn::make('incentives_total')
                    ->label('Incentives')
                    ->money('lkr')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('lkr')),
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
                    $rows = app(SalesRepPerformanceReportService::class)->query($from, $to)->get();

                    return app(SalesRepPerformanceReportExportService::class)->toPdf($rows, $from, $to);
                }),
            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->action(function () {
                    [$from, $to] = $this->resolveRange();
                    $rows = app(SalesRepPerformanceReportService::class)->query($from, $to)->get();

                    return app(SalesRepPerformanceReportExportService::class)->toExcel($rows, $from, $to);
                }),
        ];
    }
}

<?php

namespace App\Filament\Pages\Reports;

use App\Models\Customer;
use App\Models\Warehouse;
use App\Services\CustomerReportExportService;
use App\Services\CustomerReportService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CustomerSummaryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customer Summary';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.reports.customer-summary-report';

    public string $periodType = 'month';

    public int $month;

    public int $year;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?int $warehouseId = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->can('customers.view') ?? false;
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

    public function warehouses(): \Illuminate\Support\Collection
    {
        return Warehouse::orderBy('name')->pluck('name', 'id');
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
            ->query(app(CustomerReportService::class)->query($from, $to, $this->warehouseId))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->searchable(),
                Tables\Columns\TextColumn::make('zone.name'),
                Tables\Columns\TextColumn::make('warehouse.name'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()),
                Tables\Columns\TextColumn::make('orders_total')
                    ->label('Orders Total')
                    ->money('lkr')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('lkr')),
                Tables\Columns\TextColumn::make('collections_total')
                    ->label('Collections')
                    ->state(fn (Customer $record) => (float) ($record->cash_collections_total ?? 0) + (float) ($record->cheque_collections_total ?? 0))
                    ->money('lkr'),
                Tables\Columns\TextColumn::make('outstanding_balance')
                    ->label('Outstanding')
                    ->state(fn (Customer $record) => $record->outstandingBalance())
                    ->money('lkr')
                    ->color(fn (Customer $record) => $record->outstandingBalance() > 0 ? 'danger' : 'success'),
            ])
            ->defaultSort('orders_total', 'desc')
            ->paginated([10, 25, 50, 'all']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    [$from, $to] = $this->resolveRange();
                    $rows = app(CustomerReportService::class)->query($from, $to, $this->warehouseId)->get();

                    return app(CustomerReportExportService::class)->toPdf($rows, $from, $to);
                }),
            Actions\Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->action(function () {
                    [$from, $to] = $this->resolveRange();
                    $rows = app(CustomerReportService::class)->query($from, $to, $this->warehouseId)->get();

                    return app(CustomerReportExportService::class)->toExcel($rows, $from, $to);
                }),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\WarehouseStock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->can('warehouse_stocks.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Low Stock Alerts')
            ->query(
                WarehouseStock::query()
                    ->whereNotNull('reorder_level')
                    ->whereColumn('quantity', '<=', 'reorder_level')
                    ->with(['warehouse', 'product'])
            )
            ->defaultSort('quantity', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse'),
                Tables\Columns\TextColumn::make('product.name')->label('Product'),
                Tables\Columns\TextColumn::make('quantity')->label('Bags on Hand')->color('danger'),
                Tables\Columns\TextColumn::make('reorder_level')->label('Reorder Level'),
                Tables\Columns\TextColumn::make('shortfall')
                    ->label('Shortfall')
                    ->state(fn (WarehouseStock $record) => max(0, $record->reorder_level - $record->quantity))
                    ->color('danger'),
            ])
            ->paginated([5, 10, 25]);
    }
}

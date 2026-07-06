<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseStockResource\Pages;
use App\Models\WarehouseStock;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class WarehouseStockResource extends Resource
{
    protected static ?string $model = WarehouseStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Stock Levels';

    protected static ?string $navigationGroup = 'Sales & Inventory';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('warehouse_stocks.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
                Tables\Columns\TextColumn::make('product.name')->sortable(),
                Tables\Columns\TextColumn::make('quantity')->label('Bags on Hand')->sortable()
                    ->color(fn (WarehouseStock $record) => $record->quantity <= 0 ? 'danger' : 'success'),
            ])
            ->defaultSort('quantity', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('product_id')->relationship('product', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseStocks::route('/'),
        ];
    }
}

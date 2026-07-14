<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseStockResource\Pages;
use App\Models\WarehouseStock;
use Filament\Forms;
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
                    ->color(fn (WarehouseStock $record) => match (true) {
                        $record->quantity <= 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('reorder_level')->label('Reorder Level')
                    ->placeholder('Not set')->sortable(),
            ])
            ->defaultSort('quantity', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('product_id')->relationship('product', 'name'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low stock only')
                    ->query(fn ($query) => $query->whereNotNull('reorder_level')->whereColumn('quantity', '<=', 'reorder_level')),
            ])
            ->actions([
                Tables\Actions\Action::make('setReorderLevel')
                    ->label('Set Reorder Level')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn () => Auth::user()?->can('warehouse_stocks.edit') ?? false)
                    ->fillForm(fn (WarehouseStock $record) => ['reorder_level' => $record->reorder_level])
                    ->form([
                        Forms\Components\TextInput::make('reorder_level')
                            ->label('Reorder Level')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Alerts fire when bags on hand drop to or below this level. Leave blank to disable alerts for this item.'),
                    ])
                    ->action(fn (WarehouseStock $record, array $data) => $record->update(['reorder_level' => $data['reorder_level']])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouseStocks::route('/'),
        ];
    }
}

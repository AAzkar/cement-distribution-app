<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class StockTransferResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Sales & Inventory';

    protected static ?int $navigationSort = 4;

    protected static string $permissionModule = 'stock_transfers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('from_warehouse_id')
                    ->relationship('fromWarehouse', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('to_warehouse_id')
                    ->relationship('toWarehouse', 'name')->required()->searchable()->preload()
                    ->rule('different:from_warehouse_id'),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')->required()->searchable()->preload(),
                Forms\Components\TextInput::make('quantity')->required()->numeric()->label('Bags'),
                Forms\Components\DatePicker::make('transfer_date')->required()->default(now()),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_no')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('fromWarehouse.name')->label('From'),
                Tables\Columns\TextColumn::make('toWarehouse.name')->label('To'),
                Tables\Columns\TextColumn::make('product.name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('transfer_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'confirmed' ? 'success' : 'gray'),
            ])
            ->defaultSort('transfer_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['draft' => 'Draft', 'confirmed' => 'Confirmed']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (StockTransfer $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StockTransfer $record) => $record->status === 'draft' && Auth::user()->can('stock_transfers.approve'))
                    ->requiresConfirmation()
                    ->action(fn (StockTransfer $record) => app(StockTransferService::class)->confirm($record, Auth::user())),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\GoodsReceiptResource\Pages;
use App\Models\GoodsReceipt;
use App\Services\GoodsReceiptService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = GoodsReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Sales & Inventory';

    protected static ?int $navigationSort = 3;

    protected static string $permissionModule = 'goods_receipts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')->searchable()->preload(),
                Forms\Components\DatePicker::make('receipt_date')->required()->default(now()),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')->required()->searchable()->preload(),
                        Forms\Components\TextInput::make('quantity')->required()->numeric()->label('Bags'),
                        Forms\Components\TextInput::make('unit_cost')->numeric()->prefix('LKR '),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->required()
                    ->minItems(1),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('receipt_no')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('receipt_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'confirmed' ? 'success' : 'gray'),
            ])
            ->defaultSort('receipt_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('status')->options(['draft' => 'Draft', 'confirmed' => 'Confirmed']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (GoodsReceipt $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (GoodsReceipt $record) => $record->status === 'draft' && Auth::user()->can('goods_receipts.approve'))
                    ->requiresConfirmation()
                    ->action(fn (GoodsReceipt $record) => app(GoodsReceiptService::class)->confirm($record, Auth::user())),
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
            'index' => Pages\ListGoodsReceipts::route('/'),
            'create' => Pages\CreateGoodsReceipt::route('/create'),
            'edit' => Pages\EditGoodsReceipt::route('/{record}/edit'),
        ];
    }
}

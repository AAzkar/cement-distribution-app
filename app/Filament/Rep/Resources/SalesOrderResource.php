<?php

namespace App\Filament\Rep\Resources;

use App\Filament\Rep\Concerns\ScopedToCurrentRep;
use App\Filament\Rep\Resources\SalesOrderResource\Pages;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\DiscountService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SalesOrderResource extends Resource
{
    use ScopedToCurrentRep;

    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'My Orders';

    protected static ?int $navigationSort = 5;

    public static function canEdit($record): bool
    {
        return $record->status === 'draft';
    }

    public static function canDelete($record): bool
    {
        return $record->status === 'draft';
    }

    public static function form(Form $form): Form
    {
        $rep = Auth::user();

        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')->required()->searchable()->preload()
                    ->default(fn () => request()->query('customer_id')),
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'name', fn ($query) => $query->whereIn('id', $rep->warehouses()->pluck('warehouses.id')))
                    ->required()
                    ->default(fn () => $rep->warehouses()->first()?->id),
                Forms\Components\Select::make('zone_id')
                    ->relationship('zone', 'name', fn ($query) => $query->whereIn('id', $rep->zones()->pluck('zones.id')))
                    ->default(fn () => $rep->zones()->first()?->id),
                Forms\Components\DatePicker::make('order_date')->required()->default(now()),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),

                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get, $state) => self::applyPricing($set, $get, $state, $get('bag_count'))),
                        Forms\Components\TextInput::make('bag_count')
                            ->label('Bags')
                            ->required()
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get, $state) => self::applyPricing($set, $get, $get('product_id'), $state)),
                        Forms\Components\TextInput::make('rate_per_bag')->required()->numeric()->prefix('LKR ')->disabled()->dehydrated(),
                        Forms\Components\TextInput::make('discount_per_bag')->required()->numeric()->default(0)->prefix('LKR ')->disabled()->dehydrated(),
                        Forms\Components\TextInput::make('line_total')->numeric()->prefix('LKR ')->disabled()->dehydrated(),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->required()
                    ->minItems(1),
            ]);
    }

    protected static function applyPricing(Set $set, Get $get, $productId, $bagCount): void
    {
        if (! $productId || ! $bagCount) {
            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $rate = (float) $product->base_price_per_bag;
        $discount = app(DiscountService::class)->resolveDiscountPerBag($product, (int) $bagCount);

        $set('rate_per_bag', $rate);
        $set('discount_per_bag', $discount);
        $set('line_total', round(($rate - $discount) * (int) $bagCount, 2));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_no'),
                Tables\Columns\TextColumn::make('order_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('customer.name'),
                Tables\Columns\TextColumn::make('total_amount')->money('lkr'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->defaultSort('order_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('printReceipt')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn (SalesOrder $record) => app(\App\Services\SalesOrderReceiptService::class)->toPdf($record)),
            ]);
    }

    public static function mutateFormDataBeforeCreateHook(array $data): array
    {
        $data['sales_rep_id'] = Auth::id();
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';
        $data['order_no'] = \App\Models\VoucherSequence::next('sales_order');

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
        ];
    }
}

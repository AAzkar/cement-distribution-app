<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\SalesOrderResource\Pages;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\DiscountService;
use App\Services\SalesOrderReceiptService;
use App\Services\SalesOrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SalesOrderResource extends Resource
{
    use AuthorizesWithPermissions;

    protected static ?string $model = SalesOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Sales & Inventory';

    protected static ?int $navigationSort = 6;

    protected static string $permissionModule = 'sales_orders';

    public static function canEdit(Model $record): bool
    {
        if ($record->status !== 'draft') {
            return false;
        }

        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        if ($record->status !== 'draft') {
            return false;
        }

        return parent::canDelete($record);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Details')->schema([
                    Forms\Components\DatePicker::make('order_date')->required()->default(now()),
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')->required()->searchable()->preload(),
                    Forms\Components\Select::make('warehouse_id')
                        ->relationship('warehouse', 'name')->required()->searchable()->preload()->live(),
                    Forms\Components\Select::make('zone_id')
                        ->relationship('zone', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('sales_rep_id')
                        ->relationship('salesRep', 'name')->searchable()->preload(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                self::applyPricing($set, $get, $state, $get('bag_count'));
                            }),
                        Forms\Components\TextInput::make('bag_count')
                            ->label('Bags')
                            ->required()
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                self::applyPricing($set, $get, $get('product_id'), $state);
                            }),
                        Forms\Components\TextInput::make('rate_per_bag')->required()->numeric()->prefix('LKR '),
                        Forms\Components\TextInput::make('discount_per_bag')->required()->numeric()->default(0)->prefix('LKR '),
                        Forms\Components\TextInput::make('line_total')->numeric()->prefix('LKR ')->disabled()->dehydrated(),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->required()
                    ->minItems(1)
                    ->live(),

                Forms\Components\Section::make('Totals')->schema([
                    Forms\Components\Placeholder::make('subtotal_display')
                        ->label('Subtotal')
                        ->content(fn (Get $get) => 'LKR '.number_format(self::sumItems($get, 'rate_per_bag'), 2)),
                    Forms\Components\Placeholder::make('discount_display')
                        ->label('Discount Total')
                        ->content(fn (Get $get) => 'LKR '.number_format(self::sumItems($get, 'discount_per_bag'), 2)),
                    Forms\Components\Placeholder::make('total_display')
                        ->label('Total Amount')
                        ->content(fn (Get $get) => 'LKR '.number_format(self::sumItems($get, 'rate_per_bag') - self::sumItems($get, 'discount_per_bag'), 2)),
                ])->columns(3),
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

    protected static function sumItems(Get $get, string $field): float
    {
        $items = $get('items') ?? [];

        return collect($items)->sum(function ($item) use ($field) {
            $bagCount = (float) ($item['bag_count'] ?? 0);

            return $field === 'rate_per_bag'
                ? $bagCount * (float) ($item['rate_per_bag'] ?? 0)
                : $bagCount * (float) ($item['discount_per_bag'] ?? 0);
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_no')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('order_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')->sortable(),
                Tables\Columns\TextColumn::make('salesRep.name')->placeholder('—'),
                Tables\Columns\TextColumn::make('total_amount')->money('lkr')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'draft' => 'gray',
                    'confirmed' => 'warning',
                    'delivered' => 'info',
                    'invoiced' => 'success',
                    'cancelled' => 'danger',
                }),
            ])
            ->defaultSort('order_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('sales_rep_id')->relationship('salesRep', 'name')->label('Sales Rep'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'confirmed' => 'Confirmed',
                    'delivered' => 'Delivered',
                    'invoiced' => 'Invoiced',
                    'cancelled' => 'Cancelled',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('printReceipt')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn (SalesOrder $record) => app(SalesOrderReceiptService::class)->toPdf($record)),
                Tables\Actions\Action::make('confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SalesOrder $record) => $record->status === 'draft' && Auth::user()->can('sales_orders.approve'))
                    ->requiresConfirmation()
                    ->action(function (SalesOrder $record) {
                        app(SalesOrderService::class)->confirm($record, Auth::user());
                    }),
                Tables\Actions\Action::make('deliver')
                    ->icon('heroicon-o-truck')
                    ->visible(fn (SalesOrder $record) => $record->status === 'confirmed' && Auth::user()->can('sales_orders.approve'))
                    ->requiresConfirmation()
                    ->action(fn (SalesOrder $record) => app(SalesOrderService::class)->deliver($record)),
                Tables\Actions\Action::make('invoice')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (SalesOrder $record) => in_array($record->status, ['confirmed', 'delivered']) && Auth::user()->can('sales_orders.approve'))
                    ->requiresConfirmation()
                    ->action(fn (SalesOrder $record) => app(SalesOrderService::class)->invoice($record)),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SalesOrder $record) => ! in_array($record->status, ['cancelled']) && Auth::user()->can('sales_orders.approve'))
                    ->requiresConfirmation()
                    ->action(fn (SalesOrder $record) => app(SalesOrderService::class)->cancel($record, Auth::user())),
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
            'index' => Pages\ListSalesOrders::route('/'),
            'create' => Pages\CreateSalesOrder::route('/create'),
            'edit' => Pages\EditSalesOrder::route('/{record}/edit'),
            'view' => Pages\ViewSalesOrder::route('/{record}'),
        ];
    }
}

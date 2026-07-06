<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\SalesOrderReceiptService;
use Filament\Actions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOrder extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printReceipt')
                ->label('Print Receipt')
                ->icon('heroicon-o-printer')
                ->action(fn (SalesOrder $record) => app(SalesOrderReceiptService::class)->toPdf($record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Order Summary')->schema([
                TextEntry::make('order_no'),
                TextEntry::make('order_date')->date(),
                TextEntry::make('customer.name'),
                TextEntry::make('warehouse.name'),
                TextEntry::make('salesRep.name')->placeholder('—'),
                TextEntry::make('status')->badge(),
            ])->columns(3),
            Section::make('Line Items')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('product.name'),
                    TextEntry::make('bag_count')->label('Bags'),
                    TextEntry::make('rate_per_bag')->money('lkr'),
                    TextEntry::make('discount_per_bag')->money('lkr'),
                    TextEntry::make('line_total')->money('lkr'),
                ])->columns(5),
            ]),
            Section::make('Totals')->schema([
                TextEntry::make('subtotal')->money('lkr'),
                TextEntry::make('discount_total')->money('lkr'),
                TextEntry::make('total_amount')->money('lkr'),
            ])->columns(3),
        ]);
    }
}

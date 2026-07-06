<?php

namespace App\Filament\Resources\SalesOrderResource\Pages;

use App\Filament\Resources\SalesOrderResource;
use App\Models\VoucherSequence;
use App\Services\SalesOrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_no'] = VoucherSequence::next('sales_order');
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        app(SalesOrderService::class)->recalculateTotals($this->record);
    }
}

<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use App\Models\VoucherSequence;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockTransfer extends CreateRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['transfer_no'] = VoucherSequence::next('stock_transfer');
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';

        return $data;
    }
}

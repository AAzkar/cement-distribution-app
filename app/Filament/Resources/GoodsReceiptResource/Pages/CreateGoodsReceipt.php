<?php

namespace App\Filament\Resources\GoodsReceiptResource\Pages;

use App\Filament\Resources\GoodsReceiptResource;
use App\Models\VoucherSequence;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['receipt_no'] = VoucherSequence::next('goods_receipt');
        $data['created_by'] = Auth::id();
        $data['status'] = 'draft';

        return $data;
    }
}

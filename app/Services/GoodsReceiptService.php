<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoodsReceiptService
{
    public function __construct(protected StockService $stock) {}

    public function confirm(GoodsReceipt $receipt, User $user): GoodsReceipt
    {
        if ($receipt->status !== 'draft') {
            throw new RuntimeException('Only draft goods receipts can be confirmed.');
        }

        return DB::transaction(function () use ($receipt, $user) {
            foreach ($receipt->items()->with('product')->get() as $item) {
                $this->stock->adjust(
                    warehouse: $receipt->warehouse,
                    product: $item->product,
                    delta: $item->quantity,
                    type: 'receipt',
                    user: $user,
                    reference: $receipt,
                    date: $receipt->receipt_date,
                );
            }

            $receipt->update(['status' => 'confirmed', 'confirmed_at' => now()]);

            return $receipt->fresh();
        });
    }
}

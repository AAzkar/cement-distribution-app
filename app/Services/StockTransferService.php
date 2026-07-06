<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockTransferService
{
    public function __construct(protected StockService $stock) {}

    public function confirm(StockTransfer $transfer, User $user): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new RuntimeException('Only draft transfers can be confirmed.');
        }

        if (! $this->stock->hasSufficientStock($transfer->fromWarehouse, $transfer->product, $transfer->quantity)) {
            throw new RuntimeException("Insufficient stock at {$transfer->fromWarehouse->name} to transfer {$transfer->quantity} bag(s).");
        }

        return DB::transaction(function () use ($transfer, $user) {
            $this->stock->adjust(
                warehouse: $transfer->fromWarehouse,
                product: $transfer->product,
                delta: -$transfer->quantity,
                type: 'transfer_out',
                user: $user,
                reference: $transfer,
                date: $transfer->transfer_date,
            );

            $this->stock->adjust(
                warehouse: $transfer->toWarehouse,
                product: $transfer->product,
                delta: $transfer->quantity,
                type: 'transfer_in',
                user: $user,
                reference: $transfer,
                date: $transfer->transfer_date,
            );

            $transfer->update(['status' => 'confirmed', 'confirmed_at' => now()]);

            return $transfer->fresh();
        });
    }
}

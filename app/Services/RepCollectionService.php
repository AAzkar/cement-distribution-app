<?php

namespace App\Services;

use App\Models\CashbookEntry;
use App\Models\PaymentMode;
use App\Models\RepCollection;
use App\Models\User;
use App\Models\VoucherSequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RepCollectionService
{
    public function approve(RepCollection $collection, User $approver): RepCollection
    {
        if ($collection->status === 'confirmed') {
            throw new RuntimeException('This collection has already been confirmed.');
        }

        return DB::transaction(function () use ($collection, $approver) {
            if (in_array($collection->mode, ['cash', 'bank_transfer'])) {
                $paymentModeId = PaymentMode::where('code', $collection->mode)->value('id');

                $entry = CashbookEntry::create([
                    'voucher_no' => VoucherSequence::next('inflow'),
                    'entry_date' => $collection->entry_date,
                    'direction' => 'inflow',
                    'subtype' => 'sales_rep_collection',
                    'warehouse_id' => $collection->warehouse_id,
                    'zone_id' => $collection->zone_id,
                    'amount' => $collection->amount,
                    'payment_mode_id' => $paymentModeId,
                    'reference' => "Rep collection #{$collection->id}",
                    'description' => 'Sales rep collection approved directly',
                    'customer_id' => $collection->customer_id,
                    'sales_rep_id' => $collection->sales_rep_id,
                    'status' => 'approved',
                    'created_by' => $approver->id,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                ]);

                $collection->update(['cashbook_entry_id' => $entry->id, 'status' => 'confirmed']);
            } else {
                // Cheque collections are tracked via the ChequeReceived lifecycle instead
                // of a cashbook entry — approving here just confirms the collection record.
                $collection->update(['status' => 'confirmed']);
            }

            return $collection->fresh();
        });
    }
}

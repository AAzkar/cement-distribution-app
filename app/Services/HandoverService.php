<?php

namespace App\Services;

use App\Models\CashbookEntry;
use App\Models\ChequeReceived;
use App\Models\Handover;
use App\Models\PaymentMode;
use App\Models\RepCollection;
use App\Models\User;
use App\Models\VoucherSequence;
use Illuminate\Support\Facades\DB;

class HandoverService
{
    public function submit(Handover $handover): Handover
    {
        return DB::transaction(function () use ($handover) {
            $collections = RepCollection::query()
                ->where('sales_rep_id', $handover->sales_rep_id)
                ->where('warehouse_id', $handover->warehouse_id)
                ->whereNull('handover_id')
                ->where('status', 'pending')
                ->whereDate('entry_date', '<=', $handover->handover_date)
                ->get();

            $collections->each->update(['handover_id' => $handover->id, 'status' => 'handed_over']);

            $cheques = ChequeReceived::query()
                ->where('sales_rep_id', $handover->sales_rep_id)
                ->whereNull('handover_id')
                ->whereDate('received_date', '<=', $handover->handover_date)
                ->get();

            $cheques->each->update(['handover_id' => $handover->id]);

            $handover->update([
                'cash_total' => $collections->where('mode', 'cash')->sum('amount'),
                'cheque_total' => $cheques->sum('amount'),
                'cheque_count' => $cheques->count(),
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            return $handover->fresh();
        });
    }

    public function confirm(Handover $handover, User $confirmedBy): Handover
    {
        return DB::transaction(function () use ($handover, $confirmedBy) {
            $handover->update([
                'status' => 'confirmed',
                'confirmed_by' => $confirmedBy->id,
                'confirmed_at' => now(),
            ]);

            $handover->collections()
                ->whereIn('mode', ['cash', 'bank_transfer'])
                ->where('status', 'handed_over')
                ->get()
                ->each(function (RepCollection $collection) use ($handover, $confirmedBy) {
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
                        'reference' => 'Handover #'.$handover->id,
                        'description' => 'Sales rep collection handover',
                        'customer_id' => $collection->customer_id,
                        'sales_rep_id' => $collection->sales_rep_id,
                        'status' => 'pending_approval',
                        'created_by' => $confirmedBy->id,
                    ]);

                    $collection->update(['cashbook_entry_id' => $entry->id, 'status' => 'confirmed']);
                });

            $handover->collections()
                ->where('mode', 'cheque')
                ->where('status', 'handed_over')
                ->update(['status' => 'confirmed']);

            return $handover->fresh();
        });
    }
}

<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;

class CustomerLedgerService
{
    public function buildLedger(Customer $customer): array
    {
        $entries = collect();

        foreach ($customer->salesOrders()->whereIn('status', ['confirmed', 'delivered', 'invoiced'])->get() as $order) {
            $entries->push([
                'date' => Carbon::parse($order->order_date),
                'type' => 'order',
                'reference' => $order,
                'description' => "Sales Order {$order->order_no}",
                'debit' => (float) $order->total_amount,
                'credit' => 0.0,
            ]);
        }

        foreach ($customer->cashbookEntries()->where('direction', 'inflow')->whereIn('status', ['approved', 'locked'])->get() as $entry) {
            $entries->push([
                'date' => Carbon::parse($entry->entry_date),
                'type' => 'cashbook',
                'reference' => $entry,
                'description' => $entry->voucher_no ? "Payment {$entry->voucher_no}" : 'Payment received',
                'debit' => 0.0,
                'credit' => (float) $entry->amount,
            ]);
        }

        foreach ($customer->chequesReceived()->where('status', 'cleared')->get() as $cheque) {
            $entries->push([
                'date' => Carbon::parse($cheque->deposit_date ?? $cheque->received_date),
                'type' => 'cheque',
                'reference' => $cheque,
                'description' => "Cheque {$cheque->cheque_no} ({$cheque->bank_name})",
                'debit' => 0.0,
                'credit' => (float) $cheque->amount,
            ]);
        }

        $balance = (float) $customer->opening_balance;

        $sorted = $entries->sortBy('date')->values()->map(function ($row) use (&$balance) {
            $balance += $row['debit'] - $row['credit'];
            $row['running_balance'] = $balance;

            return $row;
        });

        return [
            'opening_balance' => (float) $customer->opening_balance,
            'entries' => $sorted,
            'closing_balance' => $balance,
        ];
    }

    /**
     * FIFO-allocates credits against the oldest outstanding debits (opening balance first,
     * then sales orders oldest to newest) to age the remaining unpaid amount per debit.
     */
    public function agingBuckets(Customer $customer): array
    {
        $debits = collect();

        if ((float) $customer->opening_balance !== 0.0) {
            $debits->push([
                // Sentinel date well before any real business activity, so the opening
                // balance is always the first debit consumed by incoming payments and,
                // if it survives, always ages into the 90+ bucket.
                'date' => Carbon::create(1970, 1, 1),
                'remaining' => (float) $customer->opening_balance,
            ]);
        }

        foreach ($customer->salesOrders()->whereIn('status', ['confirmed', 'delivered', 'invoiced'])->orderBy('order_date')->get() as $order) {
            $debits->push([
                'date' => Carbon::parse($order->order_date),
                'remaining' => (float) $order->total_amount,
            ]);
        }

        // Cast to a plain array: nested-array mutation (`$debits[$i]['remaining'] -= ...`)
        // silently no-ops on a Collection, since offsetGet returns items by value.
        $debits = $debits->sortBy('date')->values()->all();

        $totalCredit = $customer->cashbookEntries()->where('direction', 'inflow')->whereIn('status', ['approved', 'locked'])->sum('amount')
            + $customer->chequesReceived()->where('status', 'cleared')->sum('amount');

        foreach ($debits as $index => $debit) {
            if ($totalCredit <= 0) {
                break;
            }

            $applied = min($debit['remaining'], $totalCredit);
            $debits[$index]['remaining'] -= $applied;
            $totalCredit -= $applied;
        }

        $buckets = [
            'current' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_90_plus' => 0.0,
        ];

        foreach ($debits as $debit) {
            if ($debit['remaining'] <= 0) {
                continue;
            }

            $age = now()->diffInDays($debit['date'], absolute: true);

            $bucket = match (true) {
                $age <= 30 => 'current',
                $age <= 60 => 'days_31_60',
                $age <= 90 => 'days_61_90',
                default => 'days_90_plus',
            };

            $buckets[$bucket] += $debit['remaining'];
        }

        $buckets['total'] = array_sum($buckets);

        return $buckets;
    }

    public function summary(Customer $customer): array
    {
        return [
            'outstanding_balance' => $customer->outstandingBalance(),
            'aging' => $this->agingBuckets($customer),
            'credit_limit_overage' => $customer->creditLimitExceededBy(0),
        ];
    }
}

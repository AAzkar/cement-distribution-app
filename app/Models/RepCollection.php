<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[Fillable([
    'sales_rep_id', 'warehouse_id', 'zone_id', 'customer_id', 'entry_date', 'mode', 'amount',
    'reference', 'cheque_received_id', 'handover_id', 'cashbook_entry_id', 'status',
])]
class RepCollection extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function chequeReceived(): BelongsTo
    {
        return $this->belongsTo(ChequeReceived::class);
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(Handover::class);
    }

    public function cashbookEntry(): BelongsTo
    {
        return $this->belongsTo(CashbookEntry::class);
    }
}

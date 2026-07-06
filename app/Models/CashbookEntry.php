<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'voucher_no', 'entry_date', 'direction', 'subtype', 'warehouse_id', 'zone_id', 'amount',
    'payment_mode_id', 'reference', 'description', 'expense_category_id', 'customer_id',
    'supplier_id', 'sales_rep_id', 'bank_account_id', 'cheque_received_id', 'cheque_issued_id',
    'status', 'created_by', 'approved_by', 'approved_at', 'locked_at',
])]
class CashbookEntry extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(PaymentMode::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function chequeReceived(): BelongsTo
    {
        return $this->belongsTo(ChequeReceived::class);
    }

    public function chequeIssued(): BelongsTo
    {
        return $this->belongsTo(ChequeIssued::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

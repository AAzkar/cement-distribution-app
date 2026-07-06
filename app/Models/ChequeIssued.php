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
    'supplier_id', 'payee_name', 'cashbook_entry_id', 'bank_name', 'cheque_no', 'amount',
    'issue_date', 'bank_account_id', 'status', 'cleared_date', 'bounced_reason', 'notes',
])]
class ChequeIssued extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    protected $table = 'cheques_issued';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issue_date' => 'date',
            'cleared_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function cashbookEntry(): BelongsTo
    {
        return $this->belongsTo(CashbookEntry::class);
    }
}

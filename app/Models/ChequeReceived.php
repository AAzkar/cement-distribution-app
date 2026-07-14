<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'customer_id', 'sales_rep_id', 'handover_id', 'cashbook_entry_id', 'bank_name', 'cheque_no',
    'amount', 'received_date', 'deposit_date', 'bank_account_id', 'status',
    'returned_reason', 'returned_date', 'notes',
])]
class ChequeReceived extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    protected $table = 'cheques_received';

    protected static function booted(): void
    {
        static::updated(function (ChequeReceived $cheque) {
            if ($cheque->wasChanged('status') && $cheque->status === 'returned') {
                $recipients = User::role(['Admin', 'Accountant'])->get()
                    ->when($cheque->salesRep, fn ($users) => $users->push($cheque->salesRep))
                    ->unique('id');

                if ($recipients->isEmpty()) {
                    return;
                }

                Notification::make()
                    ->title("Cheque returned: {$cheque->cheque_no}")
                    ->body("{$cheque->bank_name} — LKR ".number_format((float) $cheque->amount, 2).($cheque->returned_reason ? " — reason: {$cheque->returned_reason}" : ''))
                    ->danger()
                    ->sendToDatabase($recipients);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'received_date' => 'date',
            'deposit_date' => 'date',
            'returned_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(Handover::class);
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

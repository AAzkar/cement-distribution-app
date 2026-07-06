<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['warehouse_id', 'bank_name', 'account_name', 'account_number', 'branch', 'opening_balance', 'is_active'])]
class BankAccount extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashbookEntries(): HasMany
    {
        return $this->hasMany(CashbookEntry::class);
    }

    public function chequesReceived(): HasMany
    {
        return $this->hasMany(ChequeReceived::class);
    }

    public function chequesIssued(): HasMany
    {
        return $this->hasMany(ChequeIssued::class);
    }
}

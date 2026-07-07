<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[Fillable(['name', 'code', 'type', 'phone', 'email', 'address', 'zone_id', 'warehouse_id', 'opening_balance', 'credit_limit', 'is_active'])]
class Customer extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->qr_token ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function qrLookupUrl(): string
    {
        $this->ensureQrToken();

        return \App\Filament\Rep\Pages\CustomerLookup::getUrl(['token' => $this->qr_token], panel: 'rep');
    }

    public function ensureQrToken(): void
    {
        if (! $this->qr_token) {
            $this->forceFill(['qr_token' => (string) Str::uuid()])->save();
        }
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
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

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function outstandingBalance(): float
    {
        $ordered = $this->salesOrders()
            ->whereIn('status', ['confirmed', 'delivered', 'invoiced'])
            ->sum('total_amount');

        $paidByCashbook = $this->cashbookEntries()
            ->where('direction', 'inflow')
            ->whereIn('status', ['approved', 'locked'])
            ->sum('amount');

        $paidByCheque = $this->chequesReceived()
            ->where('status', 'cleared')
            ->sum('amount');

        return (float) $this->opening_balance + (float) $ordered - (float) $paidByCashbook - (float) $paidByCheque;
    }

    public function creditLimitExceededBy(float $additionalAmount): ?float
    {
        if ($this->credit_limit === null) {
            return null;
        }

        $overage = $this->outstandingBalance() + $additionalAmount - (float) $this->credit_limit;

        return $overage > 0 ? $overage : null;
    }
}

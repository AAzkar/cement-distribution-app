<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[Fillable(['sales_rep_id', 'warehouse_id', 'handover_date', 'cash_total', 'cheque_total', 'cheque_count', 'status', 'submitted_at', 'confirmed_by', 'confirmed_at', 'notes'])]
class Handover extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'handover_date' => 'date',
            'cash_total' => 'decimal:2',
            'cheque_total' => 'decimal:2',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
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

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(RepCollection::class);
    }

    public function chequesReceived(): HasMany
    {
        return $this->hasMany(ChequeReceived::class);
    }
}

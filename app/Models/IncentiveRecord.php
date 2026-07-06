<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[Fillable([
    'sales_rep_id', 'incentive_rule_id', 'record_date', 'metric_value', 'calculated_amount',
    'override_amount', 'final_amount', 'status', 'approved_by', 'approved_at', 'notes',
])]
class IncentiveRecord extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'metric_value' => 'decimal:2',
            'calculated_amount' => 'decimal:2',
            'override_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'approved_at' => 'datetime',
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

    public function incentiveRule(): BelongsTo
    {
        return $this->belongsTo(IncentiveRule::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

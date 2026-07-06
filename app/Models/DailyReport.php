<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[Fillable([
    'report_date', 'warehouse_id', 'opening_balance', 'total_inflows', 'total_outflows',
    'closing_balance', 'cheques_summary', 'status', 'submitted_by', 'submitted_at',
    'approved_by', 'approved_at',
])]
class DailyReport extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'opening_balance' => 'decimal:2',
            'total_inflows' => 'decimal:2',
            'total_outflows' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'cheques_summary' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

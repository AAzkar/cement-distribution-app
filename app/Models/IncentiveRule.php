<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'name', 'metric', 'rule_type', 'min_target', 'slabs', 'allowance_type',
    'fixed_amount', 'percentage', 'warehouse_id', 'zone_id', 'is_active',
])]
class IncentiveRule extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'min_target' => 'decimal:2',
            'slabs' => 'array',
            'fixed_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'is_active' => 'boolean',
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

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(IncentiveRecord::class);
    }
}

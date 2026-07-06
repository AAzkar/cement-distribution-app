<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['bank_reconciliation_id', 'reconcilable_type', 'reconcilable_id', 'is_cleared', 'cleared_date'])]
class ReconciliationItem extends Model
{
    protected function casts(): array
    {
        return [
            'is_cleared' => 'boolean',
            'cleared_date' => 'date',
        ];
    }

    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function reconcilable(): MorphTo
    {
        return $this->morphTo();
    }
}

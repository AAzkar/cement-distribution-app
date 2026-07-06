<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['key', 'warehouse_id', 'prefix', 'padding', 'next_number'])]
class VoucherSequence extends Model
{
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public static function next(string $key): string
    {
        return DB::transaction(function () use ($key) {
            $sequence = static::where('key', $key)->lockForUpdate()->firstOrFail();

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            return $sequence->prefix.str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}

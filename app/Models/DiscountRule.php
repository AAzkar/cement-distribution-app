<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'name', 'min_bags', 'max_bags', 'discount_type', 'discount_value', 'is_active'])]
class DiscountRule extends Model
{
    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function matches(int $bagCount): bool
    {
        return $bagCount >= $this->min_bags && ($this->max_bags === null || $bagCount <= $this->max_bags);
    }

    public function discountPerBag(float $ratePerBag): float
    {
        return $this->discount_type === 'percentage'
            ? round($ratePerBag * ((float) $this->discount_value / 100), 2)
            : (float) $this->discount_value;
    }
}

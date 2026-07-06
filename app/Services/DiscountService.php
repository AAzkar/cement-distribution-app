<?php

namespace App\Services;

use App\Models\DiscountRule;
use App\Models\Product;

class DiscountService
{
    public function resolveDiscountPerBag(Product $product, int $bagCount): float
    {
        $rule = $this->bestRule($product, $bagCount);

        if (! $rule) {
            return 0.0;
        }

        return $rule->discountPerBag((float) $product->base_price_per_bag);
    }

    protected function bestRule(Product $product, int $bagCount): ?DiscountRule
    {
        $productSpecific = DiscountRule::where('product_id', $product->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (DiscountRule $rule) => $rule->matches($bagCount))
            ->sortByDesc('min_bags')
            ->first();

        if ($productSpecific) {
            return $productSpecific;
        }

        return DiscountRule::whereNull('product_id')
            ->where('is_active', true)
            ->get()
            ->filter(fn (DiscountRule $rule) => $rule->matches($bagCount))
            ->sortByDesc('min_bags')
            ->first();
    }
}

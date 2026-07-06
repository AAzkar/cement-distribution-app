<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $fillable = ['sales_order_id', 'product_id', 'bag_count', 'rate_per_bag', 'discount_per_bag', 'line_total'];

    protected function casts(): array
    {
        return [
            'rate_per_bag' => 'decimal:2',
            'discount_per_bag' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

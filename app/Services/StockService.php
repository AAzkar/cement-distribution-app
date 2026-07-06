<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function adjust(
        Warehouse $warehouse,
        Product $product,
        int $delta,
        string $type,
        User $user,
        ?Model $reference = null,
        ?CarbonInterface $date = null,
        ?string $notes = null,
    ): StockMovement {
        return DB::transaction(function () use ($warehouse, $product, $delta, $type, $user, $reference, $date, $notes) {
            $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = WarehouseStock::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                ]);
            }

            $stock->increment('quantity', $delta);

            return StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'movement_date' => $date ?? now(),
                'type' => $type,
                'quantity' => $delta,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->id,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);
        });
    }

    public function quantityOnHand(Warehouse $warehouse, Product $product): int
    {
        return (int) (WarehouseStock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity') ?? 0);
    }

    public function hasSufficientStock(Warehouse $warehouse, Product $product, int $quantity): bool
    {
        return $this->quantityOnHand($warehouse, $product) >= $quantity;
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
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
        [$movement, $stock, $crossedLowStock] = DB::transaction(function () use ($warehouse, $product, $delta, $type, $user, $reference, $date, $notes) {
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

            $before = $stock->quantity;
            $stock->increment('quantity', $delta);
            $after = $stock->quantity;

            $movement = StockMovement::create([
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

            $crossedLowStock = $stock->reorder_level !== null
                && $before > $stock->reorder_level
                && $after <= $stock->reorder_level;

            return [$movement, $stock, $crossedLowStock];
        });

        if ($crossedLowStock) {
            $this->notifyLowStock($stock, $warehouse, $product);
        }

        return $movement;
    }

    private function notifyLowStock(WarehouseStock $stock, Warehouse $warehouse, Product $product): void
    {
        $recipients = User::role(['Admin', 'Warehouse Manager'])->get()
            ->merge(User::whereHas('warehouses', fn ($query) => $query->where('warehouses.id', $warehouse->id))->get())
            ->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title("Low stock: {$product->name} at {$warehouse->name}")
            ->body("Only {$stock->quantity} bags remaining (reorder level: {$stock->reorder_level}).")
            ->warning()
            ->sendToDatabase($recipients);
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

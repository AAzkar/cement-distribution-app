<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DiscountRule;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DiscountService;
use App\Services\GoodsReceiptService;
use App\Services\SalesOrderService;
use App\Services\StockService;
use App\Services\StockTransferService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_discount_service_resolves_product_specific_slab_over_global(): void
    {
        $opc53 = Product::where('code', 'OPC53')->firstOrFail();

        $discount = app(DiscountService::class)->resolveDiscountPerBag($opc53, 250);

        // OPC 53 has its own 200+ bag rule at 5% of $9.25 = 0.4625, rounded to 0.46, which should win over the global 100+ bag flat $0.15 rule.
        $this->assertEqualsWithDelta(0.46, $discount, 0.0001);
    }

    public function test_discount_service_falls_back_to_global_slab(): void
    {
        $ppc = Product::where('code', 'PPC')->firstOrFail();

        $discount = app(DiscountService::class)->resolveDiscountPerBag($ppc, 150);

        $this->assertEquals(0.15, $discount);
    }

    public function test_discount_service_returns_zero_below_any_threshold(): void
    {
        $ppc = Product::where('code', 'PPC')->firstOrFail();

        $discount = app(DiscountService::class)->resolveDiscountPerBag($ppc, 10);

        $this->assertEquals(0.0, $discount);
    }

    public function test_goods_receipt_confirmation_increases_stock(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $stockService = app(StockService::class);

        $before = $stockService->quantityOnHand($warehouse, $product);

        $receipt = GoodsReceipt::create([
            'receipt_no' => 'GR-TEST-1',
            'warehouse_id' => $warehouse->id,
            'receipt_date' => now(),
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $receipt->items()->create(['product_id' => $product->id, 'quantity' => 200, 'unit_cost' => 5]);

        app(GoodsReceiptService::class)->confirm($receipt->fresh(), $admin);

        $this->assertEquals($before + 200, $stockService->quantityOnHand($warehouse, $product));
        $this->assertEquals('confirmed', $receipt->fresh()->status);
    }

    public function test_stock_transfer_moves_bags_between_warehouses(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        [$warehouseA, $warehouseB] = Warehouse::orderBy('id')->take(2)->get();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $stockService = app(StockService::class);

        $beforeA = $stockService->quantityOnHand($warehouseA, $product);
        $beforeB = $stockService->quantityOnHand($warehouseB, $product);

        $transfer = StockTransfer::create([
            'transfer_no' => 'ST-TEST-1',
            'from_warehouse_id' => $warehouseA->id,
            'to_warehouse_id' => $warehouseB->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'transfer_date' => now(),
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        app(StockTransferService::class)->confirm($transfer, $admin);

        $this->assertEquals($beforeA - 100, $stockService->quantityOnHand($warehouseA, $product));
        $this->assertEquals($beforeB + 100, $stockService->quantityOnHand($warehouseB, $product));
    }

    public function test_sales_order_confirm_deducts_stock_and_applies_discount(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'PPC')->firstOrFail();
        $stockService = app(StockService::class);

        $beforeStock = $stockService->quantityOnHand($warehouse, $product);

        $customer = Customer::create([
            'name' => 'Bulk Buyer Shop',
            'code' => 'CUST-BULK-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $order = SalesOrder::create([
            'order_no' => 'SO-TEST-2',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $itemData = app(SalesOrderService::class)->buildItemData($product->id, 150);
        $order->items()->create($itemData);
        app(SalesOrderService::class)->recalculateTotals($order);

        $this->assertEquals(0.15, $itemData['discount_per_bag']);
        $this->assertEquals((8.00 - 0.15) * 150, $order->fresh()->total_amount);

        $confirmed = app(SalesOrderService::class)->confirm($order->fresh(), $admin);

        $this->assertEquals('confirmed', $confirmed->status);
        $this->assertEquals($beforeStock - 150, $stockService->quantityOnHand($warehouse, $product));
    }

    public function test_sales_order_confirm_fails_when_insufficient_stock(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'PPC')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Overorder Shop',
            'code' => 'CUST-OVER-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $order = SalesOrder::create([
            'order_no' => 'SO-TEST-3',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $order->items()->create(app(SalesOrderService::class)->buildItemData($product->id, 999999));
        app(SalesOrderService::class)->recalculateTotals($order);

        $this->expectException(\RuntimeException::class);
        app(SalesOrderService::class)->confirm($order->fresh(), $admin);
    }

    public function test_sales_order_cancel_reverses_stock_deduction(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $stockService = app(StockService::class);

        $beforeStock = $stockService->quantityOnHand($warehouse, $product);

        $customer = Customer::create([
            'name' => 'Cancel Test Shop',
            'code' => 'CUST-CANCEL-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $order = SalesOrder::create([
            'order_no' => 'SO-TEST-4',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(SalesOrderService::class)->buildItemData($product->id, 50));
        app(SalesOrderService::class)->recalculateTotals($order);

        $service = app(SalesOrderService::class);
        $service->confirm($order->fresh(), $admin);
        $this->assertEquals($beforeStock - 50, $stockService->quantityOnHand($warehouse, $product));

        $service->cancel($order->fresh(), $admin);
        $this->assertEquals($beforeStock, $stockService->quantityOnHand($warehouse, $product));
        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    public function test_customer_outstanding_balance_reflects_confirmed_order(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();

        $customer = Customer::create([
            'name' => 'Ledger Test Shop',
            'code' => 'CUST-LEDGER-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $order = SalesOrder::create([
            'order_no' => 'SO-TEST-5',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(SalesOrderService::class)->buildItemData($product->id, 10));
        app(SalesOrderService::class)->recalculateTotals($order);

        $this->assertEquals(0.0, $customer->fresh()->outstandingBalance());

        app(SalesOrderService::class)->confirm($order->fresh(), $admin);

        $expected = (float) $order->fresh()->total_amount;
        $this->assertEqualsWithDelta($expected, $customer->fresh()->outstandingBalance(), 0.01);
    }
}

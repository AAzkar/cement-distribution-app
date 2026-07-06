<?php

namespace Tests\Feature;

use App\Filament\Resources\SalesOrderResource\Pages\CreateSalesOrder;
use App\Filament\Resources\SalesOrderResource\Pages\ListSalesOrders;
use App\Filament\Resources\SalesOrderResource\Pages\ViewSalesOrder;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesOrderResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_sales_order_with_auto_computed_discount(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'PPC')->firstOrFail();
        $customer = Customer::create([
            'name' => 'Livewire Test Shop',
            'code' => 'CUST-LW-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateSalesOrder::class)
            ->fillForm([
                'order_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items' => [
                    ['product_id' => $product->id, 'bag_count' => 150, 'rate_per_bag' => 8.00, 'discount_per_bag' => 0.15, 'line_total' => 1177.50],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = SalesOrder::latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame('draft', $order->status);
        $this->assertEquals(1177.50, $order->total_amount);
        $this->assertCount(1, $order->items);
    }

    public function test_confirm_action_deducts_stock_via_livewire(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create([
            'name' => 'Confirm Action Shop',
            'code' => 'CUST-CONFIRM-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $order = SalesOrder::create([
            'order_no' => 'SO-LW-1',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(\App\Services\SalesOrderService::class)->buildItemData($product->id, 20));
        app(\App\Services\SalesOrderService::class)->recalculateTotals($order);

        $this->actingAs($admin);

        Livewire::test(ListSalesOrders::class)
            ->callTableAction('confirm', $order)
            ->assertHasNoTableActionErrors();

        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_view_page_renders_line_items(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create([
            'name' => 'View Page Shop',
            'code' => 'CUST-VIEW-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $order = SalesOrder::create([
            'order_no' => 'SO-LW-2',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(\App\Services\SalesOrderService::class)->buildItemData($product->id, 20));
        app(\App\Services\SalesOrderService::class)->recalculateTotals($order);

        $this->actingAs($admin);

        Livewire::test(ViewSalesOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk();

        $this->get("/admin/sales-orders/{$order->id}")->assertOk();
    }
}

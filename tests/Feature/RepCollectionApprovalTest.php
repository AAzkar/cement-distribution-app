<?php

namespace Tests\Feature;

use App\Filament\Resources\RepCollectionResource\Pages\ListRepCollections;
use App\Filament\Resources\SalesOrderResource\Pages\CreateSalesOrder;
use App\Filament\Resources\SalesOrderResource\Pages\EditSalesOrder;
use App\Models\CashbookEntry;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RepCollection;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\RepCollectionService;
use App\Services\SalesOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RepCollectionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_accountant_and_manager_have_rep_collection_approve_permission(): void
    {
        $accountant = User::where('email', 'accountant@cementco.test')->firstOrFail();
        $manager = User::where('email', 'manager@cementco.test')->firstOrFail();

        $this->assertTrue($accountant->can('rep_collections.approve'));
        $this->assertTrue($manager->can('rep_collections.approve'));
    }

    public function test_approving_a_cash_collection_creates_approved_cashbook_entry_and_updates_customer_balance(): void
    {
        $accountant = User::where('email', 'accountant@cementco.test')->firstOrFail();
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $customer = Customer::create(['name' => 'Approve Test Shop', 'code' => 'APPROVE-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $collection = RepCollection::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'entry_date' => now(),
            'mode' => 'cash',
            'amount' => 400,
            'status' => 'pending',
        ]);

        $balanceBefore = $customer->outstandingBalance();

        app(RepCollectionService::class)->approve($collection, $accountant);

        $collection->refresh();
        $this->assertSame('confirmed', $collection->status);
        $this->assertNotNull($collection->cashbook_entry_id);

        $entry = CashbookEntry::find($collection->cashbook_entry_id);
        $this->assertSame('approved', $entry->status);
        $this->assertSame('inflow', $entry->direction);
        $this->assertEquals(400, (float) $entry->amount);
        $this->assertSame($customer->id, $entry->customer_id);

        $this->assertEquals($balanceBefore - 400, $customer->fresh()->outstandingBalance());
    }

    public function test_approving_a_cheque_collection_does_not_create_cashbook_entry(): void
    {
        $accountant = User::where('email', 'accountant@cementco.test')->firstOrFail();
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $customer = Customer::create(['name' => 'Cheque Approve Shop', 'code' => 'APPROVE-2', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $collection = RepCollection::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'entry_date' => now(),
            'mode' => 'cheque',
            'amount' => 900,
            'status' => 'pending',
        ]);

        app(RepCollectionService::class)->approve($collection, $accountant);

        $collection->refresh();
        $this->assertSame('confirmed', $collection->status);
        $this->assertNull($collection->cashbook_entry_id);
    }

    public function test_approve_button_is_clickable_from_the_admin_table(): void
    {
        $accountant = User::where('email', 'accountant@cementco.test')->firstOrFail();
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $customer = Customer::create(['name' => 'Button Shop', 'code' => 'APPROVE-3', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $collection = RepCollection::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'entry_date' => now(),
            'mode' => 'cash',
            'amount' => 150,
            'status' => 'pending',
        ]);

        $this->actingAs($accountant);

        Livewire::test(ListRepCollections::class)
            ->assertTableActionExists('approve')
            ->callTableAction('approve', $collection)
            ->assertHasNoTableActionErrors();

        $this->assertSame('confirmed', $collection->fresh()->status);
    }

    public function test_sales_rep_cannot_see_approve_action(): void
    {
        // Sales reps hold rep_collections.create/edit but not .approve — they shouldn't
        // see the button at all, even though they can access the rep-panel resource.
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();

        $this->assertFalse($rep->can('rep_collections.approve'));
    }

    public function test_accountant_can_create_and_edit_sales_orders(): void
    {
        $accountant = User::where('email', 'accountant@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create(['name' => 'Order Mgmt Shop', 'code' => 'ORDMGMT-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $this->assertTrue($accountant->can('sales_orders.create'));
        $this->assertTrue($accountant->can('sales_orders.edit'));

        $this->actingAs($accountant);

        Livewire::test(CreateSalesOrder::class)
            ->fillForm([
                'order_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'items' => [
                    ['product_id' => $product->id, 'bag_count' => 5, 'rate_per_bag' => 8.50, 'discount_per_bag' => 0, 'line_total' => 42.50],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = SalesOrder::latest('id')->first();
        $this->assertNotNull($order);

        Livewire::test(EditSalesOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->fillForm(['notes' => 'Updated by accountant'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated by accountant', $order->fresh()->notes);
    }

    public function test_warehouse_manager_can_create_and_edit_sales_orders(): void
    {
        $manager = User::where('email', 'manager@cementco.test')->firstOrFail();

        $this->assertTrue($manager->can('sales_orders.create'));
        $this->assertTrue($manager->can('sales_orders.edit'));
        $this->assertTrue($manager->can('sales_orders.delete'));
    }
}

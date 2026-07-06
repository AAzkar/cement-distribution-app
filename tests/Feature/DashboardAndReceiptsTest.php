<?php

namespace Tests\Feature;

use App\Filament\Resources\RepCollectionResource\Pages\ListRepCollections as AdminListRepCollections;
use App\Filament\Resources\SalesOrderResource\Pages\ListSalesOrders as AdminListSalesOrders;
use App\Filament\Rep\Resources\RepCollectionResource\Pages\ListRepCollections as RepListRepCollections;
use App\Filament\Rep\Resources\SalesOrderResource\Pages\ListSalesOrders as RepListSalesOrders;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\CashFlowChart;
use App\Filament\Widgets\SalesTrendChart;
use App\Filament\Widgets\TopProductsChart;
use App\Filament\Rep\Widgets\RepStatsOverview;
use App\Models\CashbookEntry;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\RepCollection;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashbookVoucherReceiptService;
use App\Services\RepCollectionReceiptService;
use App\Services\RepCollectionService;
use App\Services\SalesOrderReceiptService;
use App\Services\SalesOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAndReceiptsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_dashboard_loads_with_all_widgets(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $this->actingAs($admin);

        $this->get('/admin')->assertOk();

        Livewire::test(AdminStatsOverview::class)->assertOk();
        Livewire::test(SalesTrendChart::class)->assertOk();
        Livewire::test(CashFlowChart::class)->assertOk();
        Livewire::test(TopProductsChart::class)->assertOk();
    }

    public function test_rep_dashboard_loads_with_stats_widget(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $this->actingAs($rep);

        $this->get('/rep')->assertOk();

        Livewire::test(RepStatsOverview::class)->assertOk();
    }

    public function test_dashboard_widgets_reflect_real_data(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create(['name' => 'Dash Shop', 'code' => 'DASH-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $order = SalesOrder::create([
            'order_no' => 'SO-DASH-1',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(SalesOrderService::class)->buildItemData($product->id, 10));
        app(SalesOrderService::class)->recalculateTotals($order);
        app(SalesOrderService::class)->confirm($order->fresh(), $admin);

        $widget = new AdminStatsOverview;
        $stats = (fn () => $this->getStats())->call($widget);

        $this->assertStringContainsString(number_format(10 * 8.50, 2), $stats[0]->getValue());
    }

    public function test_sales_order_receipt_pdf_downloads_from_admin_and_rep_panels(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create(['name' => 'Receipt Shop', 'code' => 'RCPT-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $order = SalesOrder::create([
            'order_no' => 'SO-RCPT-1',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(SalesOrderService::class)->buildItemData($product->id, 5));
        app(SalesOrderService::class)->recalculateTotals($order);

        $pdf = app(SalesOrderReceiptService::class)->toPdf($order->fresh());
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $pdf);
        ob_start();
        $pdf->sendContent();
        $content = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $content);

        $this->actingAs($admin);
        Livewire::test(AdminListSalesOrders::class)
            ->callTableAction('printReceipt', $order)
            ->assertFileDownloaded();

        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $repOrder = SalesOrder::create([
            'order_no' => 'SO-RCPT-2',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'sales_rep_id' => $rep->id,
            'status' => 'draft',
            'created_by' => $rep->id,
        ]);
        $repOrder->items()->create(app(SalesOrderService::class)->buildItemData($product->id, 3));
        app(SalesOrderService::class)->recalculateTotals($repOrder);

        $this->actingAs($rep);
        Livewire::test(RepListSalesOrders::class)
            ->callTableAction('printReceipt', $repOrder)
            ->assertFileDownloaded();
    }

    public function test_cashbook_voucher_receipt_downloads(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $paymentMode = PaymentMode::where('code', 'cash')->firstOrFail();

        $entry = CashbookEntry::create([
            'voucher_no' => 'IN-VOUCHER-1',
            'entry_date' => now(),
            'direction' => 'inflow',
            'subtype' => 'cash',
            'warehouse_id' => $warehouse->id,
            'amount' => 500,
            'payment_mode_id' => $paymentMode->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $pdf = app(CashbookVoucherReceiptService::class)->toPdf($entry);
        ob_start();
        $pdf->sendContent();
        $content = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_rep_collection_receipt_downloads_from_admin_and_rep_panels(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $customer = Customer::create(['name' => 'Collection Receipt Shop', 'code' => 'CRCPT-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $collection = RepCollection::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'entry_date' => now(),
            'mode' => 'cash',
            'amount' => 250,
            'status' => 'pending',
        ]);

        $pdf = app(RepCollectionReceiptService::class)->toPdf($collection);
        ob_start();
        $pdf->sendContent();
        $content = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $content);

        $this->actingAs($admin);
        Livewire::test(AdminListRepCollections::class)
            ->callTableAction('printReceipt', $collection)
            ->assertFileDownloaded();

        $this->actingAs($rep);
        Livewire::test(RepListRepCollections::class)
            ->callTableAction('printReceipt', $collection)
            ->assertFileDownloaded();
    }

    public function test_rep_collection_receipt_downloads_after_being_approved(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $customer = Customer::create(['name' => 'Approved Receipt Shop', 'code' => 'CRCPT-2', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $collection = RepCollection::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'entry_date' => now(),
            'mode' => 'cash',
            'amount' => 100,
            'status' => 'pending',
        ]);

        app(RepCollectionService::class)->approve($collection, $admin);

        $pdf = app(RepCollectionReceiptService::class)->toPdf($collection->fresh());
        ob_start();
        $pdf->sendContent();
        $content = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $content);
    }
}

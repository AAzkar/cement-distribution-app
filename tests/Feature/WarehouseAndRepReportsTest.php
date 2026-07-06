<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports\SalesRepPerformanceReport;
use App\Filament\Pages\Reports\WarehouseSummaryReport;
use App\Models\CashbookEntry;
use App\Models\Customer;
use App\Models\IncentiveRecord;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\RepCollection;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use App\Services\SalesRepPerformanceReportService;
use App\Services\WarehouseReportService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WarehouseAndRepReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function makeConfirmedOrder(Customer $customer, Warehouse $warehouse, Product $product, Carbon $date, int $bags, User $admin, ?User $rep = null): SalesOrder
    {
        $order = SalesOrder::create([
            'order_no' => 'SO-WRPT-'.uniqid(),
            'order_date' => $date,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'sales_rep_id' => $rep?->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(SalesOrderService::class)->buildItemData($product->id, $bags));
        app(SalesOrderService::class)->recalculateTotals($order);
        app(SalesOrderService::class)->confirm($order->fresh(), $admin);

        return $order->fresh();
    }

    public function test_warehouse_report_aggregates_orders_cash_flow_and_stock(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $paymentMode = PaymentMode::where('code', 'cash')->firstOrFail();
        $customer = Customer::create(['name' => 'WH Report Shop', 'code' => 'WHRPT-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $stockBefore = $warehouse->stocks()->sum('quantity');

        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 3, 10), 20, $admin);

        CashbookEntry::create([
            'voucher_no' => 'IN-WHRPT-1',
            'entry_date' => Carbon::create(2026, 3, 12),
            'direction' => 'inflow',
            'subtype' => 'cash',
            'warehouse_id' => $warehouse->id,
            'amount' => 500,
            'payment_mode_id' => $paymentMode->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        CashbookEntry::create([
            'voucher_no' => 'OUT-WHRPT-1',
            'entry_date' => Carbon::create(2026, 3, 12),
            'direction' => 'outflow',
            'subtype' => 'expense',
            'warehouse_id' => $warehouse->id,
            'amount' => 150,
            'payment_mode_id' => $paymentMode->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $from = Carbon::create(2026, 3, 1)->startOfMonth();
        $to = Carbon::create(2026, 3, 31)->endOfMonth();

        $row = app(WarehouseReportService::class)->query($from, $to)->find($warehouse->id);

        $this->assertEquals(1, $row->orders_count);
        $this->assertEqualsWithDelta(20 * 8.50, (float) $row->orders_total, 0.01);
        $this->assertEquals(500, (float) $row->inflow_total);
        $this->assertEquals(150, (float) $row->outflow_total);
        $this->assertEquals($stockBefore - 20, (int) $row->stock_on_hand);
        $this->assertEquals(20, app(WarehouseReportService::class)->bagsSold($warehouse, $from, $to));
    }

    public function test_sales_rep_performance_report_aggregates_orders_collections_and_incentives(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create(['name' => 'Rep Report Shop', 'code' => 'REPRPT-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 3, 5), 15, $admin, $rep);

        RepCollection::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'entry_date' => Carbon::create(2026, 3, 6),
            'mode' => 'cash',
            'amount' => 300,
            'status' => 'confirmed',
        ]);

        IncentiveRecord::create([
            'sales_rep_id' => $rep->id,
            'record_date' => Carbon::create(2026, 3, 7),
            'metric_value' => 1000,
            'calculated_amount' => 25,
            'final_amount' => 25,
            'status' => 'approved',
        ]);

        // Pending incentive should NOT be counted.
        IncentiveRecord::create([
            'sales_rep_id' => $rep->id,
            'record_date' => Carbon::create(2026, 3, 8),
            'metric_value' => 500,
            'calculated_amount' => 10,
            'final_amount' => 10,
            'status' => 'pending',
        ]);

        $from = Carbon::create(2026, 3, 1)->startOfMonth();
        $to = Carbon::create(2026, 3, 31)->endOfMonth();

        $row = app(SalesRepPerformanceReportService::class)->query($from, $to)->find($rep->id);

        $this->assertEquals(1, $row->orders_count);
        $this->assertEqualsWithDelta(15 * 8.50, (float) $row->orders_total, 0.01);
        $this->assertEquals(300, (float) $row->collections_total);
        $this->assertEquals(25, (float) $row->incentives_total);
        $this->assertEquals(15, app(SalesRepPerformanceReportService::class)->bagsSold($rep, $from, $to));
    }

    public function test_warehouse_summary_report_page_loads_and_export_buttons_download(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(WarehouseSummaryReport::class)->assertOk();

        Livewire::test(WarehouseSummaryReport::class)->callAction('exportPdf')->assertFileDownloaded();
        Livewire::test(WarehouseSummaryReport::class)->callAction('exportExcel')->assertFileDownloaded();
    }

    public function test_sales_rep_performance_report_page_loads_and_export_buttons_download(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(SalesRepPerformanceReport::class)->assertOk();

        Livewire::test(SalesRepPerformanceReport::class)->callAction('exportPdf')->assertFileDownloaded();
        Livewire::test(SalesRepPerformanceReport::class)->callAction('exportExcel')->assertFileDownloaded();
    }

    public function test_sales_representative_cannot_access_warehouse_or_rep_reports(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();

        $this->actingAs($rep)->get('/admin/warehouse-summary-report')->assertForbidden();
        $this->actingAs($rep)->get('/admin/sales-rep-performance-report')->assertForbidden();
    }
}

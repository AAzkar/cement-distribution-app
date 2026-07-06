<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports\CustomerSummaryReport;
use App\Filament\Pages\Reports\SalesOrdersByMonthReport;
use App\Models\CashbookEntry;
use App\Models\Customer;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerReportExportService;
use App\Services\CustomerReportService;
use App\Services\SalesOrderMonthlyReportExportService;
use App\Services\SalesOrderMonthlyReportService;
use App\Services\SalesOrderService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function makeConfirmedOrder(Customer $customer, Warehouse $warehouse, Product $product, Carbon $date, int $bags, User $admin): SalesOrder
    {
        $order = SalesOrder::create([
            'order_no' => 'SO-RPT-'.uniqid(),
            'order_date' => $date,
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
        $order->items()->create(app(SalesOrderService::class)->buildItemData($product->id, $bags));
        app(SalesOrderService::class)->recalculateTotals($order);
        app(SalesOrderService::class)->confirm($order->fresh(), $admin);

        return $order->fresh();
    }

    public function test_customer_report_service_aggregates_within_date_range(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create(['name' => 'Report Shop', 'code' => 'RPT-1', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        // Inside the target month.
        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 3, 10), 10, $admin);
        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 3, 20), 5, $admin);

        // Outside the target month — must not be counted.
        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 4, 5), 100, $admin);

        $from = Carbon::create(2026, 3, 1)->startOfMonth();
        $to = Carbon::create(2026, 3, 31)->endOfMonth();

        $row = app(CustomerReportService::class)->query($from, $to)->find($customer->id);

        $this->assertEquals(2, $row->orders_count);
        $this->assertEqualsWithDelta(15 * 8.50, (float) $row->orders_total, 0.01);
    }

    public function test_customer_report_service_counts_collections_in_range(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $paymentMode = PaymentMode::where('code', 'cash')->firstOrFail();
        $customer = Customer::create(['name' => 'Collections Shop', 'code' => 'RPT-2', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        CashbookEntry::create([
            'voucher_no' => 'IN-RPT-1',
            'entry_date' => Carbon::create(2026, 3, 15),
            'direction' => 'inflow',
            'subtype' => 'cash',
            'warehouse_id' => $warehouse->id,
            'amount' => 250,
            'payment_mode_id' => $paymentMode->id,
            'customer_id' => $customer->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        CashbookEntry::create([
            'voucher_no' => 'IN-RPT-2',
            'entry_date' => Carbon::create(2026, 2, 15),
            'direction' => 'inflow',
            'subtype' => 'cash',
            'warehouse_id' => $warehouse->id,
            'amount' => 999,
            'payment_mode_id' => $paymentMode->id,
            'customer_id' => $customer->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $from = Carbon::create(2026, 3, 1)->startOfMonth();
        $to = Carbon::create(2026, 3, 31)->endOfMonth();

        $row = app(CustomerReportService::class)->query($from, $to)->find($customer->id);

        $this->assertEquals(250, (float) $row->cash_collections_total);
    }

    public function test_sales_order_monthly_report_buckets_by_month(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customer = Customer::create(['name' => 'Monthly Shop', 'code' => 'RPT-3', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 5, 1), 20, $admin);
        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 5, 15), 30, $admin);
        $this->makeConfirmedOrder($customer, $warehouse, $product, Carbon::create(2026, 6, 1), 40, $admin);

        $rows = app(SalesOrderMonthlyReportService::class)->monthlyBreakdown(2026)->keyBy('month');

        $this->assertEquals(2, $rows[5]['orders_count']);
        $this->assertEquals(50, $rows[5]['total_bags']);
        $this->assertEquals(1, $rows[6]['orders_count']);
        $this->assertEquals(40, $rows[6]['total_bags']);
        $this->assertEquals(0, $rows[1]['orders_count']);
    }

    public function test_sales_order_monthly_report_respects_warehouse_filter(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        [$warehouseA, $warehouseB] = Warehouse::orderBy('id')->take(2)->get();
        $product = Product::where('code', 'OPC43')->firstOrFail();
        $customerA = Customer::create(['name' => 'WH A Shop', 'code' => 'RPT-4', 'type' => 'shop', 'warehouse_id' => $warehouseA->id]);
        $customerB = Customer::create(['name' => 'WH B Shop', 'code' => 'RPT-5', 'type' => 'shop', 'warehouse_id' => $warehouseB->id]);

        $this->makeConfirmedOrder($customerA, $warehouseA, $product, Carbon::create(2026, 7, 1), 10, $admin);
        $this->makeConfirmedOrder($customerB, $warehouseB, $product, Carbon::create(2026, 7, 1), 999, $admin);

        $rows = app(SalesOrderMonthlyReportService::class)->monthlyBreakdown(2026, $warehouseA->id)->keyBy('month');

        $this->assertEquals(1, $rows[7]['orders_count']);
        $this->assertEquals(10, $rows[7]['total_bags']);
    }

    public function test_customer_summary_report_page_loads_and_exports(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(CustomerSummaryReport::class)->assertOk();

        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();
        $rows = app(CustomerReportService::class)->query($from, $to)->get();

        $pdf = app(CustomerReportExportService::class)->toPdf($rows, $from, $to);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $pdf);
        ob_start();
        $pdf->sendContent();
        $pdfContent = ob_get_clean();
        $this->assertStringStartsWith('%PDF', $pdfContent);

        $excelResponse = app(CustomerReportExportService::class)->toExcel($rows, $from, $to);
        ob_start();
        $excelResponse->sendContent();
        $excelContent = ob_get_clean();
        $this->assertStringStartsWith('PK', $excelContent);
    }

    public function test_clicking_customer_report_export_buttons_actually_triggers_downloads(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(CustomerSummaryReport::class)->callAction('exportPdf')->assertFileDownloaded();
        Livewire::test(CustomerSummaryReport::class)->callAction('exportExcel')->assertFileDownloaded();
    }

    public function test_sales_orders_by_month_report_page_loads_and_exports(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(SalesOrdersByMonthReport::class)->assertOk();

        $rows = app(SalesOrderMonthlyReportService::class)->monthlyBreakdown((int) now()->year);
        $response = app(SalesOrderMonthlyReportExportService::class)->toExcel($rows, (int) now()->year);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringStartsWith('PK', $content);
    }

    public function test_clicking_orders_by_month_export_buttons_actually_triggers_downloads(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(SalesOrdersByMonthReport::class)->callAction('exportPdf')->assertFileDownloaded();
        Livewire::test(SalesOrdersByMonthReport::class)->callAction('exportExcel')->assertFileDownloaded();
    }

    public function test_sales_representative_cannot_access_customer_summary_report(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();

        $this->actingAs($rep)->get('/admin/customer-summary-report')->assertForbidden();
    }
}

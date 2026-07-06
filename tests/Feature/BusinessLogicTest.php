<?php

namespace Tests\Feature;

use App\Models\CashbookEntry;
use App\Models\Handover;
use App\Models\IncentiveRule;
use App\Models\Product;
use App\Models\RepCollection;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\VoucherSequence;
use App\Services\DailyReportService;
use App\Services\HandoverService;
use App\Services\IncentiveCalculationService;
use App\Services\SalesOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_voucher_sequence_increments(): void
    {
        $first = VoucherSequence::next('inflow');
        $second = VoucherSequence::next('inflow');

        $this->assertSame('IN-000001', $first);
        $this->assertSame('IN-000002', $second);
    }

    public function test_handover_submit_and_confirm_creates_cashbook_entry(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $manager = User::where('email', 'manager@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $customer = \App\Models\Customer::create([
            'name' => 'Test Shop',
            'code' => 'CUST-TEST-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        RepCollection::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'entry_date' => now(),
            'mode' => 'cash',
            'amount' => 500,
            'status' => 'pending',
        ]);

        $handover = Handover::create([
            'sales_rep_id' => $rep->id,
            'warehouse_id' => $warehouse->id,
            'handover_date' => now(),
            'status' => 'draft',
        ]);

        app(HandoverService::class)->submit($handover);
        $handover->refresh();

        $this->assertSame('submitted', $handover->status);
        $this->assertSame('500.00', $handover->cash_total);

        app(HandoverService::class)->confirm($handover, $manager);
        $handover->refresh();

        $this->assertSame('confirmed', $handover->status);
        $this->assertSame(1, CashbookEntry::where('subtype', 'sales_rep_collection')->count());

        $collection = RepCollection::first();
        $this->assertSame('confirmed', $collection->status);
        $this->assertNotNull($collection->cashbook_entry_id);
    }

    public function test_incentive_calculation_applies_fixed_rule(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $manager = User::where('email', 'manager@cementco.test')->firstOrFail();
        $warehouse = $rep->warehouses()->firstOrFail();
        $product = Product::where('code', 'OPC43')->firstOrFail();

        $customer = \App\Models\Customer::create([
            'name' => 'Incentive Test Shop',
            'code' => 'CUST-INC-1',
            'type' => 'shop',
            'warehouse_id' => $warehouse->id,
        ]);

        $order = SalesOrder::create([
            'order_no' => 'SO-TEST-1',
            'order_date' => now(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'sales_rep_id' => $rep->id,
            'status' => 'draft',
            'created_by' => $manager->id,
        ]);
        $order->items()->create(['product_id' => $product->id, 'bag_count' => 100, 'rate_per_bag' => 10, 'discount_per_bag' => 0, 'line_total' => 1000]);
        app(SalesOrderService::class)->recalculateTotals($order);
        app(SalesOrderService::class)->confirm($order->fresh(), $manager);

        $rule = IncentiveRule::create([
            'name' => 'Fuel Allowance',
            'metric' => 'sales',
            'rule_type' => 'fixed',
            'min_target' => 500,
            'allowance_type' => 'fuel',
            'fixed_amount' => 20,
            'is_active' => true,
        ]);

        $records = app(IncentiveCalculationService::class)->calculateForDate(now(), $rep);

        $this->assertCount(1, $records);
        $record = $records->first();
        $this->assertSame($rule->id, $record->incentive_rule_id);
        $this->assertSame('20.00', $record->calculated_amount);
        $this->assertSame('pending', $record->status);
    }

    public function test_daily_report_service_computes_balances(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = \App\Models\Warehouse::first();
        $paymentMode = \App\Models\PaymentMode::where('code', 'cash')->firstOrFail();

        CashbookEntry::create([
            'voucher_no' => 'IN-TEST-1',
            'entry_date' => now(),
            'direction' => 'inflow',
            'subtype' => 'cash',
            'warehouse_id' => $warehouse->id,
            'amount' => 1000,
            'payment_mode_id' => $paymentMode->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        CashbookEntry::create([
            'voucher_no' => 'OUT-TEST-1',
            'entry_date' => now(),
            'direction' => 'outflow',
            'subtype' => 'cash',
            'warehouse_id' => $warehouse->id,
            'amount' => 300,
            'payment_mode_id' => $paymentMode->id,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $report = app(DailyReportService::class)->generate(now(), $warehouse->id);

        $this->assertSame('0.00', $report->opening_balance);
        $this->assertSame('1000.00', $report->total_inflows);
        $this->assertSame('300.00', $report->total_outflows);
        $this->assertSame('700.00', $report->closing_balance);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_panel_pages_load(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();

        $pages = [
            '/admin', '/admin/warehouses', '/admin/zones', '/admin/customers', '/admin/suppliers',
            '/admin/expense-categories', '/admin/payment-modes', '/admin/bank-accounts',
            '/admin/voucher-sequences', '/admin/users', '/admin/inflows', '/admin/outflows',
            '/admin/cheque-receiveds', '/admin/cheque-issueds',
            '/admin/rep-collections', '/admin/handovers', '/admin/bank-reconciliations',
            '/admin/daily-reports', '/admin/incentive-rules', '/admin/incentive-records',
            '/admin/products', '/admin/discount-rules', '/admin/goods-receipts',
            '/admin/stock-transfers', '/admin/warehouse-stocks', '/admin/sales-orders',
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_admin_panel_create_pages_load(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();

        $pages = [
            '/admin/warehouses/create', '/admin/inflows/create', '/admin/outflows/create',
            '/admin/cheque-receiveds/create', '/admin/cheque-issueds/create',
            '/admin/handovers/create', '/admin/incentive-rules/create',
            '/admin/products/create', '/admin/discount-rules/create', '/admin/goods-receipts/create',
            '/admin/stock-transfers/create', '/admin/sales-orders/create',
        ];

        foreach ($pages as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_rep_panel_pages_load(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();

        $pages = ['/rep', '/rep/sales-orders', '/rep/rep-collections', '/rep/handovers', '/rep/incentive-records'];

        foreach ($pages as $page) {
            $this->actingAs($rep, 'web')->get($page)->assertOk();
        }
    }

    public function test_rep_cannot_access_admin_panel(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();

        $this->actingAs($rep)->get('/admin')->assertForbidden();
    }

    public function test_inactive_user_cannot_access_any_panel(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $admin->update(['is_active' => false]);

        $this->actingAs($admin)->get('/admin')->assertForbidden();
    }
}

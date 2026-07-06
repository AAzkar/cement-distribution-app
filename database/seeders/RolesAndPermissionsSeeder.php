<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    protected array $modules = [
        'warehouses', 'zones', 'sales_rep_assignments', 'customers', 'suppliers',
        'expense_categories', 'payment_modes', 'bank_accounts', 'voucher_sequences', 'users',
        'cashbook', 'cheques_received', 'cheques_issued', 'rep_collections',
        'handovers', 'bank_reconciliations', 'daily_reports', 'incentive_rules',
        'incentive_records', 'activity_log', 'products', 'discount_rules', 'sales_orders',
        'goods_receipts', 'stock_transfers', 'warehouse_stocks',
    ];

    protected array $actions = ['view', 'create', 'edit', 'delete', 'approve'];

    public function run(): void
    {
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                Permission::firstOrCreate(['name' => "{$module}.{$action}", 'guard_name' => 'web']);
            }
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $accountant = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);
        $accountant->syncPermissions($this->perms([
            'warehouses' => ['view'],
            'zones' => ['view'],
            'customers' => ['view', 'create', 'edit'],
            'suppliers' => ['view', 'create', 'edit'],
            'expense_categories' => ['view', 'create', 'edit'],
            'payment_modes' => ['view', 'create', 'edit'],
            'bank_accounts' => ['view', 'create', 'edit'],
            'voucher_sequences' => ['view', 'create', 'edit'],
            'cashbook' => ['view', 'create', 'edit', 'approve'],
            'cheques_received' => ['view', 'create', 'edit', 'approve'],
            'cheques_issued' => ['view', 'create', 'edit', 'approve'],
            'rep_collections' => ['view', 'approve'],
            'handovers' => ['view', 'approve'],
            'bank_reconciliations' => ['view', 'create', 'edit', 'approve'],
            'daily_reports' => ['view', 'create', 'edit', 'approve'],
            'incentive_rules' => ['view', 'create', 'edit'],
            'incentive_records' => ['view', 'approve'],
            'activity_log' => ['view'],
            'products' => ['view', 'create', 'edit'],
            'discount_rules' => ['view', 'create', 'edit'],
            'sales_orders' => ['view', 'create', 'edit', 'delete', 'approve'],
            'goods_receipts' => ['view'],
            'stock_transfers' => ['view'],
            'warehouse_stocks' => ['view'],
        ]));

        $warehouseManager = Role::firstOrCreate(['name' => 'Warehouse Manager', 'guard_name' => 'web']);
        $warehouseManager->syncPermissions($this->perms([
            'warehouses' => ['view'],
            'zones' => ['view'],
            'customers' => ['view', 'create', 'edit'],
            'suppliers' => ['view'],
            'bank_accounts' => ['view'],
            'cashbook' => ['view', 'create', 'edit'],
            'cheques_received' => ['view', 'create', 'edit'],
            'cheques_issued' => ['view'],
            'rep_collections' => ['view', 'edit', 'approve'],
            'handovers' => ['view', 'edit', 'approve'],
            'daily_reports' => ['view', 'create', 'edit'],
            'incentive_records' => ['view'],
            'products' => ['view'],
            'discount_rules' => ['view'],
            'sales_orders' => ['view', 'create', 'edit', 'delete', 'approve'],
            'goods_receipts' => ['view', 'create', 'edit', 'approve'],
            'stock_transfers' => ['view', 'create', 'edit', 'approve'],
            'warehouse_stocks' => ['view'],
        ]));

        $salesRep = Role::firstOrCreate(['name' => 'Sales Representative', 'guard_name' => 'web']);
        $salesRep->syncPermissions($this->perms([
            'customers' => ['view'],
            'rep_collections' => ['view', 'create', 'edit'],
            'handovers' => ['view', 'create', 'edit'],
            'incentive_records' => ['view'],
            'products' => ['view'],
            'sales_orders' => ['view', 'create', 'edit'],
        ]));
    }

    protected function perms(array $map): array
    {
        $names = [];

        foreach ($map as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }
}

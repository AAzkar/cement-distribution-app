<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\SalesRepAssignment;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            MasterDataSeeder::class,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@cementco.test'],
            ['name' => 'System Admin', 'password' => 'password', 'is_active' => true]
        );
        $admin->assignRole('Admin');

        $accountant = User::firstOrCreate(
            ['email' => 'accountant@cementco.test'],
            ['name' => 'Asha Accountant', 'password' => 'password', 'is_active' => true]
        );
        $accountant->assignRole('Accountant');

        $warehouseA = Warehouse::where('code', 'WH-01')->first();
        $warehouseB = Warehouse::where('code', 'WH-02')->first();
        $zoneNorth = Zone::where('code', 'ZN-01')->first();
        $zoneSouth = Zone::where('code', 'ZN-02')->first();

        $manager = User::firstOrCreate(
            ['email' => 'manager@cementco.test'],
            ['name' => 'Wasim Manager', 'password' => 'password', 'is_active' => true]
        );
        $manager->assignRole('Warehouse Manager');
        SalesRepAssignment::firstOrCreate(['user_id' => $manager->id, 'warehouse_id' => $warehouseA->id, 'zone_id' => null]);

        $rep = User::firstOrCreate(
            ['email' => 'rep@cementco.test'],
            ['name' => 'Sam Sales Rep', 'password' => 'password', 'is_active' => true, 'employee_code' => 'REP-001']
        );
        $rep->assignRole('Sales Representative');
        SalesRepAssignment::firstOrCreate(['user_id' => $rep->id, 'warehouse_id' => $warehouseA->id, 'zone_id' => $zoneNorth->id]);

        $rep2 = User::firstOrCreate(
            ['email' => 'rep2@cementco.test'],
            ['name' => 'Priya Sales Rep', 'password' => 'password', 'is_active' => true, 'employee_code' => 'REP-002']
        );
        $rep2->assignRole('Sales Representative');
        SalesRepAssignment::firstOrCreate(['user_id' => $rep2->id, 'warehouse_id' => $warehouseB->id, 'zone_id' => $zoneSouth->id]);

        $stockService = app(StockService::class);

        foreach (Product::all() as $product) {
            foreach ([$warehouseA, $warehouseB] as $warehouse) {
                if ($stockService->quantityOnHand($warehouse, $product) === 0) {
                    $stockService->adjust(
                        warehouse: $warehouse,
                        product: $product,
                        delta: 1000,
                        type: 'adjustment_in',
                        user: $admin,
                        notes: 'Opening stock balance (seeded)',
                    );
                }
            }
        }
    }
}

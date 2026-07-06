<?php

namespace Database\Seeders;

use App\Models\DiscountRule;
use App\Models\ExpenseCategory;
use App\Models\PaymentMode;
use App\Models\Product;
use App\Models\VoucherSequence;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $warehouseA = Warehouse::firstOrCreate(['code' => 'WH-01'], ['name' => 'Main Warehouse', 'is_active' => true]);
        $warehouseB = Warehouse::firstOrCreate(['code' => 'WH-02'], ['name' => 'Secondary Warehouse', 'is_active' => true]);

        Zone::firstOrCreate(['code' => 'ZN-01'], ['name' => 'Zone North', 'is_active' => true]);
        Zone::firstOrCreate(['code' => 'ZN-02'], ['name' => 'Zone South', 'is_active' => true]);

        foreach ([
            ['name' => 'Cash', 'code' => 'cash'],
            ['name' => 'Cheque', 'code' => 'cheque'],
            ['name' => 'Bank Transfer', 'code' => 'bank_transfer'],
        ] as $mode) {
            PaymentMode::firstOrCreate(['code' => $mode['code']], ['name' => $mode['name'], 'is_active' => true]);
        }

        foreach ([
            ['name' => 'Fuel', 'code' => 'fuel'],
            ['name' => 'Transport', 'code' => 'transport'],
            ['name' => 'Labour', 'code' => 'labour'],
            ['name' => 'Office Supplies', 'code' => 'office_supplies'],
            ['name' => 'Miscellaneous', 'code' => 'misc'],
        ] as $category) {
            ExpenseCategory::firstOrCreate(['code' => $category['code']], ['name' => $category['name'], 'is_active' => true]);
        }

        foreach ([
            ['key' => 'inflow', 'prefix' => 'IN-', 'warehouse_id' => null],
            ['key' => 'outflow', 'prefix' => 'OUT-', 'warehouse_id' => null],
            ['key' => 'sales_order', 'prefix' => 'SO-', 'warehouse_id' => null],
            ['key' => 'goods_receipt', 'prefix' => 'GR-', 'warehouse_id' => null],
            ['key' => 'stock_transfer', 'prefix' => 'ST-', 'warehouse_id' => null],
        ] as $sequence) {
            VoucherSequence::firstOrCreate(['key' => $sequence['key']], [
                'prefix' => $sequence['prefix'],
                'padding' => 6,
                'next_number' => 1,
                'warehouse_id' => $sequence['warehouse_id'],
            ]);
        }

        $opc43 = Product::firstOrCreate(['code' => 'OPC43'], [
            'name' => 'OPC 43 Grade Cement',
            'unit_label' => '50kg Bag',
            'base_price_per_bag' => 8.50,
            'is_active' => true,
        ]);

        $opc53 = Product::firstOrCreate(['code' => 'OPC53'], [
            'name' => 'OPC 53 Grade Cement',
            'unit_label' => '50kg Bag',
            'base_price_per_bag' => 9.25,
            'is_active' => true,
        ]);

        $ppc = Product::firstOrCreate(['code' => 'PPC'], [
            'name' => 'PPC Cement',
            'unit_label' => '50kg Bag',
            'base_price_per_bag' => 8.00,
            'is_active' => true,
        ]);

        DiscountRule::firstOrCreate(['name' => 'Bulk 100+ Bags'], [
            'product_id' => null,
            'min_bags' => 100,
            'max_bags' => 499,
            'discount_type' => 'flat_per_bag',
            'discount_value' => 0.15,
            'is_active' => true,
        ]);

        DiscountRule::firstOrCreate(['name' => 'Bulk 500+ Bags'], [
            'product_id' => null,
            'min_bags' => 500,
            'max_bags' => null,
            'discount_type' => 'flat_per_bag',
            'discount_value' => 0.30,
            'is_active' => true,
        ]);

        DiscountRule::firstOrCreate(['name' => 'OPC 53 Volume Discount'], [
            'product_id' => $opc53->id,
            'min_bags' => 200,
            'max_bags' => null,
            'discount_type' => 'percentage',
            'discount_value' => 5,
            'is_active' => true,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\DailyReportResource\Pages\CreateDailyReport;
use App\Filament\Resources\DailyReportResource\Pages\ListDailyReports;
use App\Filament\Resources\DailyReportResource\Pages\ViewDailyReport;
use App\Models\DailyReport;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyReportResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_daily_report_action_works_with_warehouse(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $this->actingAs($admin);

        Livewire::test(ListDailyReports::class)
            ->callAction('generate', data: [
                'report_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('daily_reports', ['warehouse_id' => $warehouse->id]);
    }

    public function test_view_daily_report_page_loads(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $this->actingAs($admin);

        $report = DailyReport::create([
            'report_date' => now(),
            'warehouse_id' => $warehouse->id,
            'opening_balance' => 0,
            'total_inflows' => 100,
            'total_outflows' => 50,
            'closing_balance' => 50,
            'cheques_summary' => ['received' => ['count' => 1, 'amount' => 100]],
            'status' => 'draft',
        ]);

        Livewire::test(ViewDailyReport::class, ['record' => $report->getRouteKey()])
            ->assertOk();

        $this->get("/admin/daily-reports/{$report->id}")->assertOk();
    }

    public function test_create_daily_report_manually(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $this->actingAs($admin);

        Livewire::test(CreateDailyReport::class)
            ->fillForm([
                'report_date' => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'opening_balance' => 0,
                'total_inflows' => 0,
                'total_outflows' => 0,
                'closing_balance' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }
}

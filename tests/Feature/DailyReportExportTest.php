<?php

namespace Tests\Feature;

use App\Filament\Resources\DailyReportResource\Pages\ListDailyReports;
use App\Filament\Resources\DailyReportResource\Pages\ViewDailyReport;
use App\Models\CashbookEntry;
use App\Models\DailyReport;
use App\Models\PaymentMode;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DailyReportExportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected DailyReport $report;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        $warehouse = Warehouse::first();
        $paymentMode = PaymentMode::where('code', 'cash')->firstOrFail();

        CashbookEntry::create([
            'voucher_no' => 'IN-EXPORT-1',
            'entry_date' => now(),
            'direction' => 'inflow',
            'subtype' => 'cash',
            'warehouse_id' => $warehouse->id,
            'amount' => 750,
            'payment_mode_id' => $paymentMode->id,
            'reference' => 'INV-1',
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        CashbookEntry::create([
            'voucher_no' => 'OUT-EXPORT-1',
            'entry_date' => now(),
            'direction' => 'outflow',
            'subtype' => 'expense',
            'warehouse_id' => $warehouse->id,
            'amount' => 200,
            'payment_mode_id' => $paymentMode->id,
            'reference' => 'BILL-1',
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $this->report = app(\App\Services\DailyReportService::class)->generate(now(), $warehouse->id);

        $this->actingAs($admin);
    }

    public function test_pdf_export_generates_valid_pdf(): void
    {
        $response = app(DailyReportExportService::class)->toPdf($this->report);

        // Must be a StreamedResponse (or BinaryFileResponse) — Livewire's file-download
        // support only intercepts these two types; anything else gets JSON-encoded and
        // breaks on binary PDF bytes when triggered from a real Filament action button.
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_excel_export_generates_valid_xlsx(): void
    {
        $response = app(DailyReportExportService::class)->toExcel($this->report);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('PK', $content);
    }

    public function test_table_export_actions_are_reachable(): void
    {
        Livewire::test(ListDailyReports::class)
            ->assertTableActionExists('downloadPdf')
            ->assertTableActionExists('downloadExcel');
    }

    public function test_view_page_export_actions_are_reachable(): void
    {
        Livewire::test(ViewDailyReport::class, ['record' => $this->report->getRouteKey()])
            ->assertActionExists('downloadPdf')
            ->assertActionExists('downloadExcel');
    }

    public function test_clicking_download_pdf_action_actually_triggers_a_file_download(): void
    {
        // Regression test: exercises the real Livewire action-call path, which is what
        // broke in production (the service-level test alone didn't catch it because it
        // bypassed Livewire's JSON-encoding of the action's return value).
        Livewire::test(ViewDailyReport::class, ['record' => $this->report->getRouteKey()])
            ->callAction('downloadPdf')
            ->assertFileDownloaded();
    }

    public function test_clicking_download_excel_action_actually_triggers_a_file_download(): void
    {
        Livewire::test(ViewDailyReport::class, ['record' => $this->report->getRouteKey()])
            ->callAction('downloadExcel')
            ->assertFileDownloaded();
    }
}

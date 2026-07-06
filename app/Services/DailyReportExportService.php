<?php

namespace App\Services;

use App\Models\CashbookEntry;
use App\Models\DailyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyReportExportService
{
    public function toPdf(DailyReport $report): StreamedResponse
    {
        [$inflows, $outflows] = $this->voucherLists($report);
        $fileName = $this->fileName($report, 'pdf');

        $pdf = Pdf::loadView('pdf.daily-report', [
            'report' => $report,
            'inflows' => $inflows,
            'outflows' => $outflows,
        ]);

        return new StreamedResponse(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function toExcel(DailyReport $report): StreamedResponse
    {
        [$inflows, $outflows] = $this->voucherLists($report);
        $fileName = $this->fileName($report, 'xlsx');

        return new StreamedResponse(function () use ($report, $inflows, $outflows, $fileName) {
            $writer = new Writer;
            $writer->openToBrowser($fileName);

            $writer->addRow(Row::fromValues(['Daily Report']));
            $writer->addRow(Row::fromValues(['Date', $report->report_date->toDateString()]));
            $writer->addRow(Row::fromValues(['Warehouse', $report->warehouse?->name ?? 'Consolidated']));
            $writer->addRow(Row::fromValues(['Status', $report->status]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Opening Balance', (float) $report->opening_balance]));
            $writer->addRow(Row::fromValues(['Total Inflows', (float) $report->total_inflows]));
            $writer->addRow(Row::fromValues(['Total Outflows', (float) $report->total_outflows]));
            $writer->addRow(Row::fromValues(['Closing Balance', (float) $report->closing_balance]));
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValues(['Inflow Vouchers']));
            $writer->addRow(Row::fromValues(['Voucher No', 'Date', 'Subtype', 'Amount', 'Payment Mode', 'Reference']));
            foreach ($inflows as $entry) {
                $writer->addRow(Row::fromValues([
                    $entry->voucher_no,
                    $entry->entry_date->toDateString(),
                    $entry->subtype,
                    (float) $entry->amount,
                    $entry->paymentMode?->name,
                    $entry->reference,
                ]));
            }
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValues(['Outflow Vouchers']));
            $writer->addRow(Row::fromValues(['Voucher No', 'Date', 'Subtype', 'Amount', 'Payment Mode', 'Reference']));
            foreach ($outflows as $entry) {
                $writer->addRow(Row::fromValues([
                    $entry->voucher_no,
                    $entry->entry_date->toDateString(),
                    $entry->subtype,
                    (float) $entry->amount,
                    $entry->paymentMode?->name,
                    $entry->reference,
                ]));
            }
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValues(['Cheque Summary']));
            foreach ($report->cheques_summary ?? [] as $key => $value) {
                $writer->addRow(Row::fromValues([
                    ucwords(str_replace('_', ' ', $key)),
                    is_array($value) ? json_encode($value) : $value,
                ]));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    protected function voucherLists(DailyReport $report): array
    {
        $base = CashbookEntry::query()
            ->when($report->warehouse_id, fn ($q) => $q->where('warehouse_id', $report->warehouse_id))
            ->whereDate('entry_date', $report->report_date)
            ->whereIn('status', ['approved', 'locked'])
            ->with('paymentMode');

        $inflows = (clone $base)->where('direction', 'inflow')->get();
        $outflows = (clone $base)->where('direction', 'outflow')->get();

        return [$inflows, $outflows];
    }

    protected function fileName(DailyReport $report, string $extension): string
    {
        $scope = $report->warehouse?->code ?? 'consolidated';

        return "daily-report-{$report->report_date->toDateString()}-{$scope}.{$extension}";
    }
}

<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesRepPerformanceReportExportService
{
    public function __construct(protected SalesRepPerformanceReportService $reportService) {}

    public function toPdf(Collection $reps, CarbonInterface $from, CarbonInterface $to): StreamedResponse
    {
        $fileName = "sales-rep-performance-{$from->toDateString()}-to-{$to->toDateString()}.pdf";

        $pdf = Pdf::loadView('pdf.sales-rep-performance-report', [
            'reps' => $reps,
            'from' => $from,
            'to' => $to,
            'reportService' => $this->reportService,
        ]);

        return new StreamedResponse(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function toExcel(Collection $reps, CarbonInterface $from, CarbonInterface $to): StreamedResponse
    {
        $fileName = "sales-rep-performance-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";

        return new StreamedResponse(function () use ($reps, $from, $to, $fileName) {
            $writer = new Writer;
            $writer->openToBrowser($fileName);

            $writer->addRow(Row::fromValues([
                'Sales Rep', 'Orders Count', 'Orders Total', 'Bags Sold', 'Collections Total', 'Incentives Total',
            ]));

            foreach ($reps as $rep) {
                $writer->addRow(Row::fromValues([
                    $rep->name,
                    $rep->orders_count ?? 0,
                    (float) ($rep->orders_total ?? 0),
                    $this->reportService->bagsSold($rep, $from, $to),
                    (float) ($rep->collections_total ?? 0),
                    (float) ($rep->incentives_total ?? 0),
                ]));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

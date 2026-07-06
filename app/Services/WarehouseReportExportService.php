<?php

namespace App\Services;

use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseReportExportService
{
    public function __construct(protected WarehouseReportService $reportService) {}

    public function toPdf(Collection $warehouses, CarbonInterface $from, CarbonInterface $to): StreamedResponse
    {
        $fileName = "warehouse-summary-{$from->toDateString()}-to-{$to->toDateString()}.pdf";

        $pdf = Pdf::loadView('pdf.warehouse-summary-report', [
            'warehouses' => $warehouses,
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

    public function toExcel(Collection $warehouses, CarbonInterface $from, CarbonInterface $to): StreamedResponse
    {
        $fileName = "warehouse-summary-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";

        return new StreamedResponse(function () use ($warehouses, $from, $to, $fileName) {
            $writer = new Writer;
            $writer->openToBrowser($fileName);

            $writer->addRow(Row::fromValues([
                'Warehouse', 'Orders Count', 'Orders Total', 'Inflows', 'Outflows', 'Net Cash Flow',
                'Bags Sold', 'Stock On Hand',
            ]));

            foreach ($warehouses as $warehouse) {
                $writer->addRow(Row::fromValues([
                    $warehouse->name,
                    $warehouse->orders_count ?? 0,
                    (float) ($warehouse->orders_total ?? 0),
                    (float) ($warehouse->inflow_total ?? 0),
                    (float) ($warehouse->outflow_total ?? 0),
                    (float) ($warehouse->inflow_total ?? 0) - (float) ($warehouse->outflow_total ?? 0),
                    $this->reportService->bagsSold($warehouse, $from, $to),
                    (int) ($warehouse->stock_on_hand ?? 0),
                ]));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

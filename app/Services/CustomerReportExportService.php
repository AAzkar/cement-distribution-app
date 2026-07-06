<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerReportExportService
{
    public function toPdf(Collection $customers, CarbonInterface $from, CarbonInterface $to): StreamedResponse
    {
        $fileName = "customer-summary-{$from->toDateString()}-to-{$to->toDateString()}.pdf";

        $pdf = Pdf::loadView('pdf.customer-summary-report', [
            'customers' => $customers,
            'from' => $from,
            'to' => $to,
        ]);

        return new StreamedResponse(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function toExcel(Collection $customers, CarbonInterface $from, CarbonInterface $to): StreamedResponse
    {
        $fileName = "customer-summary-{$from->toDateString()}-to-{$to->toDateString()}.xlsx";

        return new StreamedResponse(function () use ($customers, $fileName) {
            $writer = new Writer;
            $writer->openToBrowser($fileName);

            $writer->addRow(Row::fromValues([
                'Customer', 'Code', 'Zone', 'Warehouse', 'Orders Count', 'Orders Total',
                'Collections Total', 'Outstanding Balance',
            ]));

            foreach ($customers as $customer) {
                $writer->addRow(Row::fromValues([
                    $customer->name,
                    $customer->code,
                    $customer->zone?->name,
                    $customer->warehouse?->name,
                    $customer->orders_count ?? 0,
                    (float) ($customer->orders_total ?? 0),
                    (float) ($customer->cash_collections_total ?? 0) + (float) ($customer->cheque_collections_total ?? 0),
                    $customer->outstandingBalance(),
                ]));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

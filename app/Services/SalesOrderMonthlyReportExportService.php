<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesOrderMonthlyReportExportService
{
    public function toPdf(Collection $rows, int $year): StreamedResponse
    {
        $fileName = "sales-orders-by-month-{$year}.pdf";

        $pdf = Pdf::loadView('pdf.sales-orders-by-month-report', [
            'rows' => $rows,
            'year' => $year,
        ]);

        return new StreamedResponse(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function toExcel(Collection $rows, int $year): StreamedResponse
    {
        $fileName = "sales-orders-by-month-{$year}.xlsx";

        return new StreamedResponse(function () use ($rows, $fileName) {
            $writer = new Writer;
            $writer->openToBrowser($fileName);

            $writer->addRow(Row::fromValues(['Month', 'Orders Count', 'Total Bags', 'Total Amount']));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues([
                    $row['month_name'],
                    $row['orders_count'],
                    $row['total_bags'],
                    $row['total_amount'],
                ]));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

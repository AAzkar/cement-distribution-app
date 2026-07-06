<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesOrderReceiptService
{
    public function toPdf(SalesOrder $order): StreamedResponse
    {
        $fileName = "order-{$order->order_no}.pdf";

        $pdf = Pdf::loadView('pdf.sales-order-receipt', [
            'order' => $order->load(['items.product', 'customer', 'warehouse', 'salesRep']),
            'settings' => AppSetting::current(),
        ]);

        return new StreamedResponse(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}

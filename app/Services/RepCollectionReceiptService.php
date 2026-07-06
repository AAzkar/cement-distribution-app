<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\RepCollection;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RepCollectionReceiptService
{
    public function toPdf(RepCollection $collection): StreamedResponse
    {
        $fileName = "collection-receipt-{$collection->id}.pdf";

        $pdf = Pdf::loadView('pdf.rep-collection-receipt', [
            'collection' => $collection->load(['customer', 'salesRep', 'warehouse', 'chequeReceived']),
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

<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\CashbookEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashbookVoucherReceiptService
{
    public function toPdf(CashbookEntry $entry): StreamedResponse
    {
        $fileName = "voucher-{$entry->voucher_no}.pdf";

        $pdf = Pdf::loadView('pdf.cashbook-voucher-receipt', [
            'entry' => $entry->load(['warehouse', 'zone', 'paymentMode', 'expenseCategory', 'customer', 'supplier', 'salesRep', 'bankAccount', 'createdBy', 'approvedBy']),
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

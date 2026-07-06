<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        .receipt { max-width: 380px; margin: 0 auto; border: 1px solid #d1d5db; padding: 20px; }
        .center { text-align: center; }
        .brand img { max-height: 50px; margin-bottom: 6px; }
        .brand-name { font-size: 15px; font-weight: bold; }
        .brand-tagline { color: #6b7280; font-size: 10px; margin-bottom: 12px; }
        hr { border: none; border-top: 1px dashed #9ca3af; margin: 12px 0; }
        table.detail { width: 100%; border-collapse: collapse; }
        table.detail td { padding: 4px 0; font-size: 12px; }
        table.detail td.label { color: #6b7280; }
        table.detail td.value { text-align: right; font-weight: bold; }
        .amount-box { text-align: center; margin: 16px 0; padding: 12px; border: 2px solid #1f2937; border-radius: 6px; }
        .amount-box .amount { font-size: 22px; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 4px; background: #f3f4f6; font-size: 11px; }
        .footer-note { font-size: 10px; color: #6b7280; margin-top: 16px; text-align: center; }
        .sig-line { border-top: 1px solid #9ca3af; width: 160px; margin: 40px auto 0; padding-top: 4px; font-size: 10px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center brand">
            @if ($settings->logoUrl())
                <img src="{{ $settings->logoUrl() }}" alt="Logo">
            @endif
            <div class="brand-name">{{ $settings->company_name }}</div>
            @if ($settings->tagline)
                <div class="brand-tagline">{{ $settings->tagline }}</div>
            @endif
        </div>

        <div class="center"><strong>COLLECTION RECEIPT</strong></div>
        <hr>

        <table class="detail">
            <tr><td class="label">Receipt No</td><td class="value">COL-{{ str_pad($collection->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
            <tr><td class="label">Date</td><td class="value">{{ $collection->entry_date->toFormattedDateString() }}</td></tr>
            <tr><td class="label">Customer</td><td class="value">{{ $collection->customer?->name ?? '—' }}</td></tr>
            <tr><td class="label">Collected By</td><td class="value">{{ $collection->salesRep?->name }}</td></tr>
            <tr><td class="label">Warehouse</td><td class="value">{{ $collection->warehouse->name }}</td></tr>
            <tr><td class="label">Mode</td><td class="value"><span class="badge">{{ ucfirst(str_replace('_', ' ', $collection->mode)) }}</span></td></tr>
            @if ($collection->mode === 'cheque' && $collection->chequeReceived)
                <tr><td class="label">Cheque No</td><td class="value">{{ $collection->chequeReceived->cheque_no }}</td></tr>
                <tr><td class="label">Bank</td><td class="value">{{ $collection->chequeReceived->bank_name }}</td></tr>
            @elseif ($collection->reference)
                <tr><td class="label">Reference</td><td class="value">{{ $collection->reference }}</td></tr>
            @endif
            <tr><td class="label">Status</td><td class="value">{{ ucfirst(str_replace('_', ' ', $collection->status)) }}</td></tr>
        </table>

        <div class="amount-box">
            <div>Amount Received</div>
            <div class="amount">LKR {{ number_format($collection->amount, 2) }}</div>
        </div>

        <div class="sig-line">Customer Signature</div>

        <div class="footer-note">Thank you for your payment. Please retain this receipt for your records.</div>
    </div>
</body>
</html>

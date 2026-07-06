<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        .header { display: table; width: 100%; margin-bottom: 20px; }
        .header .brand { display: table-cell; vertical-align: top; }
        .header .brand img { max-height: 60px; margin-bottom: 6px; }
        .header .brand-name { font-size: 16px; font-weight: bold; }
        .header .voucher-meta { display: table-cell; vertical-align: top; text-align: right; }
        .header .voucher-meta h1 { font-size: 18px; margin: 0 0 6px; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 4px; background: #f3f4f6; font-size: 11px; }
        .direction-inflow { background: #e6f4ea; color: #0ca30c; }
        .direction-outflow { background: #fdeaea; color: #d03b3b; }
        table.detail { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.detail td { border: 1px solid #e5e7eb; padding: 8px 10px; }
        table.detail td.label { width: 180px; font-weight: bold; background: #f9fafb; }
        .amount-box { text-align: center; margin: 24px 0; padding: 16px; border: 2px solid #1f2937; border-radius: 6px; }
        .amount-box .amount { font-size: 26px; font-weight: bold; }
        .signatures { display: table; width: 100%; margin-top: 60px; }
        .signatures .sig { display: table-cell; width: 50%; }
        .sig .line { border-top: 1px solid #9ca3af; width: 200px; margin-top: 40px; padding-top: 4px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            @if ($settings->logoUrl())
                <img src="{{ $settings->logoUrl() }}" alt="Logo">
            @endif
            <div class="brand-name">{{ $settings->company_name }}</div>
        </div>
        <div class="voucher-meta">
            <h1>{{ $entry->direction === 'inflow' ? 'Receipt Voucher' : 'Payment Voucher' }}</h1>
            <div>Voucher No: <strong>{{ $entry->voucher_no }}</strong></div>
            <div>Date: {{ $entry->entry_date->toFormattedDateString() }}</div>
            <div>
                <span class="badge {{ $entry->direction === 'inflow' ? 'direction-inflow' : 'direction-outflow' }}">
                    {{ $entry->direction === 'inflow' ? 'Money In' : 'Money Out' }}
                </span>
                <span class="badge">{{ ucfirst($entry->status) }}</span>
            </div>
        </div>
    </div>

    <div class="amount-box">
        <div>Amount</div>
        <div class="amount">LKR {{ number_format($entry->amount, 2) }}</div>
    </div>

    <table class="detail">
        <tr>
            <td class="label">Type</td>
            <td>{{ ucwords(str_replace('_', ' ', $entry->subtype)) }}</td>
        </tr>
        <tr>
            <td class="label">Warehouse</td>
            <td>{{ $entry->warehouse->name }}{{ $entry->zone ? ' — '.$entry->zone->name : '' }}</td>
        </tr>
        <tr>
            <td class="label">Payment Mode</td>
            <td>{{ $entry->paymentMode->name }}</td>
        </tr>
        @if ($entry->customer)
            <tr><td class="label">Customer</td><td>{{ $entry->customer->name }}</td></tr>
        @endif
        @if ($entry->supplier)
            <tr><td class="label">Supplier / Payee</td><td>{{ $entry->supplier->name }}</td></tr>
        @endif
        @if ($entry->salesRep)
            <tr><td class="label">Sales Rep</td><td>{{ $entry->salesRep->name }}</td></tr>
        @endif
        @if ($entry->expenseCategory)
            <tr><td class="label">Expense Category</td><td>{{ $entry->expenseCategory->name }}</td></tr>
        @endif
        @if ($entry->bankAccount)
            <tr><td class="label">Bank Account</td><td>{{ $entry->bankAccount->account_name }} — {{ $entry->bankAccount->bank_name }}</td></tr>
        @endif
        @if ($entry->reference)
            <tr><td class="label">Reference</td><td>{{ $entry->reference }}</td></tr>
        @endif
        @if ($entry->description)
            <tr><td class="label">Description</td><td>{{ $entry->description }}</td></tr>
        @endif
        <tr>
            <td class="label">Prepared By</td>
            <td>{{ $entry->createdBy?->name }}</td>
        </tr>
        @if ($entry->approvedBy)
            <tr><td class="label">Approved By</td><td>{{ $entry->approvedBy->name }} on {{ $entry->approved_at?->toFormattedDateString() }}</td></tr>
        @endif
    </table>

    <div class="signatures">
        <div class="sig"><div class="line">Received / Paid By</div></div>
        <div class="sig"><div class="line">Authorized Signature</div></div>
    </div>
</body>
</html>

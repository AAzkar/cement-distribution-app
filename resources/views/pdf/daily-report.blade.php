<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .summary-table td:first-child { font-weight: bold; width: 200px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 16px 0 8px; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Daily Cashbook Report</h1>
    <div class="meta">
        {{ $report->report_date->toFormattedDateString() }}
        &mdash; {{ $report->warehouse?->name ?? 'Consolidated (All Warehouses)' }}
        &mdash; Status: <span class="badge">{{ ucfirst($report->status) }}</span>
    </div>

    <table class="summary-table">
        <tr><td>Opening Balance</td><td>LKR {{ number_format($report->opening_balance, 2) }}</td></tr>
        <tr><td>Total Inflows</td><td>LKR {{ number_format($report->total_inflows, 2) }}</td></tr>
        <tr><td>Total Outflows</td><td>LKR {{ number_format($report->total_outflows, 2) }}</td></tr>
        <tr><td>Closing Balance</td><td>LKR {{ number_format($report->closing_balance, 2) }}</td></tr>
    </table>

    <div class="section-title">Inflow Vouchers</div>
    <table>
        <thead>
            <tr><th>Voucher No</th><th>Subtype</th><th>Payment Mode</th><th>Reference</th><th class="text-right">Amount</th></tr>
        </thead>
        <tbody>
            @forelse ($inflows as $entry)
                <tr>
                    <td>{{ $entry->voucher_no }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $entry->subtype)) }}</td>
                    <td>{{ $entry->paymentMode?->name }}</td>
                    <td>{{ $entry->reference }}</td>
                    <td class="text-right">LKR {{ number_format($entry->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No inflow vouchers for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Outflow Vouchers</div>
    <table>
        <thead>
            <tr><th>Voucher No</th><th>Subtype</th><th>Payment Mode</th><th>Reference</th><th class="text-right">Amount</th></tr>
        </thead>
        <tbody>
            @forelse ($outflows as $entry)
                <tr>
                    <td>{{ $entry->voucher_no }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $entry->subtype)) }}</td>
                    <td>{{ $entry->paymentMode?->name }}</td>
                    <td>{{ $entry->reference }}</td>
                    <td class="text-right">LKR {{ number_format($entry->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No outflow vouchers for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($report->cheques_summary)
        <div class="section-title">Cheque Summary</div>
        <table>
            <tr>
                <th>Received</th>
                <td>{{ $report->cheques_summary['received']['count'] ?? 0 }} cheque(s) &mdash; LKR {{ number_format($report->cheques_summary['received']['amount'] ?? 0, 2) }}</td>
            </tr>
            <tr><th>Deposited</th><td>{{ $report->cheques_summary['deposited'] ?? 0 }}</td></tr>
            <tr><th>Cleared</th><td>{{ $report->cheques_summary['cleared'] ?? 0 }}</td></tr>
            <tr><th>Returned</th><td>{{ $report->cheques_summary['returned'] ?? 0 }}</td></tr>
            <tr>
                <th>Issued</th>
                <td>{{ $report->cheques_summary['issued']['count'] ?? 0 }} cheque(s) &mdash; LKR {{ number_format($report->cheques_summary['issued']['amount'] ?? 0, 2) }}</td>
            </tr>
            <tr><th>Issued &amp; Cleared</th><td>{{ $report->cheques_summary['issued_cleared'] ?? 0 }}</td></tr>
            <tr><th>Issued &amp; Bounced</th><td>{{ $report->cheques_summary['issued_bounced'] ?? 0 }}</td></tr>
        </table>
    @endif
</body>
</html>

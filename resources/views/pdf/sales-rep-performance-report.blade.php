<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { color: #6b7280; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Sales Rep Performance Report</h1>
    <div class="meta">{{ $from->toFormattedDateString() }} &ndash; {{ $to->toFormattedDateString() }}</div>

    <table>
        <thead>
            <tr>
                <th>Sales Rep</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Orders Total</th>
                <th class="text-right">Bags Sold</th>
                <th class="text-right">Collections</th>
                <th class="text-right">Incentives</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reps as $rep)
                <tr>
                    <td>{{ $rep->name }}</td>
                    <td class="text-right">{{ $rep->orders_count ?? 0 }}</td>
                    <td class="text-right">LKR {{ number_format($rep->orders_total ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($reportService->bagsSold($rep, $from, $to)) }}</td>
                    <td class="text-right">LKR {{ number_format($rep->collections_total ?? 0, 2) }}</td>
                    <td class="text-right">LKR {{ number_format($rep->incentives_total ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No sales representatives found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

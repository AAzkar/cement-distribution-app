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
        tfoot td { font-weight: bold; background-color: #f9fafb; }
    </style>
</head>
<body>
    <h1>Warehouse Summary Report</h1>
    <div class="meta">{{ $from->toFormattedDateString() }} &ndash; {{ $to->toFormattedDateString() }}</div>

    <table>
        <thead>
            <tr>
                <th>Warehouse</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Orders Total</th>
                <th class="text-right">Inflows</th>
                <th class="text-right">Outflows</th>
                <th class="text-right">Net Cash Flow</th>
                <th class="text-right">Bags Sold</th>
                <th class="text-right">Stock On Hand</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($warehouses as $warehouse)
                @php
                    $inflow = (float) ($warehouse->inflow_total ?? 0);
                    $outflow = (float) ($warehouse->outflow_total ?? 0);
                @endphp
                <tr>
                    <td>{{ $warehouse->name }}</td>
                    <td class="text-right">{{ $warehouse->orders_count ?? 0 }}</td>
                    <td class="text-right">LKR {{ number_format($warehouse->orders_total ?? 0, 2) }}</td>
                    <td class="text-right">LKR {{ number_format($inflow, 2) }}</td>
                    <td class="text-right">LKR {{ number_format($outflow, 2) }}</td>
                    <td class="text-right">LKR {{ number_format($inflow - $outflow, 2) }}</td>
                    <td class="text-right">{{ number_format($reportService->bagsSold($warehouse, $from, $to)) }}</td>
                    <td class="text-right">{{ number_format($warehouse->stock_on_hand ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

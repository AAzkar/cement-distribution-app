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
    <h1>Sales Orders by Month</h1>
    <div class="meta">Year {{ $year }}</div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Total Bags</th>
                <th class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['month_name'] }}</td>
                    <td class="text-right">{{ $row['orders_count'] }}</td>
                    <td class="text-right">{{ number_format($row['total_bags']) }}</td>
                    <td class="text-right">LKR {{ number_format($row['total_amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="text-right">{{ $rows->sum('orders_count') }}</td>
                <td class="text-right">{{ number_format($rows->sum('total_bags')) }}</td>
                <td class="text-right">LKR {{ number_format($rows->sum('total_amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

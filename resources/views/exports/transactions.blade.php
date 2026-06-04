<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Transactions</title>
<style>
    body { font-family: sans-serif; font-size: 11px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #f5f5f5; font-weight: 600; font-size: 10px; text-transform: uppercase; }
    h1 { font-size: 18px; margin: 0; }
    .meta { color: #666; font-size: 10px; margin-top: 4px; }
</style></head>
<body>
    <h1>Transactions Report</h1>
    <p class="meta">Generated {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead><tr><th>Type</th><th>Number</th><th>Client</th><th>Service</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
            @foreach($txns as $t)
            <tr>
                <td>{{ $t['type'] }}</td>
                <td>{{ $t['number'] }}</td>
                <td>{{ $t['client'] }}</td>
                <td>{{ $t['service'] }}</td>
                <td>UGX {{ number_format($t['amount'], 2) }}</td>
                <td>{{ $t['status'] }}</td>
                <td>{{ $t['date']->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body></html>

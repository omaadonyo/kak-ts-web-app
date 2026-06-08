<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipts</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        .amount { text-align: right; font-weight: 600; }
        .status { font-size: 10px; }
        h1 { font-size: 18px; margin: 0; }
        .meta { color: #6b7280; font-size: 11px; margin-top: 4px; }
    </style>
</head>
<body>
    <h1>Receipts</h1>
    <p class="meta">Generated {{ now()->format('M d, Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>Receipt #</th>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Service</th>
                <th class="amount">Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipts as $r)
                <tr>
                    <td>{{ $r->receipt_number ?? 'N/A' }}</td>
                    <td>{{ $r->invoice->invoice_number }}</td>
                    <td>{{ $r->invoice->bookService->user->name }}</td>
                    <td>{{ $r->invoice->bookService->service_type }}</td>
                    <td class="amount">UGX {{ number_format($r->amount, 2) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $r->method)) }}</td>
                    <td>{{ $r->reference ?? 'N/A' }}</td>
                    <td class="status">{{ ucfirst($r->status) }}</td>
                    <td>{{ ($r->paid_at ?? $r->created_at)->format('Y-m-d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

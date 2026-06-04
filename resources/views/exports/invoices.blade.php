<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Invoices</title>
<style>
    body { font-family: sans-serif; font-size: 11px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #f5f5f5; font-weight: 600; font-size: 10px; text-transform: uppercase; }
    h1 { font-size: 18px; margin: 0; }
    .meta { color: #666; font-size: 10px; margin-top: 4px; }
</style></head>
<body>
    <h1>Invoices Report</h1>
    <p class="meta">Generated {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead><tr><th>ID</th><th>Invoice #</th><th>Client</th><th>Service</th><th>Location</th><th>Total</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
            @foreach($invoices as $i)
            <tr>
                <td>{{ $i->id }}</td>
                <td>{{ $i->invoice_number }}</td>
                <td>{{ $i->bookService->user->name }}</td>
                <td>{{ $i->bookService->service_type }}</td>
                <td>{{ $i->bookService->location }}</td>
                <td>UGX {{ number_format($i->total, 2) }}</td>
                <td>{{ $i->status }}</td>
                <td>{{ $i->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body></html>

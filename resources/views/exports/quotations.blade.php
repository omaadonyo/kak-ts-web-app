<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Quotations</title>
<style>
    body { font-family: sans-serif; font-size: 11px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #f5f5f5; font-weight: 600; font-size: 10px; text-transform: uppercase; }
    h1 { font-size: 18px; margin: 0; }
    .meta { color: #666; font-size: 10px; margin-top: 4px; }
</style></head>
<body>
    <h1>Quotations Report</h1>
    <p class="meta">Generated {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead><tr><th>ID</th><th>Client</th><th>Service</th><th>Location</th><th>Total</th><th>Status</th><th>Valid Until</th><th>Created</th></tr></thead>
        <tbody>
            @foreach($quotations as $q)
            <tr>
                <td>{{ $q->id }}</td>
                <td>{{ $q->bookService->user->name }}</td>
                <td>{{ $q->bookService->service_type }}</td>
                <td>{{ $q->bookService->location }}</td>
                <td>${{ number_format($q->total, 2) }}</td>
                <td>{{ $q->status }}</td>
                <td>{{ $q->valid_until?->format('Y-m-d') ?? '-' }}</td>
                <td>{{ $q->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body></html>

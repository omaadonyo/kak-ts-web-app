<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Users</title>
<style>
    body { font-family: sans-serif; font-size: 11px; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
    th { background: #f5f5f5; font-weight: 600; font-size: 10px; text-transform: uppercase; }
    h1 { font-size: 18px; margin: 0; }
    .meta { color: #666; font-size: 10px; margin-top: 4px; }
</style></head>
<body>
    <h1>Users Report</h1>
    <p class="meta">Generated {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Client Type</th><th>Created</th></tr></thead>
        <tbody>
            @foreach($users as $u)
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->phone ?? '-' }}</td>
                <td>{{ $u->role }}</td>
                <td>{{ $u->client_type ?? '-' }}</td>
                <td>{{ $u->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body></html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Project Report - {{ $bs->service_type }}</title>
    <style>
        @page { margin: 40px 50px; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1f2937; line-height: 1.5; }
        .header { text-align: center; border-bottom: 3px solid #1f2937; padding-bottom: 20px; margin-bottom: 25px; }
        .header h1 { font-size: 22px; margin: 0 0 4px; color: #111827; }
        .header .company { font-size: 12px; color: #6b7280; }
        .header .company strong { color: #374151; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 10px; }
        table.details { width: 100%; border-collapse: collapse; }
        table.details td { padding: 4px 8px; vertical-align: top; }
        table.details td:first-child { width: 140px; color: #6b7280; font-weight: 600; }
        table.details td:last-child { color: #1f2937; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th { background: #f3f4f6; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        table.items td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
        table.items .amount { text-align: right; font-weight: 600; }
        table.items .total-row td { border-top: 2px solid #1f2937; font-weight: 700; font-size: 12px; padding-top: 8px; }
        .progress-bar { background: #e5e7eb; height: 16px; border-radius: 8px; overflow: hidden; margin: 6px 0; }
        .progress-fill { height: 100%; background: #1f2937; border-radius: 8px; }
        .milestones { margin-top: 8px; }
        .milestone { padding: 4px 0; display: flex; align-items: center; gap: 8px; }
        .milestone-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .milestone-dot.completed { background: #059669; }
        .milestone-dot.pending { background: #d1d5db; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-sent { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-in_progress { background: #dbeafe; color: #1e40af; }
        .footer { text-align: center; color: #9ca3af; font-size: 9px; border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: 30px; }
        .footer strong { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company['name'] }}</h1>
        <div class="company">
            <strong>{{ $company['email'] }}</strong> &middot; {{ $company['phone'] }}
        </div>
        <div style="margin-top:6px; font-size:10px; color:#9ca3af;">Project Report &mdash; {{ now()->format('F d, Y') }}</div>
    </div>

    <div class="section">
        <h2>Service Details</h2>
        <table class="details">
            <tr><td>Service Type</td><td>{{ ucfirst($bs->service_type) }}</td></tr>
            <tr><td>Location</td><td>{{ $bs->location }}</td></tr>
            <tr><td>Status</td><td><span class="badge badge-{{ $bs->status }}">{{ $bs->status }}</span></td></tr>
            <tr><td>Created</td><td>{{ $bs->created_at->format('F d, Y') }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Client Information</h2>
        <table class="details">
            <tr><td>Name</td><td>{{ $bs->user->name }}</td></tr>
            <tr><td>Email</td><td>{{ $bs->user->email }}</td></tr>
            <tr><td>Phone</td><td>{{ $bs->user->phone ?? 'N/A' }}</td></tr>
            @if($bs->assignedTo)
            <tr><td>Technician</td><td>{{ $bs->assignedTo->name }}</td></tr>
            @endif
        </table>
    </div>

    @if($bs->assessment)
    <div class="section">
        <h2>Assessment</h2>
        <p style="margin:0 0 4px;"><strong>By:</strong> {{ $bs->assessment->assessedBy->name ?? 'N/A' }}</p>
        <p style="margin:0; color:#4b5563;">{{ $bs->assessment->findings }}</p>
    </div>
    @endif

    @if($bs->quotation)
    <div class="section">
        <h2>Quotation</h2>
        <table class="items">
            <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($bs->quotation->line_items as $item)
                <tr>
                    <td>{{ $item['description'] ?? 'Item' }}</td>
                    <td>{{ $item['quantity'] ?? 1 }}</td>
                    <td class="amount">UGX {{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                    <td class="amount">UGX {{ number_format($item['total'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
                <tr><td colspan="3" style="text-align:right; padding-right:12px; color:#6b7280;">Subtotal</td><td class="amount">UGX {{ number_format($bs->quotation->subtotal, 2) }}</td></tr>
                <tr><td colspan="3" style="text-align:right; padding-right:12px; color:#6b7280;">Tax</td><td class="amount">UGX {{ number_format($bs->quotation->tax, 2) }}</td></tr>
                <tr class="total-row"><td colspan="3" style="text-align:right; padding-right:12px;">Total</td><td class="amount">UGX {{ number_format($bs->quotation->total, 2) }}</td></tr>
            </tbody>
        </table>
    </div>
    @endif

    @if($bs->project)
    <div class="section">
        <h2>Project Progress</h2>
        <div style="display:flex; align-items:center; gap:12px;">
            <div class="progress-bar" style="flex:1;"><div class="progress-fill" style="width:{{ $bs->project->progress }}%;"></div></div>
            <span style="font-size:18px; font-weight:700;">{{ $bs->project->progress }}%</span>
        </div>
        @if($bs->project->milestones->count() > 0)
        <div class="milestones">
            <p style="font-weight:600; margin:10px 0 4px; font-size:10px; color:#6b7280;">MILESTONES</p>
            @foreach($bs->project->milestones as $m)
            <div class="milestone">
                <span class="milestone-dot {{ $m->status === 'completed' ? 'completed' : 'pending' }}"></span>
                <span>{{ $m->name }}</span>
                @if($m->due_date)<span style="color:#9ca3af; font-size:10px; margin-left:auto;">Due {{ $m->due_date->format('M d, Y') }}</span>@endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    @if($bs->invoice)
    <div class="section">
        <h2>Invoice</h2>
        <table class="details">
            <tr><td>Invoice #</td><td>{{ $bs->invoice->invoice_number }}</td></tr>
            <tr><td>Status</td><td><span class="badge badge-{{ $bs->invoice->status }}">{{ $bs->invoice->status }}</span></td></tr>
            <tr><td>Total</td><td><strong>UGX {{ number_format($bs->invoice->total, 2) }}</strong></td></tr>
        </table>
    </div>
    @endif

    @if($bs->notes)
    <div class="section">
        <h2>Notes</h2>
        <p style="margin:0; color:#4b5563;">{{ $bs->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <strong>{{ $company['name'] }}</strong> &middot; {{ $company['email'] }} &middot; {{ $company['phone'] }}<br>
        Generated on {{ now()->format('F d, Y \a\t h:i A') }}
    </div>
</body>
</html>

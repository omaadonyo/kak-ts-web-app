<?php

use App\Models\Invoice;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Transactions')] class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 15;
    public string $typeFilter = '';

    protected $queryString = ['search', 'perPage', 'typeFilter'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function transactions()
    {
        $items = collect();
        $user = Auth::user();
        $ids = [$user->id];
        if ($user->isCompany()) $ids = array_merge($ids, $user->companyUsers()->pluck('id')->toArray());

        $invQuery = Invoice::with('bookService.user')
            ->when(!$user->isAdmin(), fn($q) => $q->whereHas('bookService', fn($q) => $q->whereIn('user_id', $ids)));

        $quoQuery = Quotation::with('bookService.user')
            ->when(!$user->isAdmin(), fn($q) => $q->whereHas('bookService', fn($q) => $q->whereIn('user_id', $ids)));

        if ($this->typeFilter === 'invoice') $quoQuery->whereRaw('1=0');
        if ($this->typeFilter === 'quotation') $invQuery->whereRaw('1=0');
        if ($this->typeFilter === 'receipt') $invQuery->where('status', 'paid');
        elseif ($this->typeFilter && $this->typeFilter !== 'all') $invQuery->whereRaw('1=0');

        if ($this->search) {
            $s = $this->search;
            $invQuery->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                  ->orWhereHas('bookService', fn($q) => $q->where('service_type', 'like', "%{$s}%")->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%")));
            });
            $quoQuery->where(function ($q) use ($s) {
                $q->whereHas('bookService', fn($q) => $q->where('service_type', 'like', "%{$s}%")->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%")));
            });
        }

        $invoices = $invQuery->latest()->get()->map(fn($i) => [
            'type' => $i->status === 'paid' ? 'Receipt' : 'Invoice',
            'id' => $i->id,
            'number' => $i->invoice_number,
            'amount' => $i->total,
            'status' => $i->status,
            'client' => $i->bookService?->user?->name ?? 'N/A',
            'service' => $i->bookService?->service_type ?? 'N/A',
            'date' => $i->created_at,
            'route' => route('invoices.show', $i->bookService?->id ?? 0),
        ]);

        $quotations = $quoQuery->latest()->get()->map(fn($q) => [
            'type' => 'Quotation',
            'id' => $q->id,
            'number' => 'Q-' . str_pad($q->id, 5, '0', STR_PAD_LEFT),
            'amount' => $q->total,
            'status' => $q->status,
            'client' => $q->bookService?->user?->name ?? 'N/A',
            'service' => $q->bookService?->service_type ?? 'N/A',
            'date' => $q->created_at,
            'route' => route('quotations.show', $q->bookService?->id ?? 0),
        ]);

        return $items->concat($invoices)->concat($quotations)->sortByDesc('date');
    }

    public function exportCsv()
    {
        $txns = $this->transactions;
        $headers = ['Type', 'Number', 'Client', 'Service', 'Amount', 'Status', 'Date'];
        $rows = $txns->map(fn($t) => [$t['type'], $t['number'], $t['client'], $t['service'], $t['amount'], $t['status'], $t['date']->format('Y-m-d')]);
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($rows as $row) fputcsv($csv, $row);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        return response()->streamDownload(fn() => print $content, 'transactions.csv');
    }

    public function exportPdf()
    {
        $txns = $this->transactions;
        $pdf = Pdf::loadView('exports.transactions', compact('txns'));
        return response()->streamDownload(fn() => print $pdf->output(), 'transactions.pdf');
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Transactions</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">All invoices, receipts, and quotations in one place.</p>
        </div>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" wire:model.live.debounce="search" placeholder="Search transactions..."
                           class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20 focus:border-zinc-900 dark:focus:border-zinc-400">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select wire:model.live="typeFilter" class="border border-zinc-200 dark:border-zinc-600 rounded-xl px-3 py-2.5 text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800">
                        <option value="">All Types</option>
                        <option value="invoice">Invoices</option>
                        <option value="receipt">Receipts</option>
                        <option value="quotation">Quotations</option>
                    </select>
                    <select wire:model.live="perPage" class="border border-zinc-200 dark:border-zinc-600 rounded-xl px-3 py-2.5 text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800">
                        <option value="15">15/page</option>
                        <option value="25">25/page</option>
                        <option value="50">50/page</option>
                        <option value="100">100/page</option>
                    </select>
                    <button wire:click="exportCsv" class="p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-zinc-500 dark:text-zinc-400" title="Export CSV">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    </button>
                    <button wire:click="exportPdf" class="p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-zinc-500 dark:text-zinc-400" title="Export PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    </button>
                </div>
            </div>

            <div class="w-full overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Type</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Number</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Client</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Service</th>
                            <th class="text-right py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Amount</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Date</th>
                            <th class="text-right py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $txns = $this->transactions->forPage($this->getPage(), $this->perPage); @endphp
                        @forelse ($txns as $txn)
                            @php
                                $typeStyles = [
                                    'Invoice' => 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300',
                                    'Receipt' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                                    'Quotation' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                ];
                                $statusStyles = [
                                    'draft' => 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400',
                                    'sent' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                    'paid' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                                    'overdue' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                                    'accepted' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                                    'rejected' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                                ];
                            @endphp
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeStyles[$txn['type']] ?? '' }}">{{ $txn['type'] }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $txn['number'] }}</td>
                                <td class="py-3 px-4 text-zinc-700 dark:text-zinc-300">{{ $txn['client'] }}</td>
                                <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 capitalize">{{ $txn['service'] }}</td>
                                <td class="py-3 px-4 text-right font-medium text-zinc-800 dark:text-zinc-200">UGX {{ number_format($txn['amount'], 2) }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusStyles[$txn['status']] ?? '' }}">{{ $txn['status'] }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs text-zinc-400 dark:text-zinc-500">{{ $txn['date']->format('M d, Y') }}</td>
                                <td class="py-3 px-4 text-right">
                                    <flux:button :href="$txn['route']" size="sm" variant="ghost" wire:navigate>View</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-16 text-zinc-400 dark:text-zinc-500">No transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

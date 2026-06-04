<?php

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Transactions')] class extends Component {

    #[Computed]
    public function transactions()
    {
        $items = collect();

        $invoices = Invoice::with('bookService.user')
            ->when(!Auth::user()->isAdmin(), fn($q) => $q->whereHas('bookService', fn($q) => $q->where('user_id', Auth::id())))
            ->latest()
            ->get()
            ->map(fn($i) => [
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

        $quotations = Quotation::with('bookService.user')
            ->when(!Auth::user()->isAdmin(), fn($q) => $q->whereHas('bookService', fn($q) => $q->where('user_id', Auth::id())))
            ->latest()
            ->get()
            ->map(fn($q) => [
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
                    @forelse ($this->transactions as $txn)
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeStyles[$txn['type']] ?? '' }}">
                                    {{ $txn['type'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $txn['number'] }}</td>
                            <td class="py-3 px-4 text-zinc-700 dark:text-zinc-300">{{ $txn['client'] }}</td>
                            <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 capitalize">{{ $txn['service'] }}</td>
                            <td class="py-3 px-4 text-right font-medium text-zinc-800 dark:text-zinc-200">${{ number_format($txn['amount'], 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusStyles[$txn['status']] ?? '' }}">
                                    {{ $txn['status'] }}
                                </span>
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

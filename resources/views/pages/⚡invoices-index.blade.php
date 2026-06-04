<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoices')] class extends Component {

    #[Computed]
    public function invoices()
    {
        return Invoice::with(['bookService'])
            ->whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();
    }

    public function markPaid(int $id): void
    {
        $invoice = Invoice::with('bookService')->findOrFail($id);

        if ($invoice->bookService->user_id !== Auth::id()) return;

        $invoice->update(['status' => 'paid']);
        $invoice->bookService->update(['status' => 'completed']);

        Flux::toast(variant: 'success', text: 'Invoice marked as paid.');
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Invoices</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">View and manage invoices for completed services.</p>
        </div>

        <div class="w-full md:w-1/2 md:mx-auto space-y-6">

    @forelse ($this->invoices as $invoice)
        @php
            $statusStyles = [
                'draft' => 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400',
                'sent' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                'paid' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                'overdue' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
            ];
            $style = $statusStyles[$invoice->status] ?? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400';
            $book = $invoice->bookService;
        @endphp

        <div class="group relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm transition-all duration-200 hover:shadow-md dark:hover:shadow-zinc-900/50">
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-semibold text-sm shrink-0">INV</span>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 truncate capitalize">{{ $book->service_type }}</h3>
                                    <span class="text-xs font-mono text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 rounded">{{ $invoice->invoice_number }}</span>
                                </div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $book->location }}</p>
                            </div>
                        </div>

                        <div class="space-y-1.5 pl-13 mt-2">
                            @foreach (array_slice($invoice->line_items ?? [], 0, 3) as $item)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400 truncate">{{ $item['description'] ?? 'Item' }}</span>
                                    <span class="text-zinc-700 dark:text-zinc-300 font-medium shrink-0 ml-4">${{ number_format($item['total'] ?? 0, 2) }}</span>
                                </div>
                            @endforeach
                            @if (count($invoice->line_items ?? []) > 3)
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">+ {{ count($invoice->line_items) - 3 }} more items</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-6 mt-4 pl-13 pt-3 border-t border-zinc-100 dark:border-zinc-700">
                            <div>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Subtotal</p>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">${{ number_format($invoice->subtotal, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Tax</p>
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">${{ number_format($invoice->tax, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">Total</p>
                                <p class="text-base font-bold text-zinc-800 dark:text-zinc-100">${{ number_format($invoice->total, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2 shrink-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $style }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $invoice->created_at->format('M d, Y') }}</span>
                        <flux:button href="{{ route('invoices.show', $book->id) }}" size="sm" variant="ghost" wire:navigate>
                            View Invoice
                        </flux:button>
                    </div>
                </div>
            </div>

            @if ($invoice->status === 'sent')
                <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-700 flex items-center">
                    <flux:button wire:click="markPaid({{ $invoice->id }})" size="sm" variant="primary" class="ml-auto">Mark as Paid</flux:button>
                </div>
            @endif
        </div>
    @empty
        <div class="text-center py-16 px-4">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-1">No invoices yet</h3>
            <p class="text-sm text-zinc-400 dark:text-zinc-500 mb-4">Invoices will appear here once projects are completed.</p>
            <flux:button href="{{ route('book-services') }}" variant="primary" wire:navigate>View Service Requests</flux:button>
        </div>
    @endforelse
        </div>
    </div>
</div>

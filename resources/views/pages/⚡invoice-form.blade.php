<?php

use App\Models\BookService;
use App\Models\Invoice;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice')] class extends Component {
    public BookService $bookService;
    public ?Invoice $invoice = null;
    public array $lineItems = [];
    public float $taxPercent = 0;
    public string $notes = '';
    public string $status = 'draft';
    public string $invoiceNumber = '';

    public function mount(BookService $bookService): void
    {
        $this->bookService = $bookService;
        $this->invoice = $bookService->invoice;

        if ($this->invoice) {
            $this->lineItems = $this->invoice->line_items;
            $this->taxPercent = $this->invoice->tax;
            $this->notes = $this->invoice->notes ?? '';
            $this->status = $this->invoice->status;
            $this->invoiceNumber = $this->invoice->invoice_number;
        } elseif ($bookService->quotation) {
            $q = $bookService->quotation;
            $this->lineItems = $q->line_items;
            $this->taxPercent = $q->tax;
            $this->notes = $q->notes ?? '';
            $this->invoiceNumber = 'INV-' . strtoupper(str_replace('.', '', uniqid('', true)));
        } else {
            $this->lineItems = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0]];
            $this->invoiceNumber = 'INV-' . strtoupper(substr(uniqid(), -8));
        }
    }

    public function addItem(): void
    {
        $this->lineItems[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function updatedLineItems(): void
    {
        foreach ($this->lineItems as $i => $item) {
            $this->lineItems[$i]['total'] = round(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2);
        }
    }

    #[Computed]
    public function subtotal(): float { return round(array_sum(array_column($this->lineItems, 'total')), 2); }

    #[Computed]
    public function taxAmount(): float { return round($this->subtotal * ($this->taxPercent / 100), 2); }

    #[Computed]
    public function grandTotal(): float { return round($this->subtotal + $this->taxAmount, 2); }

    public function save(): void
    {
        $this->validate([
            'invoiceNumber' => ['required', 'string', 'max:50'],
            'lineItems' => ['required', 'array', 'min:1'],
            'lineItems.*.description' => ['required', 'string', 'max:500'],
            'lineItems.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lineItems.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data = [
            'book_service_id' => $this->bookService->id,
            'project_id' => $this->bookService->project?->id,
            'quotation_id' => $this->bookService->quotation->id,
            'invoice_number' => $this->invoiceNumber,
            'line_items' => $this->lineItems,
            'subtotal' => $this->subtotal,
            'tax' => $this->taxAmount,
            'total' => $this->grandTotal,
            'status' => $this->status,
            'notes' => $this->notes,
        ];

        if ($this->invoice) {
            $this->invoice->update($data);
            Flux::toast(variant: 'success', text: 'Invoice updated.');
        } else {
            Invoice::create($data);
            Flux::toast(variant: 'success', text: 'Invoice generated.');
        }

        $this->redirect(route('book-services'), navigate: true);
    }

    public function markSent(): void
    {
        $this->invoice?->update(['status' => 'sent']);
        $this->status = 'sent';
        Flux::toast(variant: 'success', text: 'Invoice marked as sent.');
    }

    public function markPaid(): void
    {
        if ($this->invoice) {
            $this->invoice->update(['status' => 'paid']);
            $this->status = 'paid';
            $this->bookService->update(['status' => 'completed']);
            Flux::toast(variant: 'success', text: 'Invoice marked as paid.');
        }
    }
}; ?>

<div class="w-full max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">Invoice</flux:heading>
                @if ($invoice)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        {{ $status === 'paid' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($status === 'sent' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400') }}">
                        {{ ucfirst($status) }}
                    </span>
                @endif
            </div>
            <flux:subheading>{{ $bookService->service_type }} &mdash; {{ $bookService->location }}</flux:subheading>
        </div>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div class="lg:col-span-3 space-y-6">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
                    <flux:input wire:model="invoiceNumber" label="Invoice Number" required />
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Line Items</h3>
                        <button type="button" wire:click="addItem" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">+ Add Item</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-xs text-zinc-400 dark:text-zinc-500 bg-zinc-50 dark:bg-zinc-800/50">
                                    <th class="p-3 pl-6 text-left font-medium">Description</th>
                                    <th class="p-3 text-center font-medium w-20">Qty</th>
                                    <th class="p-3 text-right font-medium w-28">Unit Price</th>
                                    <th class="p-3 text-right font-medium w-24">Total</th>
                                    <th class="p-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lineItems as $i => $item)
                                    <tr class="border-t border-zinc-100 dark:border-zinc-700">
                                        <td class="p-2 pl-6">
                                            <input type="text" wire:model="lineItems.{{ $i }}.description" placeholder="Item description"
                                                   class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-800/20 dark:focus:ring-zinc-400/20 focus:border-zinc-800 dark:focus:border-zinc-400">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" step="0.01" min="0.01" wire:model.live="lineItems.{{ $i }}.quantity"
                                                   class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-center text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-800/20 dark:focus:ring-zinc-400/20 focus:border-zinc-800 dark:focus:border-zinc-400">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" step="0.01" min="0" wire:model.live="lineItems.{{ $i }}.unit_price"
                                                   class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-800/20 dark:focus:ring-zinc-400/20 focus:border-zinc-800 dark:focus:border-zinc-400">
                                        </td>
                                        <td class="p-2 text-right text-sm font-medium text-zinc-700 dark:text-zinc-300">UGX {{ number_format($item['total'] ?? 0, 2) }}</td>
                                        <td class="p-2 text-center">
                                            @if (count($lineItems) > 1)
                                                <button type="button" wire:click="removeItem({{ $i }})" class="text-red-400 hover:text-red-600 transition-colors text-lg leading-none">&times;</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 space-y-4">
                    <flux:input type="number" step="0.01" min="0" max="100" wire:model.live="taxPercent" label="Tax (%)" />
                    <flux:textarea wire:model="notes" label="Notes" placeholder="Payment terms, due date, etc..." rows="3" />
                </div>

                <div class="flex items-center gap-3">
                    <flux:button variant="primary" type="submit">{{ $invoice ? 'Update Invoice' : 'Generate Invoice' }}</flux:button>
                    <flux:button href="{{ route('book-services') }}" variant="ghost" wire:navigate>Back</flux:button>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden sticky top-6">
                    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/80">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Preview</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start border-b border-zinc-100 dark:border-zinc-700 pb-4 mb-4">
                            <div>
                                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 tracking-widest uppercase">Invoice</p>
                                <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-1">{{ $invoiceNumber }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 capitalize">{{ $bookService->service_type }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $bookService->location }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 min-h-[120px]">
                            @forelse ($lineItems as $item)
                                @if ($item['description'])
                                    <div class="flex justify-between text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400 truncate">{{ $item['description'] }}</span>
                                        <span class="text-zinc-800 dark:text-zinc-200 font-medium shrink-0 ml-4">UGX {{ number_format($item['total'] ?? 0, 2) }}</span>
                                    </div>
                                @endif
                            @empty
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-6">Add line items to see preview</p>
                            @endforelse
                        </div>
                        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 space-y-1 text-right">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Subtotal: <span class="font-medium text-zinc-700 dark:text-zinc-300">UGX {{ number_format($this->subtotal, 2) }}</span></p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tax ({{ $taxPercent }}%): <span class="font-medium text-zinc-700 dark:text-zinc-300">UGX {{ number_format($this->taxAmount, 2) }}</span></p>
                            <p class="text-base font-bold text-zinc-800 dark:text-zinc-100">Total: UGX {{ number_format($this->grandTotal, 2) }}</p>
                        </div>
                        @if ($notes)
                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                                <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 mb-1">Notes</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 whitespace-pre-wrap">{{ $notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="flex gap-3">
        @if ($invoice && $status === 'draft')
            <flux:button wire:click="markSent" variant="primary">Mark as Sent</flux:button>
        @endif

        @if ($invoice && $status === 'sent')
            <flux:button href="{{ route('receipts.create', $bookService->id) }}" variant="primary" class="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400" wire:navigate>Record Receipt</flux:button>
        @endif
    </div>

    @if ($invoice && $invoice->payments->count() > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Receipts ({{ $invoice->payments->count() }})</h3>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach ($invoice->payments as $payment)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $payment->receipt_number ?? 'Receipt' }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                {{ ucfirst(str_replace('_', ' ', $payment->method)) }}
                                @if ($payment->reference)
                                    &middot; {{ $payment->reference }}
                                @endif
                                &middot; {{ ($payment->paid_at ?? $payment->created_at)->format('M d, Y') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100">UGX {{ number_format($payment->amount, 2) }}</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $payment->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($payment->status === 'pending' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400') }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

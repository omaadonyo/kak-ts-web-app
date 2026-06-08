<?php

use App\Models\BookService;
use App\Models\Invoice;
use App\Models\Payment;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Record Receipt')] class extends Component {
    public BookService $bookService;
    public ?Invoice $invoice = null;
    public ?Payment $payment = null;
    public float $amount = 0;
    public string $method = 'bank_transfer';
    public string $reference = '';
    public string $notes = '';
    public string $status = 'completed';
    public string $paidAt = '';

    public function mount(BookService $bookService, ?Payment $payment = null): void
    {
        $this->bookService = $bookService;
        $this->invoice = $bookService->invoice;
        $this->payment = $payment;

        if ($this->payment) {
            $this->amount = $this->payment->amount;
            $this->method = $this->payment->method;
            $this->reference = $this->payment->reference ?? '';
            $this->notes = $this->payment->notes ?? '';
            $this->status = $this->payment->status;
            $this->paidAt = $this->payment->paid_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        } else {
            $totalPaid = $this->invoice?->payments()->where('status', '!=', 'failed')->sum('amount') ?? 0;
            $this->amount = max(0, ($this->invoice->total ?? 0) - $totalPaid);
            $this->paidAt = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank_transfer,mobile_money,cheque'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:pending,completed,failed,refunded'],
            'paidAt' => ['required', 'date'],
        ]);

        if (!$this->invoice) {
            Flux::toast(variant: 'error', text: 'No invoice found for this booking.');
            return;
        }

        $data = [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'status' => $this->status,
            'paid_at' => $this->paidAt,
        ];

        if ($this->payment) {
            $this->payment->update($data);
            Flux::toast(variant: 'success', text: 'Receipt updated.');
        } else {
            $data['receipt_number'] = Payment::generateReceiptNumber();
            Payment::create($data);
            Flux::toast(variant: 'success', text: 'Receipt recorded.');
        }

        $this->redirect(route('invoices.show', $this->bookService->id), navigate: true);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Record Receipt</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 capitalize">{{ $bookService->service_type }} &mdash; {{ $bookService->location }}</p>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Amount (UGX) <span class="text-zinc-400">*</span></label>
                        <input type="number" step="0.01" min="0.01" wire:model="amount" placeholder="0.00"
                               class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                        @error('amount') <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Method <span class="text-zinc-400">*</span></label>
                        <select wire:model="method" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Reference <span class="text-zinc-400">(optional)</span></label>
                        <input type="text" wire:model="reference" placeholder="Transaction ID or reference..."
                               class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Status <span class="text-zinc-400">*</span></label>
                        <select wire:model="status" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Date <span class="text-zinc-400">*</span></label>
                        <input type="date" wire:model="paidAt"
                               class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                        @error('paidAt') <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Invoice</label>
                        <p class="mt-1.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $invoice?->invoice_number ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Notes <span class="text-zinc-400">(optional)</span></label>
                    <textarea wire:model="notes" placeholder="Additional notes..."
                              rows="3"
                              class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 resize-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <flux:button href="{{ route('invoices.show', $bookService->id) }}" variant="ghost" wire:navigate>Back</flux:button>
                <flux:button variant="primary" type="submit">{{ $payment ? 'Update Receipt' : 'Record Receipt' }}</flux:button>
            </div>
        </form>
    </div>
</div>

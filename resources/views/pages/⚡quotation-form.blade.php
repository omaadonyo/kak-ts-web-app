<?php

use App\Models\BookService;
use App\Models\Quotation;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Quotation')] class extends Component {
    public BookService $bookService;
    public ?Quotation $quotation = null;
    public array $lineItems = [];
    public float $taxPercent = 0;
    public ?string $validUntil = null;
    public string $notes = '';
    public string $status = 'draft';

    public function mount(BookService $bookService): void
    {
        $this->bookService = $bookService;
        $this->quotation = $bookService->quotation;

        if ($this->quotation) {
            $this->lineItems = $this->quotation->line_items;
            $this->taxPercent = $this->quotation->tax;
            $this->validUntil = $this->quotation->valid_until?->format('Y-m-d');
            $this->notes = $this->quotation->notes ?? '';
            $this->status = $this->quotation->status;
        } else {
            $this->addItem();
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
            'lineItems' => ['required', 'array', 'min:1'],
            'lineItems.*.description' => ['required', 'string', 'max:500'],
            'lineItems.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lineItems.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data = [
            'book_service_id' => $this->bookService->id,
            'assessment_id' => $this->bookService->assessment?->id,
            'line_items' => $this->lineItems,
            'subtotal' => $this->subtotal,
            'tax' => $this->taxAmount,
            'total' => $this->grandTotal,
            'status' => $this->status,
            'valid_until' => $this->validUntil,
            'notes' => $this->notes,
        ];

        if ($this->quotation) {
            $this->quotation->update($data);
            Flux::toast(variant: 'success', text: 'Quotation updated.');
        } else {
            Quotation::create($data);
            Flux::toast(variant: 'success', text: 'Quotation generated.');
        }

        $this->redirect(route('book-services'), navigate: true);
    }

    public function markSent(): void
    {
        if ($this->quotation) {
            $this->quotation->update(['status' => 'sent']);
            $this->status = 'sent';
            Flux::toast(variant: 'success', text: 'Quotation marked as sent.');
        }
    }

    public function markAccepted(): void
    {
        if ($this->quotation) {
            $this->quotation->update(['status' => 'accepted']);
            App\Models\Project::create([
                'book_service_id' => $this->bookService->id,
                'quotation_id' => $this->quotation->id,
                'name' => $this->bookService->service_type . ' - ' . $this->bookService->location,
                'description' => $this->bookService->notes,
                'progress' => 0,
                'status' => 'not_started',
            ]);
            Flux::toast(variant: 'success', text: 'Quotation accepted. Project created.');
            $this->redirect(route('book-services'), navigate: true);
        }
    }
}; ?>

<div class="w-full md:w-1/2 md:mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">Quotation</flux:heading>
                @if ($quotation)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 capitalize">{{ $quotation->status }}</span>
                @endif
            </div>
            <flux:subheading>{{ $bookService->service_type }} &mdash; {{ $bookService->location }}</flux:subheading>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
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
                                <td class="p-2 text-right text-sm font-medium text-zinc-700 dark:text-zinc-300">${{ number_format($item['total'] ?? 0, 2) }}</td>
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

        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 space-y-4">
                <flux:input type="number" step="0.01" min="0" max="100" wire:model.live="taxPercent" label="Tax (%)" />
                <flux:input type="date" wire:model="validUntil" label="Valid Until" />
                <flux:textarea wire:model="notes" label="Notes / Terms" placeholder="Payment terms, warranty, etc..." rows="3" />
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Preview</h3>
                </div>
                <div class="p-6">
                    <div class="text-center mb-4">
                        <p class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 tracking-widest uppercase">Quotation</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 capitalize mt-1">{{ $bookService->service_type }} &mdash; {{ $bookService->location }}</p>
                    </div>
                    <div class="space-y-2">
                        @foreach ($lineItems as $item)
                            @if ($item['description'])
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400 truncate">{{ $item['description'] }}</span>
                                    <span class="text-zinc-800 dark:text-zinc-200 font-medium shrink-0 ml-4">${{ number_format($item['total'] ?? 0, 2) }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 space-y-1 text-right">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Subtotal: <span class="font-medium text-zinc-700 dark:text-zinc-300">${{ number_format($this->subtotal, 2) }}</span></p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Tax ({{ $taxPercent }}%): <span class="font-medium text-zinc-700 dark:text-zinc-300">${{ number_format($this->taxAmount, 2) }}</span></p>
                        <p class="text-base font-bold text-zinc-800 dark:text-zinc-100">Total: ${{ number_format($this->grandTotal, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">{{ $quotation ? 'Update Quotation' : 'Generate Quotation' }}</flux:button>
            <flux:button href="{{ route('book-services') }}" variant="ghost" wire:navigate>Back</flux:button>
        </div>
    </form>

    @if ($quotation && $status === 'draft')
        <div class="flex gap-3">
            <flux:button wire:click="markSent" variant="primary">Mark as Sent</flux:button>
        </div>
    @endif

    @if ($quotation && $status === 'sent')
        <div class="flex gap-3">
            <flux:button wire:click="markAccepted" variant="primary" class="bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400">Accept &amp; Start Project</flux:button>
        </div>
    @endif
</div>

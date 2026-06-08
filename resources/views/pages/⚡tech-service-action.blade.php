<?php

use App\Models\Assessment;
use App\Models\BookService;
use App\Models\Quotation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Service Action')] class extends Component {
    use WithFileUploads;

    public BookService $bookService;
    public ?Assessment $assessment = null;
    public ?Quotation $quotation = null;

    // Assessment fields
    public string $findings = '';
    public array $photos = [];
    public array $photoPreviews = [];

    // Quotation fields
    public array $lineItems = [];
    public float $taxPercent = 0;
    public ?string $validUntil = null;
    public string $notes = '';

    public function mount(BookService $bookService): void
    {
        $this->bookService = $bookService;
        $this->assessment = $bookService->assessment;
        $this->quotation = $bookService->quotation;

        if ($this->assessment) {
            $this->findings = $this->assessment->findings;
        }
        if ($this->quotation) {
            $this->lineItems = $this->quotation->line_items;
            $this->taxPercent = $this->quotation->tax;
            $this->validUntil = $this->quotation->valid_until?->format('Y-m-d');
            $this->notes = $this->quotation->notes ?? '';
        } else {
            $this->addItem();
        }
    }

    // Assessment methods
    public function updatedPhotos(): void
    {
        $this->photoPreviews = [];
        foreach ($this->photos as $photo) {
            $this->photoPreviews[] = $photo->temporaryUrl();
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index], $this->photoPreviews[$index]);
        $this->photos = array_values($this->photos);
        $this->photoPreviews = array_values($this->photoPreviews);
    }

    public function saveAssessment(): void
    {
        $this->validate([
            'findings' => ['required', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ]);

        $paths = $this->assessment?->photos ?? [];
        foreach ($this->photos as $photo) {
            $paths[] = $photo->store('assessments', 'public');
        }

        if ($this->assessment) {
            $this->assessment->update(['findings' => $this->findings, 'photos' => $paths]);
            Flux::toast(variant: 'success', text: 'Assessment updated.');
        } else {
            Assessment::create([
                'book_service_id' => $this->bookService->id,
                'assessed_by' => Auth::id(),
                'findings' => $this->findings,
                'photos' => $paths,
                'status' => 'completed',
            ]);
            Flux::toast(variant: 'success', text: 'Assessment submitted.');
            $this->assessment = $this->bookService->fresh()->assessment;
        }
    }

    // Quotation methods
    public function addItem(): void
    {
        $this->lineItems[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0];
    }

    public function removeItem(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function getSubtotalProperty(): float
    {
        return round(array_sum(array_map(fn($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), $this->lineItems)), 2);
    }

    public function getTaxAmountProperty(): float
    {
        return round($this->subtotal * ($this->taxPercent / 100), 2);
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->subtotal + $this->taxAmount, 2);
    }

    public function saveQuotation(): void
    {
        $this->validate([
            'lineItems' => ['required', 'array', 'min:1'],
            'lineItems.*.description' => ['required', 'string', 'max:500'],
            'lineItems.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'lineItems.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $lineItems = array_map(fn($item) => array_merge($item, ['total' => round(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2)]), $this->lineItems);
        $data = [
            'book_service_id' => $this->bookService->id,
            'assessment_id' => $this->bookService->assessment?->id,
            'line_items' => $lineItems,
            'subtotal' => $this->subtotal,
            'tax' => $this->taxAmount,
            'total' => $this->grandTotal,
            'status' => 'draft',
            'valid_until' => $this->validUntil,
            'notes' => $this->notes,
        ];

        if ($this->quotation) {
            $this->quotation->update($data);
            Flux::toast(variant: 'success', text: 'Quotation updated.');
        } else {
            Quotation::create($data);
            Flux::toast(variant: 'success', text: 'Quotation saved.');
            $this->quotation = $this->bookService->fresh()->quotation;
        }
    }

    public function markSent(): void
    {
        if ($this->quotation) {
            $this->quotation->update(['status' => 'sent']);
            Flux::toast(variant: 'success', text: 'Quotation marked as sent.');
        }
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 px-4">
    <div class="max-w-5xl mx-auto space-y-8">
        <div>
            <flux:button href="{{ route('book-services') }}" variant="ghost" wire:navigate>&larr; Back to Services</flux:button>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-4 capitalize">{{ $bookService->service_type }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ $bookService->location }} &middot; {{ $bookService->created_at->format('d M Y') }}</p>
            @if ($bookService->notes)
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">{{ $bookService->notes }}</p>
            @endif
        </div>

        {{-- Assessment Section --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                {{ $assessment ? 'Assessment' : 'Create Assessment' }}
                @if ($assessment)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 ml-2">Completed</span>
                @endif
            </h2>

            @if ($bookService->photos && count($bookService->photos) > 0)
                <div class="mb-4">
                    <p class="text-xs text-zinc-400 mb-2">Service photos:</p>
                    <div class="flex gap-2">
                        @foreach ($bookService->photos as $photo)
                            <img src="{{ asset('storage/' . $photo) }}" class="w-20 h-20 rounded-lg object-cover border border-zinc-200 dark:border-zinc-700">
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Findings</label>
                    <textarea wire:model="findings" rows="5" class="w-full mt-1 border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 resize-none" placeholder="Describe your findings..."></textarea>
                    @error('findings') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Photos (optional)</label>
                    <input type="file" multiple accept="image/*" wire:model="photos" class="mt-1 block text-sm text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600">
                    @if (!empty($photoPreviews))
                        <div class="flex gap-2 mt-2 flex-wrap">
                            @foreach ($photoPreviews as $i => $preview)
                                <div class="relative">
                                    <img src="{{ $preview }}" class="w-20 h-20 rounded-lg object-cover border">
                                    <button type="button" wire:click="removePhoto({{ $i }})" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center">&times;</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <flux:button wire:click="saveAssessment" variant="primary">
                    {{ $assessment ? 'Update Assessment' : 'Submit Assessment' }}
                </flux:button>
            </div>
        </div>

        {{-- Quotation Section --}}
        @if ($assessment)
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">
                    {{ $quotation ? 'Quotation' : 'Create Quotation' }}
                    @if ($quotation)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize bg-{{ $quotation->status === 'sent' ? 'blue' : ($quotation->status === 'accepted' ? 'emerald' : 'zinc') }}-50 dark:bg-{{ $quotation->status === 'sent' ? 'blue' : ($quotation->status === 'accepted' ? 'emerald' : 'zinc') }}-900/30 text-{{ $quotation->status === 'sent' ? 'blue' : ($quotation->status === 'accepted' ? 'emerald' : 'zinc') }}-700 dark:text-{{ $quotation->status === 'sent' ? 'blue' : ($quotation->status === 'accepted' ? 'emerald' : 'zinc') }}-400 ml-2">{{ $quotation->status }}</span>
                    @endif
                </h2>

                <div class="space-y-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                                <th class="text-left py-2 font-medium">Description</th>
                                <th class="text-center py-2 font-medium w-20">Qty</th>
                                <th class="text-right py-2 font-medium w-28">Unit Price</th>
                                <th class="text-right py-2 font-medium w-24">Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lineItems as $i => $item)
                                <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                    <td class="py-2 pr-2">
                                        <input wire:model="lineItems.{{ $i }}.description" placeholder="Description..." class="w-full border-0 bg-transparent text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="number" step="0.01" min="0.01" wire:model.live="lineItems.{{ $i }}.quantity" class="w-16 text-center border-0 bg-transparent text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none">
                                    </td>
                                    <td class="py-2 px-1">
                                        <input type="number" step="0.01" min="0" wire:model.live="lineItems.{{ $i }}.unit_price" class="w-24 text-right border-0 bg-transparent text-sm text-zinc-800 dark:text-zinc-200 focus:outline-none">
                                    </td>
                                    <td class="py-2 text-right text-sm text-zinc-700 dark:text-zinc-300">UGX {{ number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 0) }}</td>
                                    <td class="py-2 text-center">
                                        <button type="button" wire:click="removeItem({{ $i }})" class="text-red-400 hover:text-red-600 text-xs">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <button type="button" wire:click="addItem" class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100">+ Add Line Item</button>

                    <div class="flex justify-end">
                        <div class="w-64 space-y-1 text-sm">
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400"><span>Subtotal</span><span>UGX {{ number_format($this->subtotal, 0) }}</span></div>
                            <div class="flex items-center justify-between text-zinc-600 dark:text-zinc-400">
                                <span>Tax</span>
                                <div class="flex items-center gap-1">
                                    <input type="number" step="0.01" wire:model="taxPercent" class="w-16 text-right border border-zinc-200 dark:border-zinc-600 rounded-lg px-2 py-0.5 text-sm bg-transparent dark:bg-zinc-700/30">%
                                </div>
                            </div>
                            <div class="flex justify-between text-zinc-600 dark:text-zinc-400"><span>Tax Amount</span><span>UGX {{ number_format($this->taxAmount, 0) }}</span></div>
                            <div class="flex justify-between font-semibold text-zinc-900 dark:text-zinc-100 pt-1 border-t border-zinc-200 dark:border-zinc-700"><span>Total</span><span>UGX {{ number_format($this->grandTotal, 0) }}</span></div>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Valid Until</label>
                        <input type="date" wire:model="validUntil" class="mt-1 border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2 text-sm bg-transparent dark:bg-zinc-700/30">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Notes</label>
                        <textarea wire:model="notes" rows="2" class="w-full mt-1 border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2 text-sm bg-transparent dark:bg-zinc-700/30 resize-none" placeholder="Optional notes..."></textarea>
                    </div>

                    <div class="flex gap-2">
                        <flux:button wire:click="saveQuotation" variant="primary">{{ $quotation ? 'Update Quotation' : 'Save Quotation' }}</flux:button>
                        @if ($quotation)
                            <flux:button wire:click="markSent" variant="secondary">Mark as Sent</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 text-center text-sm text-zinc-400">
                Complete the assessment first to create a quotation.
            </div>
        @endif
    </div>
</div>

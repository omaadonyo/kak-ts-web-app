<?php

use App\Models\Assessment;
use App\Models\BookService;
use App\Models\Quotation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Assessment Report')] class extends Component {
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

    // Assessment
    public function rules(): array
    {
        return [
            'findings' => ['required', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ];
    }

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
        $this->validate();

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
        }

        $this->assessment = $this->bookService->fresh()->assessment;
    }

    // Quotation
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
            'assessment_id' => $this->assessment?->id,
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
            $this->quotation = $this->bookService->fresh()->quotation;
            Flux::toast(variant: 'success', text: 'Quotation marked as sent.');
        }
    }
}; ?>

@php $isEditable = Auth::user()->isAdmin() || Auth::user()->isTechnician(); @endphp

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M12 3a9 9 0 0 0-9 9v1h2.5a2.5 2.5 0 0 1 0 5H3v1a9 9 0 0 0 9 9h.5v-2.5a2.5 2.5 0 0 1 5 0V23h1a9 9 0 0 0 9-9v-1h-2.5a2.5 2.5 0 0 1 0-5H21v-1a9 9 0 0 0-9-9z"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Assessment Report</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 capitalize">{{ $bookService->service_type }} &mdash; {{ $bookService->location }}</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-8">
            {{-- Service Photos --}}
            @if ($bookService->photos && count($bookService->photos) > 0)
                @php $servicePhotos = array_map(fn($p) => asset('storage/' . $p), $bookService->photos); @endphp
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                    <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Service Request Photos</h2>
                    <div x-data="{ activeSlide: 0, images: @js($servicePhotos), prev() { this.activeSlide = this.activeSlide === 0 ? this.images.length - 1 : this.activeSlide - 1; }, next() { this.activeSlide = this.activeSlide === this.images.length - 1 ? 0 : this.activeSlide + 1; } }" class="space-y-3">
                        <div class="relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-700/50">
                            <div class="w-full aspect-[4/3]">
                                <template x-for="(src, i) in images" :key="i">
                                    <div x-show="activeSlide === i" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="w-full h-full">
                                        <img :src="src" alt="" class="w-full h-full object-contain" loading="lazy">
                                    </div>
                                </template>
                            </div>
                            <button x-show="images.length > 1" @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
                            <button x-show="images.length > 1" @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
                            <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-lg font-medium" x-show="images.length > 1" x-text="`${activeSlide + 1} / ${images.length}`"></div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Assessment Section --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Assessment</h2>
                    @if ($assessment)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">Completed</span>
                    @endif
                </div>

                @if ($isEditable)
                    <div class="space-y-5">
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Findings <span class="text-zinc-400">*</span></label>
                            <textarea wire:model="findings" rows="5" class="w-full mt-1 border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 resize-none" placeholder="Describe the assessment findings..."></textarea>
                            @error('findings') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Photos</label>
                            <input type="file" multiple accept="image/*" wire:model="photos" class="mt-1 block text-sm text-zinc-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600">
                            @if (!empty($photoPreviews))
                                <div class="flex gap-2 mt-2 flex-wrap">
                                    @foreach ($photoPreviews as $i => $preview)
                                        <div class="relative">
                                            <img src="{{ $preview }}" class="w-20 h-20 rounded-lg object-cover border">
                                            <button type="button" wire:click="removePhoto({{ $i }})" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <flux:button wire:click="saveAssessment" variant="primary">{{ $assessment ? 'Update Assessment' : 'Submit Assessment' }}</flux:button>
                    </div>
                @elseif ($assessment)
                    <div class="prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $assessment->findings }}</div>
                    @if ($assessment->photos && count($assessment->photos) > 0)
                        @php $aps = array_map(fn($p) => asset('storage/' . $p), $assessment->photos); @endphp
                        <div class="mt-4 flex gap-2 flex-wrap">
                            @foreach ($aps as $p)
                                <img src="{{ $p }}" class="w-20 h-20 rounded-lg object-cover border">
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="text-zinc-400 text-sm">Assessment not yet completed.</p>
                @endif
            </div>

            {{-- Quotation Section (only if assessment exists) --}}
            @if ($assessment)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Quotation</h2>
                        @if ($quotation)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize
                                {{ $quotation->status === 'sent' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : ($quotation->status === 'accepted' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400') }}">
                                {{ $quotation->status }}
                            </span>
                        @endif
                    </div>

                    @if ($isEditable)
                        <div class="space-y-5">
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
                                @if ($quotation && $quotation->status === 'draft')
                                    <flux:button wire:click="markSent" variant="secondary">Mark as Sent</flux:button>
                                @endif
                            </div>
                        </div>
                    @elseif ($quotation)
                        <div class="space-y-4">
                            <table class="w-full text-sm">
                                <thead><tr class="text-xs text-zinc-400 border-b border-zinc-200 dark:border-zinc-700"><th class="text-left py-2 font-medium">Description</th><th class="text-center py-2 font-medium w-20">Qty</th><th class="text-right py-2 font-medium w-28">Unit Price</th><th class="text-right py-2 font-medium w-24">Total</th></tr></thead>
                                <tbody>
                                    @foreach ($quotation->line_items as $item)
                                        <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                            <td class="py-2 text-zinc-700 dark:text-zinc-300">{{ $item['description'] }}</td>
                                            <td class="py-2 text-center text-zinc-600 dark:text-zinc-400">{{ $item['quantity'] }}</td>
                                            <td class="py-2 text-right text-zinc-600 dark:text-zinc-400">UGX {{ number_format($item['unit_price'], 0) }}</td>
                                            <td class="py-2 text-right text-zinc-700 dark:text-zinc-300 font-medium">UGX {{ number_format($item['total'], 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="flex justify-end">
                                <div class="w-64 space-y-1 text-sm">
                                    <div class="flex justify-between text-zinc-600 dark:text-zinc-400"><span>Subtotal</span><span>UGX {{ number_format($quotation->subtotal, 0) }}</span></div>
                                    <div class="flex justify-between text-zinc-600 dark:text-zinc-400"><span>Tax</span><span>UGX {{ number_format($quotation->tax, 0) }}</span></div>
                                    <div class="flex justify-between font-semibold text-zinc-900 dark:text-zinc-100 pt-1 border-t border-zinc-200 dark:border-zinc-700"><span>Total</span><span>UGX {{ number_format($quotation->total, 0) }}</span></div>
                                </div>
                            </div>
                            @if ($quotation->notes)
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $quotation->notes }}</p>
                            @endif
                        </div>
                    @else
                        <p class="text-zinc-400 text-sm">No quotation yet.</p>
                    @endif
                </div>
            @endif

            <div class="flex">
                <flux:button href="{{ route('book-services') }}" variant="ghost" wire:navigate>&larr; Back to Services</flux:button>
            </div>
        </div>
    </div>
</div>

<?php

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Quotations')] class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    protected $queryString = ['search', 'perPage'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function quotations()
    {
        $query = Quotation::with(['bookService.user']);

        if (Auth::user()->isClient()) {
            $ids = [Auth::id()];
            if (Auth::user()->isCompany()) $ids = array_merge($ids, Auth::user()->companyUsers()->pluck('id')->toArray());
            $query->whereHas('bookService', fn($q) => $q->whereIn('user_id', $ids));
        } elseif (Auth::user()->isTechnician()) {
            $query->whereHas('bookService', fn($q) => $q->where('assigned_to', Auth::id()));
        }

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%")
                  ->orWhereHas('bookService', fn($q) => $q->where('service_type', 'like', "%{$s}%")->orWhere('location', 'like', "%{$s}%"))
                  ->orWhereHas('bookService.user', fn($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }

        return $query->latest()->paginate($this->perPage);
    }

    public function accept(int $id): void
    {
        $quotation = Quotation::with('bookService')->findOrFail($id);
        if ($quotation->bookService->user_id !== Auth::id()) return;
        $quotation->update(['status' => 'accepted']);
        if (!$quotation->bookService->project) {
            App\Models\Project::create([
                'book_service_id' => $quotation->bookService->id,
                'quotation_id' => $quotation->id,
                'name' => $quotation->bookService->service_type . ' - ' . $quotation->bookService->location,
                'description' => $quotation->bookService->notes,
                'progress' => 0,
                'status' => 'not_started',
            ]);
        }
        Flux::toast(variant: 'success', text: 'Quotation accepted. Project has been created.');
        $this->redirect(route('book-services'), navigate: true);
    }

    public function exportCsv()
    {
        $quotations = $this->exportQuery()->get();
        $headers = ['ID', 'Client', 'Service Type', 'Location', 'Subtotal', 'Tax', 'Total', 'Status', 'Valid Until', 'Created At'];
        $rows = $quotations->map(fn($q) => [
            $q->id, $q->bookService->user->name, $q->bookService->service_type,
            $q->bookService->location, $q->subtotal, $q->tax, $q->total,
            $q->status, $q->valid_until?->format('Y-m-d') ?? '', $q->created_at->format('Y-m-d H:i'),
        ]);
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($rows as $row) fputcsv($csv, $row);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        return response()->streamDownload(fn() => print $content, 'quotations.csv');
    }

    public function exportPdf()
    {
        $quotations = $this->exportQuery()->get();
        $pdf = Pdf::loadView('exports.quotations', compact('quotations'));
        return response()->streamDownload(fn() => print $pdf->output(), 'quotations.pdf');
    }

    private function exportQuery()
    {
        $query = Quotation::with(['bookService.user']);
        if (Auth::user()->isClient()) {
            $ids = [Auth::id()];
            if (Auth::user()->isCompany()) $ids = array_merge($ids, Auth::user()->companyUsers()->pluck('id')->toArray());
            $query->whereHas('bookService', fn($q) => $q->whereIn('user_id', $ids));
        } elseif (Auth::user()->isTechnician()) {
            $query->whereHas('bookService', fn($q) => $q->where('assigned_to', Auth::id()));
        }
        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%")
                  ->orWhereHas('bookService', fn($q) => $q->where('service_type', 'like', "%{$s}%"));
            });
        }
        return $query;
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Quotations</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Review and manage quotations.</p>
        </div>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" wire:model.live.debounce="search" placeholder="Search quotations..."
                           class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20 focus:border-zinc-900 dark:focus:border-zinc-400">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select wire:model.live="perPage" class="border border-zinc-200 dark:border-zinc-600 rounded-xl px-3 py-2.5 text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800">
                        <option value="10">10/page</option>
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

            @forelse ($this->quotations as $quotation)
                @php
                    $statusStyles = [
                        'draft' => 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400',
                        'sent' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                        'accepted' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
                        'rejected' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                    ];
                    $style = $statusStyles[$quotation->status] ?? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400';
                    $book = $quotation->bookService;
                @endphp

                <div class="group relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm transition-all duration-200 hover:shadow-md dark:hover:shadow-zinc-900/50">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-semibold text-sm shrink-0">Q</span>
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 truncate capitalize">{{ $book->service_type }}</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $book->location }}</p>
                                        @if (!Auth::user()->isClient())
                                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Client: {{ $book->user->name }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-1.5 pl-13">
                                    @foreach (array_slice($quotation->line_items ?? [], 0, 3) as $item)
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-zinc-600 dark:text-zinc-400 truncate">{{ $item['description'] ?? 'Item' }}</span>
                                            <span class="text-zinc-700 dark:text-zinc-300 font-medium shrink-0 ml-4">UGX {{ number_format($item['total'] ?? 0, 2) }}</span>
                                        </div>
                                    @endforeach
                                    @if (count($quotation->line_items ?? []) > 3)
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500">+ {{ count($quotation->line_items) - 3 }} more items</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-4 mt-4 pl-13 pt-3 border-t border-zinc-100 dark:border-zinc-700">
                                    <div>
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500">Subtotal</p>
                                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">UGX {{ number_format($quotation->subtotal, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500">Tax</p>
                                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">UGX {{ number_format($quotation->tax, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-400 dark:text-zinc-500">Total</p>
                                        <p class="text-base font-bold text-zinc-800 dark:text-zinc-100">UGX {{ number_format($quotation->total, 2) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $style }}">
                                    {{ ucfirst($quotation->status) }}
                                </span>
                                @if ($quotation->valid_until)
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">Valid until {{ $quotation->valid_until->format('M d, Y') }}</span>
                                @endif
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $quotation->created_at->format('M d, Y') }}</span>
                                <a href="{{ route('quotations.show', $book->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-700 flex items-center gap-2">
                        <a href="{{ route('quotations.show', $book->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            Full Quote
                        </a>
                        @if ($quotation->status === 'sent')
                            <flux:button wire:click="accept({{ $quotation->id }})" size="sm" variant="primary" class="ml-auto">Accept & Start Project</flux:button>
                        @endif
                        @if ($quotation->bookService->invoice)
                            <a href="{{ route('invoices.show', $book->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors ml-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                Invoice
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-16 px-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-1">{{ $this->search ? 'No matching results' : 'No quotations yet' }}</h3>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ $this->search ? 'Try a different search term.' : 'Quotations will appear here once assessments are completed.' }}</p>
                </div>
            @endforelse

            @if ($this->quotations->hasPages())
                <div class="mt-6">{{ $this->quotations->links(data: ['scrollTo' => false]) }}</div>
            @endif
        </div>
    </div>
</div>

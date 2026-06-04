<?php

use App\Models\Assessment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Assessments')] class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    protected $queryString = ['search', 'perPage'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function assessments()
    {
        $query = Assessment::with(['bookService.user', 'assessedBy']);

        if (Auth::user()->isClient()) {
            $ids = [Auth::id()];
            if (Auth::user()->isCompany()) $ids = array_merge($ids, Auth::user()->companyUsers()->pluck('id')->toArray());
            $query->whereHas('bookService', fn($q) => $q->whereIn('user_id', $ids));
        } elseif (Auth::user()->isTechnician()) {
            $query->where('assessed_by', Auth::id());
        }

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('findings', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%")
                  ->orWhereHas('bookService', fn($q) => $q->where('service_type', 'like', "%{$s}%")->orWhere('location', 'like', "%{$s}%"))
                  ->orWhereHas('bookService.user', fn($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }

        return $query->latest()->paginate($this->perPage);
    }

    public function exportCsv()
    {
        $assessments = $this->exportQuery()->get();
        $headers = ['ID', 'Client', 'Service Type', 'Location', 'Findings', 'Status', 'Assessed By', 'Created At'];
        $rows = $assessments->map(fn($a) => [
            $a->id, $a->bookService->user->name, $a->bookService->service_type,
            $a->bookService->location, str_replace(["\r", "\n"], ' ', $a->findings ?? ''),
            $a->status, $a->assessedBy->name ?? 'N/A', $a->created_at->format('Y-m-d H:i'),
        ]);
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($rows as $row) fputcsv($csv, $row);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        return response()->streamDownload(fn() => print $content, 'assessments.csv');
    }

    public function exportPdf()
    {
        $assessments = $this->exportQuery()->get();
        $pdf = Pdf::loadView('exports.assessments', compact('assessments'));
        return response()->streamDownload(fn() => print $pdf->output(), 'assessments.pdf');
    }

    private function exportQuery()
    {
        $query = Assessment::with(['bookService.user', 'assessedBy']);
        if (Auth::user()->isClient()) {
            $ids = [Auth::id()];
            if (Auth::user()->isCompany()) $ids = array_merge($ids, Auth::user()->companyUsers()->pluck('id')->toArray());
            $query->whereHas('bookService', fn($q) => $q->whereIn('user_id', $ids));
        } elseif (Auth::user()->isTechnician()) {
            $query->where('assessed_by', Auth::id());
        }
        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('findings', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%")
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
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M12 3a9 9 0 0 0-9 9v1h2.5a2.5 2.5 0 0 1 0 5H3v1a9 9 0 0 0 9 9h.5v-2.5a2.5 2.5 0 0 1 5 0V23h1a9 9 0 0 0 9-9v-1h-2.5a2.5 2.5 0 0 1 0-5H21v-1a9 9 0 0 0-9-9z"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Assessments</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Review assessment reports.</p>
        </div>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" wire:model.live.debounce="search" placeholder="Search assessments..."
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

            @forelse ($this->assessments as $assessment)
                <div class="group relative overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm transition-all duration-200 hover:shadow-md dark:hover:shadow-zinc-900/50">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-semibold text-sm shrink-0">
                                        {{ strtoupper(substr($assessment->bookService->service_type, 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 truncate capitalize">{{ $assessment->bookService->service_type }}</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $assessment->bookService->location }}</p>
                                        @if (!Auth::user()->isClient())
                                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Client: {{ $assessment->bookService->user->name }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-3 pl-13">
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed line-clamp-3">{{ $assessment->findings }}</p>
                                </div>

                                @if ($assessment->photos && count($assessment->photos) > 0)
                                    <div class="flex gap-2 mt-3 pl-13">
                                        @foreach ($assessment->photos as $photo)
                                            <img src="{{ asset('storage/' . $photo) }}" alt="" class="w-16 h-16 rounded-lg object-cover border border-zinc-100 dark:border-zinc-700">
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                                    {{ $assessment->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $assessment->status === 'completed' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    {{ $assessment->status }}
                                </span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $assessment->created_at->format('M d, Y') }}</span>
                                <flux:button href="{{ route('assessments.show', $assessment->book_service_id) }}" size="sm" variant="ghost" wire:navigate>View Details</flux:button>
                            </div>
                        </div>
                    </div>

                    @if ($assessment->quotation)
                        <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-700">
                            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                <span>Quotation generated — <strong>UGX {{ number_format($assessment->quotation->total, 2) }}</strong></span>
                                <flux:button href="{{ route('quotations.show', $assessment->book_service_id) }}" size="xs" variant="ghost" wire:navigate class="ml-auto">View</flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-16 px-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-1">{{ $this->search ? 'No matching results' : 'No assessments yet' }}</h3>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ $this->search ? 'Try a different search term.' : 'Assessments will appear here once a service has been assessed.' }}</p>
                </div>
            @endforelse

            @if ($this->assessments->hasPages())
                <div class="mt-6">{{ $this->assessments->links(data: ['scrollTo' => false]) }}</div>
            @endif
        </div>
    </div>
</div>

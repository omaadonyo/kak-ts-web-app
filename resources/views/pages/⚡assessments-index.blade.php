<?php

use App\Models\Assessment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Assessments')] class extends Component {

    #[Computed]
    public function assessments()
    {
        return Assessment::with(['bookService', 'assessedBy'])
            ->whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))
            ->latest()
            ->get();
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M12 3a9 9 0 0 0-9 9v1h2.5a2.5 2.5 0 0 1 0 5H3v1a9 9 0 0 0 9 9h.5v-2.5a2.5 2.5 0 0 1 5 0V23h1a9 9 0 0 0 9-9v-1h-2.5a2.5 2.5 0 0 1 0-5H21v-1a9 9 0 0 0-9-9z"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Assessments</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Review assessment reports for your service requests.</p>
        </div>

        <div class="w-full md:w-1/2 md:mx-auto space-y-6">

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
                            </div>
                        </div>

                        <div class="mt-3 pl-13">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed line-clamp-3">{{ $assessment->findings }}</p>
                        </div>

                        @if ($assessment->photos && count($assessment->photos) > 0)
                            <div class="flex gap-2 mt-3 pl-13">
                                @foreach ($assessment->photos as $photo)
                                    <img src="{{ asset('storage/' . $photo) }}" alt=""
                                         class="w-16 h-16 rounded-lg object-cover border border-zinc-100 dark:border-zinc-700">
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
                        <flux:button href="{{ route('assessments.show', $assessment->book_service_id) }}" size="sm" variant="ghost" wire:navigate>
                            View Details
                        </flux:button>
                    </div>
                </div>
            </div>

            @if ($assessment->quotation)
                <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-700">
                    <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        <span>Quotation generated — <strong>${{ number_format($assessment->quotation->total, 2) }}</strong></span>
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
            <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-1">No assessments yet</h3>
            <p class="text-sm text-zinc-400 dark:text-zinc-500 mb-4">Assessments will appear here once a service has been assessed.</p>
            <flux:button href="{{ route('book-services') }}" variant="primary" wire:navigate>View Service Requests</flux:button>
        </div>
    @endforelse
        </div>
    </div>
</div>

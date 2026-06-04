<?php

use App\Models\BookService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My Service Requests')] class extends Component {

    #[Computed]
    public function services()
    {
        return BookService::with(['user', 'assessment', 'quotation', 'project', 'invoice'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    public function delete(int $id): void
    {
        $service = BookService::where('user_id', Auth::id())->findOrFail($id);
        $service->delete();
        Flux::toast(variant: 'success', text: 'Service request deleted.');
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">My Service Requests</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">View and manage all your service requests.</p>
        </div>

        <div class="w-full md:w-1/2 md:mx-auto space-y-6">
            <div class="flex justify-end">
                <flux:button href="{{ route('book-service') }}" icon="plus" variant="primary" wire:navigate>
                    New Request
                </flux:button>
            </div>

            @forelse ($this->services as $service)
                @php
                    $nextStep = match(true) {
                        !$service->assessment => 'assessment',
                        !$service->quotation => 'quotation',
                        !$service->project => 'project',
                        !$service->invoice => 'invoice',
                        default => null,
                    };
                    $servicePhotos = $service->photos ? array_map(fn($p) => asset('storage/' . $p), $service->photos) : [];
                @endphp

                <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-700/50 bg-white dark:bg-zinc-800 shadow-sm transition-all duration-200 hover:shadow-md dark:hover:shadow-zinc-900/50">
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-zinc-800 dark:bg-zinc-600 text-white font-semibold text-sm shrink-0 capitalize">
                                        {{ substr($service->service_type, 0, 2) }}
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 capitalize">{{ $service->service_type }}</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $service->location }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 mt-1 ml-13">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $service->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($service->status === 'pending' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400') }}">
                                        <span class="w-1.5 h-1.5 rounded-full
                                            {{ $service->status === 'completed' ? 'bg-emerald-500' : ($service->status === 'pending' ? 'bg-amber-500' : 'bg-blue-500') }}"></span>
                                        {{ ucfirst($service->status) }}
                                    </span>
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $service->created_at->format('M d, Y') }}</span>
                                </div>

                                @if ($service->notes)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-3 ml-13 line-clamp-2">{{ $service->notes }}</p>
                                @endif
                            </div>

                            <div class="shrink-0 flex flex-col items-end gap-2">
                                @if ($nextStep)
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">Next: {{ ucfirst($nextStep) }}</span>
                                @endif

                                @if ($service->project)
                                    <div class="w-28">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-zinc-400 dark:text-zinc-500">Progress</span>
                                            <span class="font-medium text-zinc-600 dark:text-zinc-400">{{ $service->project->progress }}%</span>
                                        </div>
                                        <div class="w-full h-1.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-zinc-800 dark:bg-zinc-400 rounded-full" style="width: {{ $service->project->progress }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (count($servicePhotos) > 0)
                        <div class="px-6 pb-4">
                            <div x-data="{
                                activeSlide: 0,
                                images: @js($servicePhotos),
                                prev() { this.activeSlide = this.activeSlide === 0 ? this.images.length - 1 : this.activeSlide - 1; },
                                next() { this.activeSlide = this.activeSlide === this.images.length - 1 ? 0 : this.activeSlide + 1; }
                            }" class="space-y-2">
                                <div class="relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-700/50">
                                    <div class="w-full aspect-[16/9]">
                                        <template x-for="(src, i) in images" :key="i">
                                            <div x-show="activeSlide === i"
                                                 x-transition:enter="transition ease-out duration-300"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 class="w-full h-full">
                                                <img :src="src" alt="" class="w-full h-full object-contain" loading="lazy">
                                            </div>
                                        </template>
                                    </div>
                                    <button x-show="images.length > 1" @click="prev()"
                                            class="absolute left-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                    </button>
                                    <button x-show="images.length > 1" @click="next()"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-lg font-medium"
                                         x-show="images.length > 1"
                                         x-text="`${activeSlide + 1} / ${images.length}`"></div>
                                </div>
                                <div class="flex justify-center gap-1.5" x-show="images.length > 1">
                                    <template x-for="(_, i) in images" :key="'dot-'+i">
                                        <button @click="activeSlide = i"
                                                :class="activeSlide === i ? 'bg-zinc-800 dark:bg-zinc-200 w-5' : 'bg-zinc-300 dark:bg-zinc-600 w-2'"
                                                class="h-1.5 rounded-full transition-all duration-300"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-700 flex items-center gap-2 flex-wrap">
                        @if (!$service->assessment)
                            <flux:button href="{{ route('assessments.create', $service->id) }}" size="sm" variant="ghost" wire:navigate>Add Assessment</flux:button>
                        @else
                            <flux:button href="{{ route('assessments.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Assessment</flux:button>

                            @if (!$service->quotation)
                                <flux:button href="{{ route('quotations.create', $service->id) }}" size="sm" variant="ghost" wire:navigate>Generate Quotation</flux:button>
                            @else
                                <flux:button href="{{ route('quotations.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Quotation</flux:button>

                                @if ($service->project)
                                    <flux:button href="{{ route('projects.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Project</flux:button>

                                    @if (!$service->invoice)
                                        <flux:button href="{{ route('invoices.create', $service->id) }}" size="sm" variant="ghost" wire:navigate>Generate Invoice</flux:button>
                                    @else
                                        <flux:button href="{{ route('invoices.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Invoice</flux:button>
                                    @endif
                                @endif
                            @endif
                        @endif

                        @if (!$service->assessment && !$service->quotation && !$service->project)
                            <flux:button wire:click="delete({{ $service->id }})" size="sm" variant="danger" wire:confirm="Delete this request?" class="ml-auto">Delete</flux:button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-16 px-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-1">No service requests yet</h3>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500 mb-4">Book your first service to get started.</p>
                    <flux:button href="{{ route('book-service') }}" variant="primary" wire:navigate>Book a Service</flux:button>
                </div>
            @endforelse
        </div>
    </div>
</div>

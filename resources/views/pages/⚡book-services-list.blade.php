<?php

use App\Models\BookService;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Service Requests')] class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    protected $queryString = ['search', 'perPage', 'sortField', 'sortDirection'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function services()
    {
        $user = Auth::user();
        $query = BookService::with(['user', 'assignedTo', 'assessment', 'quotation', 'project', 'invoice']);

        if ($user->isClient()) {
            $ids = [$user->id];
            if ($user->isCompany()) $ids = array_merge($ids, $user->companyUsers()->pluck('id')->toArray());
            $query->whereIn('user_id', $ids);
        } elseif ($user->isTechnician()) {
            $query->where('assigned_to', $user->id);
        }

        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('service_type', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
                  ->orWhereHas('assignedTo', fn($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
    }

    public function delete(int $id): void
    {
        $service = BookService::findOrFail($id);
        if (Auth::user()->isClient() && $service->user_id !== Auth::id()) abort(403);
        $service->delete();
        Flux::toast(variant: 'success', text: 'Service request deleted.');
    }

    public function assign(int $id, int $technicianId): void
    {
        $this->authorize('assign-booking');
        BookService::where('id', $id)->update(['assigned_to' => $technicianId]);
        Flux::toast(variant: 'success', text: 'Technician assigned.');
    }

    public function exportCsv()
    {
        $services = $this->exportQuery()->get();
        $headers = ['ID', 'Client', 'Email', 'Phone', 'Service Type', 'Location', 'Status', 'Assigned To', 'Notes', 'Created At'];
        $rows = $services->map(fn($s) => [
            $s->id, $s->user->name, $s->user->email, $s->user->phone ?? '',
            $s->service_type, $s->location, $s->status,
            $s->assignedTo->name ?? 'Unassigned',
            str_replace(["\r", "\n"], ' ', $s->notes ?? ''),
            $s->created_at->format('Y-m-d H:i'),
        ]);
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($rows as $row) fputcsv($csv, $row);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        return response()->streamDownload(fn() => print $content, 'service-requests.csv');
    }

    public function exportPdf()
    {
        $services = $this->exportQuery()->get();
        $pdf = Pdf::loadView('exports.service-requests', compact('services'));
        return response()->streamDownload(fn() => print $pdf->output(), 'service-requests.pdf');
    }

    private function exportQuery()
    {
        $user = Auth::user();
        $query = BookService::with(['user', 'assignedTo']);
        if ($user->isClient()) {
            $ids = [$user->id];
            if ($user->isCompany()) $ids = array_merge($ids, $user->companyUsers()->pluck('id')->toArray());
            $query->whereIn('user_id', $ids);
        } elseif ($user->isTechnician()) {
            $query->where('assigned_to', $user->id);
        }
        if ($this->search) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('service_type', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%")
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        return $query->orderBy($this->sortField, $this->sortDirection);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">{{ auth()->user()->isClient() ? 'My Service Requests' : (auth()->user()->isTechnician() ? 'Assigned Services' : 'All Services') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">View and manage service requests.</p>
        </div>

        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" wire:model.live.debounce="search" placeholder="Search by service, location, client, technician..."
                           class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20 focus:border-zinc-900 dark:focus:border-zinc-400">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select wire:model.live="perPage" class="border border-zinc-200 dark:border-zinc-600 rounded-xl px-3 py-2.5 text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800 focus:outline-none">
                        <option value="10">10/page</option>
                        <option value="25">25/page</option>
                        <option value="50">50/page</option>
                        <option value="100">100/page</option>
                    </select>
                    <div class="flex gap-1">
                        <button wire:click="exportCsv" class="p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-zinc-500 dark:text-zinc-400" title="Export CSV">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        </button>
                        <button wire:click="exportPdf" class="p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-zinc-500 dark:text-zinc-400" title="Export PDF">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </button>
                    </div>
                    @can('book-service')
                        <flux:button href="{{ route('book-service') }}" icon="plus" variant="primary" wire:navigate>New Request</flux:button>
                    @endcan
                </div>
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

                                @if (!auth()->user()->isClient())
                                    <div class="flex items-center gap-3 mt-2 ml-13 text-xs text-zinc-400 dark:text-zinc-500">
                                        <span>Client: <strong class="text-zinc-600 dark:text-zinc-300">{{ $service->user->name }}</strong></span>
                                        @if ($service->assignedTo)
                                            <span>Tech: <strong class="text-zinc-600 dark:text-zinc-300">{{ $service->assignedTo->name }}</strong></span>
                                        @elseif (auth()->user()->isAdmin())
                                            <span class="text-amber-500">Unassigned</span>
                                        @endif
                                    </div>
                                @endif

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
                                            <div x-show="activeSlide === i" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="w-full h-full">
                                                <img :src="src" alt="" class="w-full h-full object-contain" loading="lazy">
                                            </div>
                                        </template>
                                    </div>
                                    <button x-show="images.length > 1" @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                    </button>
                                    <button x-show="images.length > 1" @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                    <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-lg font-medium" x-show="images.length > 1" x-text="`${activeSlide + 1} / ${images.length}`"></div>
                                </div>
                                <div class="flex justify-center gap-1.5" x-show="images.length > 1">
                                    <template x-for="(_, i) in images" :key="'dot-'+i">
                                        <button @click="activeSlide = i" :class="activeSlide === i ? 'bg-zinc-800 dark:bg-zinc-200 w-5' : 'bg-zinc-300 dark:bg-zinc-600 w-2'" class="h-1.5 rounded-full transition-all duration-300"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-100 dark:border-zinc-700 flex items-center gap-2 flex-wrap">
                        @canany(['assess-booking', 'generate-quotation'])
                            @if (!$service->assessment)
                                <flux:button href="{{ route('assessments.create', $service->id) }}" size="sm" variant="ghost" wire:navigate>Add Assessment</flux:button>
                            @else
                                <flux:button href="{{ route('assessments.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Assessment</flux:button>
                                @if (!$service->quotation)
                                    <flux:button href="{{ route('quotations.create', $service->id) }}" size="sm" variant="ghost" wire:navigate>Generate Quotation</flux:button>
                                @else
                                    <flux:button href="{{ route('quotations.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Quotation</flux:button>
                                @endif
                            @endif
                        @else
                            <flux:button href="{{ route('assessments.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>View Assessment</flux:button>
                            <flux:button href="{{ route('quotations.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>View Quotation</flux:button>
                        @endcanany

                        @if ($service->project)
                            <flux:button href="{{ route('projects.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Project</flux:button>
                            <flux:button href="{{ route('projects.report', $service->id) }}" size="sm" variant="ghost" wire:navigate>Report</flux:button>
                        @endif

                        @if ($service->invoice)
                            <flux:button href="{{ route('invoices.show', $service->id) }}" size="sm" variant="ghost" wire:navigate>Invoice</flux:button>
                        @endif

                        @can('assign-booking')
                            @if (!$service->assigned_to)
                                @php $techs = \App\Models\User::where('role', 'tech')->get(); @endphp
                                <div x-data="{ open: false, search: '', techs: @js($techs->map(fn($t) => ['id' => $t->id, 'name' => $t->name])->values()->toArray()), get filtered() { return this.techs.filter(t => t.name.toLowerCase().includes(this.search.toLowerCase())) } }" class="ml-auto relative">
                                    <button type="button" @click="open = !open" class="text-xs font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 transition-colors">Assign</button>
                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 z-50 mt-1 w-52 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-xl shadow-lg overflow-hidden">
                                        <div class="p-2 border-b border-zinc-100 dark:border-zinc-700">
                                            <input type="text" x-model="search" placeholder="Search..." class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-1.5 text-xs text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                                        </div>
                                        <div class="max-h-40 overflow-y-auto">
                                            <template x-for="tech in filtered" :key="tech.id">
                                                <button type="button" @click="$wire.assign({{ $service->id }}, tech.id); open = false; search = ''"
                                                        class="w-full text-left px-4 py-2 text-xs text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors"
                                                        x-text="tech.name">
                                                </button>
                                            </template>
                                            <div x-show="filtered.length === 0" class="px-4 py-4 text-center text-xs text-zinc-400 dark:text-zinc-500">No technicians found</div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endcan

                        @can('book-service')
                            @if (!$service->assessment && !$service->quotation && !$service->project)
                                <flux:button wire:click="delete({{ $service->id }})" size="sm" variant="danger" wire:confirm="Delete this request?" class="ml-auto">Delete</flux:button>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <div class="text-center py-16 px-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-100 dark:bg-zinc-700 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-1">{{ $this->search ? 'No matching results' : 'No service requests yet' }}</h3>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500 mb-4">{{ $this->search ? 'Try a different search term.' : 'Book your first service to get started.' }}</p>
                    @if (!$this->search)
                        <flux:button href="{{ route('book-service') }}" variant="primary" wire:navigate>Book a Service</flux:button>
                    @endif
                </div>
            @endforelse

            @if ($this->services->hasPages())
                <div class="mt-6">
                    {{ $this->services->links(data: ['scrollTo' => false]) }}
                </div>
            @endif
        </div>
    </div>
</div>

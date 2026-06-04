<?php

use App\Models\Assessment;
use App\Models\BookService;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {

    #[Computed]
    public function totalServices() { return BookService::where('user_id', Auth::id())->count(); }

    #[Computed]
    public function pendingServices() { return BookService::where('user_id', Auth::id())->where('status', 'pending')->count(); }

    #[Computed]
    public function activeServices() { return BookService::where('user_id', Auth::id())->whereIn('status', ['in_progress', 'pending'])->count(); }

    #[Computed]
    public function completedServices() { return BookService::where('user_id', Auth::id())->where('status', 'completed')->count(); }

    #[Computed]
    public function totalAssessments() { return Assessment::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->count(); }

    #[Computed]
    public function totalQuotations() { return Quotation::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->count(); }

    #[Computed]
    public function acceptedQuotations() { return Quotation::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->where('status', 'accepted')->count(); }

    #[Computed]
    public function totalProjects() { return Project::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->count(); }

    #[Computed]
    public function inProgressProjects() { return Project::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->where('status', 'in_progress')->count(); }

    #[Computed]
    public function completedProjects() { return Project::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->where('status', 'completed')->count(); }

    #[Computed]
    public function totalInvoices() { return Invoice::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->count(); }

    #[Computed]
    public function paidInvoices() { return Invoice::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))->where('status', 'paid')->count(); }

    #[Computed]
    public function totalRevenue()
    {
        return Invoice::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'paid')
            ->sum('total');
    }

    #[Computed]
    public function outstandingRevenue()
    {
        return Invoice::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))
            ->whereIn('status', ['draft', 'sent'])
            ->sum('total');
    }

    #[Computed]
    public function serviceTypeBreakdown()
    {
        return BookService::where('user_id', Auth::id())
            ->selectRaw('service_type, count(*) as count')
            ->groupBy('service_type')
            ->pluck('count', 'service_type')
            ->toArray();
    }

    #[Computed]
    public function servicesByStatus()
    {
        return BookService::where('user_id', Auth::id())
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    #[Computed]
    public function recentServices()
    {
        return BookService::with(['assessment', 'quotation', 'project'])
            ->where('user_id', Auth::id())
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function monthlyRevenue()
    {
        return Invoice::whereHas('bookService', fn($q) => $q->where('user_id', Auth::id()))
            ->where('status', 'paid')
            ->selectRaw("strftime('%Y-%m', created_at) as month, sum(total) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Dashboard</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1">Overview of your service requests and business activity.</p>
            </div>
            <flux:button href="{{ route('book-service') }}" icon="plus" variant="primary" wire:navigate class="hidden sm:inline-flex">
                New Service Request
            </flux:button>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Services</span>
                    <div class="w-9 h-9 rounded-xl bg-zinc-900 dark:bg-zinc-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->totalServices }}</p>
                <div class="flex items-center gap-2 mt-1 text-xs">
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ $this->completedServices }} completed</span>
                    <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
                    <span class="text-zinc-400 dark:text-zinc-500">{{ $this->pendingServices }} pending</span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Assessments</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M12 3a9 9 0 0 0-9 9v1h2.5a2.5 2.5 0 0 1 0 5H3v1a9 9 0 0 0 9 9h.5v-2.5a2.5 2.5 0 0 1 5 0V23h1a9 9 0 0 0 9-9v-1h-2.5a2.5 2.5 0 0 1 0-5H21v-1a9 9 0 0 0-9-9z"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->totalAssessments }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Completed reports</p>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Quotations</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->totalQuotations }}</p>
                <div class="flex items-center gap-2 mt-1 text-xs">
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ $this->acceptedQuotations }} accepted</span>
                    <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
                    <span class="text-zinc-400 dark:text-zinc-500">{{ $this->totalQuotations - $this->acceptedQuotations }} pending</span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Projects</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $this->totalProjects }}</p>
                <div class="flex items-center gap-2 mt-1 text-xs">
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ $this->completedProjects }} done</span>
                    <span class="text-zinc-300 dark:text-zinc-600">&middot;</span>
                    <span class="text-zinc-400 dark:text-zinc-500">{{ $this->inProgressProjects }} active</span>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-5 hover:shadow-md transition-shadow col-span-2 md:col-span-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Revenue</span>
                    <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">${{ number_format($this->totalRevenue, 0) }}</p>
                <div class="flex items-center gap-2 mt-1 text-xs">
                    @if ($this->outstandingRevenue > 0)
                        <span class="text-amber-600 dark:text-amber-400 font-medium">${{ number_format($this->outstandingRevenue, 0) }} outstanding</span>
                    @else
                        <span class="text-zinc-400 dark:text-zinc-500">All paid</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Service Type Breakdown --}}
            @php $breakdown = $this->serviceTypeBreakdown; @endphp
            @if (count($breakdown) > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Services by Type</h3>
                    @php
                        $maxVal = max($breakdown);
                        $colors = [
                            'plumbing' => 'bg-blue-500 dark:bg-blue-400',
                            'electricals' => 'bg-amber-500 dark:bg-amber-400',
                            'carpentry' => 'bg-emerald-500 dark:bg-emerald-400',
                        ];
                        $labels = ['plumbing' => 'Plumbing', 'electricals' => 'Electricals', 'carpentry' => 'Carpentry'];
                        $icons = [
                            'plumbing' => '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a8 8 0 0 0 8-8c0-4.42-3.58-8-8-8-3.5 0-6.5 2.25-7.5 5.5C3.5 14 6 16.5 9.5 16.5c2 0 3.75-.83 5-2.17"/><path d="M9.5 16.5c-1.5 0-2.5-1-3-2"/><path d="M12 6v2"/><path d="M14 8h-4"/></svg>',
                            'electricals' => '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1-2 1-1 1.5-2.5 1.5-3.5C17.5 5.5 15 3 12 3S6.5 5.5 6.5 8.5c0 1 .5 2.5 1.5 3.5.3.3.8 1 1 2"/><path d="M9 14h6"/><path d="M12 14v7"/></svg>',
                            'carpentry' => '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 14H9l-3 4h12l-3-4Z"/><path d="M9.5 14 12 4 14.5 14"/><path d="M13 14v2"/><path d="M11 14v2"/></svg>',
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach ($breakdown as $type => $count)
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center text-zinc-500 dark:text-zinc-400 shrink-0">
                                    {!! $icons[$type] ?? '' !!}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 capitalize">{{ $labels[$type] ?? $type }}</span>
                                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $count }}</span>
                                    </div>
                                    <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $colors[$type] ?? 'bg-zinc-800' }}" style="width: {{ $maxVal > 0 ? ($count / $maxVal) * 100 : 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Status Distribution --}}
            @php $statusCounts = $this->servicesByStatus; @endphp
            @if (count($statusCounts) > 0)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Service Status Overview</h3>
                    @php
                        $statusColors = [
                            'pending' => 'bg-amber-400 dark:bg-amber-500',
                            'in_progress' => 'bg-blue-500 dark:bg-blue-400',
                            'completed' => 'bg-emerald-500 dark:bg-emerald-400',
                        ];
                        $total = array_sum($statusCounts);
                    @endphp
                    <div class="flex h-3 rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-700 mb-5">
                        @foreach ($statusCounts as $status => $count)
                            @php $pct = $total > 0 ? ($count / $total) * 100 : 0; @endphp
                            @if ($pct > 0)
                                <div class="{{ $statusColors[$status] ?? 'bg-zinc-400' }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="space-y-2">
                        @foreach ($statusCounts as $status => $count)
                            @php $pct = $total > 0 ? round(($count / $total) * 100) : 0; @endphp
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $statusColors[$status] ?? 'bg-zinc-400' }}"></span>
                                    <span class="text-zinc-600 dark:text-zinc-400 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                                </div>
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $count }} <span class="text-zinc-400 dark:text-zinc-500 font-normal">({{ $pct }}%)</span></span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Bottom Row: Recent Activity + Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Recent Activity --}}
            <div class="lg:col-span-2 bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Recent Service Requests</h3>
                    <flux:button href="{{ route('book-services') }}" size="sm" variant="ghost" wire:navigate>View All</flux:button>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($this->recentServices as $service)
                        <div class="px-6 py-4 flex items-center gap-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition-colors">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 font-semibold text-xs capitalize shrink-0">
                                {{ substr($service->service_type, 0, 2) }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100 capitalize">{{ $service->service_type }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                                        {{ $service->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($service->status === 'pending' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400') }}">
                                        {{ ucfirst($service->status) }}
                                    </span>
                                </div>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">{{ $service->location }} &middot; {{ $service->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if (!$service->assessment)
                                    <flux:button href="{{ route('assessments.create', $service->id) }}" size="xs" variant="ghost" wire:navigate>Assess</flux:button>
                                @elseif (!$service->quotation)
                                    <flux:button href="{{ route('quotations.create', $service->id) }}" size="xs" variant="ghost" wire:navigate>Quote</flux:button>
                                @elseif (!$service->project)
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">Awaiting</span>
                                @else
                                    <flux:button href="{{ route('projects.show', $service->id) }}" size="xs" variant="ghost" wire:navigate>View</flux:button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-zinc-100 dark:bg-zinc-700 mb-3">
                                <svg class="w-6 h-6 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            </div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No service requests yet.</p>
                            <flux:button href="{{ route('book-service') }}" size="sm" variant="primary" wire:navigate class="mt-3">Book Your First Service</flux:button>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('book-service') }}" wire:navigate
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-zinc-900 dark:bg-zinc-100 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Book a Service</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Request a new service</p>
                        </div>
                    </a>
                    <a href="{{ route('book-services') }}" wire:navigate
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">View Requests</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Manage your service requests</p>
                        </div>
                    </a>
                    <a href="{{ route('assessments.index') }}" wire:navigate
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M12 3a9 9 0 0 0-9 9v1h2.5a2.5 2.5 0 0 1 0 5H3v1a9 9 0 0 0 9 9h.5v-2.5a2.5 2.5 0 0 1 5 0V23h1a9 9 0 0 0 9-9v-1h-2.5a2.5 2.5 0 0 1 0-5H21v-1a9 9 0 0 0-9-9z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Assessments</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Review assessment reports</p>
                        </div>
                    </a>
                    <a href="{{ route('quotations.index') }}" wire:navigate
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Quotations</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Manage quotations</p>
                        </div>
                    </a>
                    <a href="{{ route('invoices.index') }}" wire:navigate
                       class="flex items-center gap-3 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center group-hover:scale-105 transition-transform">
                            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Invoices</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Track invoices &amp; payments</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

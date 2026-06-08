<?php

use App\Models\BookService;
use App\Models\Invoice;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sales Reports')] class extends Component {

    public string $period = 'all';

    #[Computed]
    public function dateRange()
    {
        return match ($this->period) {
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            default => [null, null],
        };
    }

    #[Computed]
    public function totalServices()
    {
        [$from, $to] = $this->dateRange;
        return BookService::when($from, fn($q) => $q->whereBetween('created_at', [$from, $to]))->count();
    }

    #[Computed]
    public function completedServices()
    {
        [$from, $to] = $this->dateRange;
        return BookService::whereHas('invoice', fn($q) => $q->where('status', 'paid'))
            ->when($from, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->count();
    }

    #[Computed]
    public function totalRevenue()
    {
        [$from, $to] = $this->dateRange;
        return Invoice::where('status', 'paid')
            ->when($from, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->sum('total');
    }

    #[Computed]
    public function outstandingAmount()
    {
        return Invoice::whereIn('status', ['draft', 'sent'])->sum('total');
    }

    #[Computed]
    public function totalPayments()
    {
        [$from, $to] = $this->dateRange;
        return Payment::where('status', 'completed')
            ->when($from, fn($q) => $q->whereBetween('paid_at', [$from, $to]))
            ->sum('amount');
    }

    #[Computed]
    public function serviceTypeBreakdown()
    {
        [$from, $to] = $this->dateRange;
        return BookService::selectRaw('service_type, count(*) as count, sum(case when id in (select book_service_id from invoices where status = ?) then 1 else 0 end) as paid', ['paid'])
            ->when($from, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('service_type')
            ->orderByDesc('count')
            ->get();
    }

    #[Computed]
    public function monthlyBreakdown()
    {
        [$from, $to] = $this->dateRange;
        return BookService::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as services, sum(case when id in (select book_service_id from invoices where status = 'paid') then 1 else 0 end) as completed")
            ->when($from, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');
    }

    #[Computed]
    public function topClients()
    {
        [$from, $to] = $this->dateRange;
        return BookService::selectRaw('user_id, count(*) as total_services, sum(case when id in (select book_service_id from invoices where status = ?) then 1 else 0 end) as paid_services', ['paid'])
            ->with('user')
            ->when($from, fn($q) => $q->whereBetween('created_at', [$from, $to]))
            ->groupBy('user_id')
            ->orderByDesc('total_services')
            ->take(5)
            ->get();
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-zinc-50 via-white to-zinc-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 py-8 md:py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Sales Reports</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1">Revenue and service performance overview.</p>
            </div>
            <select wire:model.live="period" class="self-start rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-violet-500/20">
                <option value="all">All Time</option>
                <option value="year">This Year</option>
                <option value="quarter">This Quarter</option>
                <option value="month">This Month</option>
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-violet-800 shadow-lg shadow-violet-600/20 p-6">
                <div class="absolute top-0 right-0 w-32 h-32 translate-x-8 -translate-y-8">
                    <div class="w-full h-full rounded-full bg-white/10"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-violet-100/80 text-xs font-semibold uppercase tracking-wider mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                        Total Services
                    </div>
                    <p class="text-3xl font-bold text-white">{{ number_format($this->totalServices) }}</p>
                    <p class="text-violet-200/70 text-sm mt-1">{{ number_format($this->completedServices) }} completed</p>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 shadow-lg shadow-emerald-600/20 p-6">
                <div class="absolute top-0 right-0 w-32 h-32 translate-x-8 -translate-y-8">
                    <div class="w-full h-full rounded-full bg-white/10"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-emerald-100/80 text-xs font-semibold uppercase tracking-wider mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Revenue
                    </div>
                    <p class="text-3xl font-bold text-white">UGX {{ number_format($this->totalRevenue, 0) }}</p>
                    <p class="text-emerald-200/70 text-sm mt-1">From paid invoices</p>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-700 shadow-lg shadow-amber-600/20 p-6">
                <div class="absolute top-0 right-0 w-32 h-32 translate-x-8 -translate-y-8">
                    <div class="w-full h-full rounded-full bg-white/10"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-amber-100/80 text-xs font-semibold uppercase tracking-wider mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Payments Received
                    </div>
                    <p class="text-3xl font-bold text-white">UGX {{ number_format($this->totalPayments, 0) }}</p>
                    <p class="text-amber-200/70 text-sm mt-1">Completed transactions</p>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-600 to-rose-800 shadow-lg shadow-rose-600/20 p-6">
                <div class="absolute top-0 right-0 w-32 h-32 translate-x-8 -translate-y-8">
                    <div class="w-full h-full rounded-full bg-white/10"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-rose-100/80 text-xs font-semibold uppercase tracking-wider mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Outstanding
                    </div>
                    <p class="text-3xl font-bold text-white">UGX {{ number_format($this->outstandingAmount, 0) }}</p>
                    <p class="text-rose-200/70 text-sm mt-1">Unpaid invoices</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-5">Monthly Performance</h3>
                @php $months = $this->monthlyBreakdown; @endphp
                @if (count($months) > 0)
                    @php $maxServices = max($months->pluck('services')->toArray()); @endphp
                    <div class="space-y-3">
                        @foreach ($months as $month => $data)
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 w-16 shrink-0 font-medium">{{ $month }}</span>
                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-violet-600" style="width: {{ $maxServices > 0 ? ($data['services'] / $maxServices) * 100 : 0 }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 w-8 text-right">{{ $data['services'] }}</span>
                                    </div>
                                    @if ($data['completed'] > 0)
                                        <div class="flex items-center gap-2 pl-2">
                                            <div class="flex-1 h-1.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500" style="width: {{ $data['completed'] / $data['services'] * 100 }}%"></div>
                                            </div>
                                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 w-8 text-right font-medium">{{ $data['completed'] }} done</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-400 text-center py-8">No service data for this period.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-5">Service Type Breakdown</h3>
                @php $types = $this->serviceTypeBreakdown; @endphp
                @if (count($types) > 0)
                    @php $maxType = max($types->pluck('count')->toArray()); @endphp
                    <div class="space-y-4">
                        @foreach ($types as $type)
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <span class="text-zinc-700 dark:text-zinc-300 capitalize font-medium">{{ $type->service_type }}</span>
                                    <span class="text-xs text-zinc-400">{{ $type->count }} services</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-cyan-500" style="width: {{ $maxType > 0 ? ($type->count / $maxType) * 100 : 0 }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium shrink-0">{{ $type->paid }} paid</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-400 text-center py-8">No services yet.</p>
                @endif
            </div>
        </div>

        @php $clients = $this->topClients; @endphp
        @if (count($clients) > 0)
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Top Clients</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/30 text-zinc-500 dark:text-zinc-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-6 py-3">Client</th>
                            <th class="text-center px-6 py-3">Services</th>
                            <th class="text-center px-6 py-3">Completed</th>
                            <th class="text-right px-6 py-3">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($clients as $client)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-zinc-700 to-zinc-900 text-white text-xs font-semibold">{{ $client->user?->initials() ?? '--' }}</span>
                                        <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $client->user?->name ?? 'Deleted' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-semibold text-zinc-800 dark:text-zinc-100">{{ $client->total_services }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $client->paid_services > 0 ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400' }}">
                                        {{ $client->paid_services }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-semibold {{ $client->total_services > 0 && ($client->paid_services / $client->total_services) >= 0.5 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ $client->total_services > 0 ? round(($client->paid_services / $client->total_services) * 100) : 0 }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

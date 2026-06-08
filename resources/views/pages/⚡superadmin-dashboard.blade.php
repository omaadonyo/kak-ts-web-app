<?php

use App\Models\BookService;
use App\Models\Project;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Super Admin Dashboard')] class extends Component {
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function totalUsers() { return User::count(); }
    #[Computed]
    public function totalAdmins() { return User::where('role', 'admin')->count(); }
    #[Computed]
    public function totalTechnicians() { return User::where('role', 'technician')->count(); }
    #[Computed]
    public function totalClients() { return User::where('role', 'client')->count(); }
    #[Computed]
    public function totalServices() { return BookService::count(); }
    #[Computed]
    public function assignedServices() { return BookService::whereNotNull('assigned_to')->count(); }
    #[Computed]
    public function unassignedServices() { return BookService::whereNull('assigned_to')->count(); }

    #[Computed]
    public function serviceStatusBreakdown()
    {
        return BookService::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->pluck('count', 'status')
            ->toArray();
    }

    #[Computed]
    public function technicianPerformance()
    {
        return User::where('role', 'technician')
            ->withCount(['assignedServices'])
            ->withCount(['assignedServices as assessed_count' => fn($q) => $q->whereHas('assessment')])
            ->withCount(['assignedServices as quoted_count' => fn($q) => $q->whereHas('quotation')])
            ->withCount(['assignedServices as project_count' => fn($q) => $q->whereHas('project')])
            ->withCount(['assignedServices as completed_count' => fn($q) => $q->whereHas('invoice', fn($q) => $q->where('status', 'paid'))])
            ->orderByDesc('assigned_services_count')
            ->get();
    }

    #[Computed]
    public function unassignedTechnicians()
    {
        return User::where('role', 'technician')
            ->whereDoesntHave('assignedServices')
            ->get();
    }

    #[Computed]
    public function dueProjects()
    {
        return Project::where('status', '!=', 'completed')
            ->where('created_at', '<', now()->subDays(14))
            ->with('bookService.user', 'bookService.assignedTo')
            ->orderBy('created_at')
            ->take(10)
            ->get();
    }

    #[Computed]
    public function overdueProjects()
    {
        return Project::where('status', '!=', 'completed')
            ->where('created_at', '<', now()->subDays(30))
            ->with('bookService.user', 'bookService.assignedTo')
            ->orderBy('created_at')
            ->take(10)
            ->get();
    }

    #[Computed]
    public function usersByRole()
    {
        return User::selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-zinc-50 via-white to-zinc-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 py-8 md:py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Super Admin</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1">Platform overview and operational metrics.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-sky-600 to-sky-800 shadow-lg shadow-sky-600/20 p-5">
                <div class="absolute top-0 right-0 w-24 h-24 translate-x-6 -translate-y-6 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-sky-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Users</div>
                    <p class="text-2xl font-bold text-white">{{ $this->totalUsers }}</p>
                    <div class="flex gap-2 mt-1 text-[10px] text-sky-200/70">
                        <span>{{ $this->totalAdmins }} admin</span>
                        <span>&middot;</span>
                        <span>{{ $this->totalTechnicians }} tech</span>
                        <span>&middot;</span>
                        <span>{{ $this->totalClients }} client</span>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-violet-800 shadow-lg shadow-violet-600/20 p-5">
                <div class="absolute top-0 right-0 w-24 h-24 translate-x-6 -translate-y-6 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-violet-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Services</div>
                    <p class="text-2xl font-bold text-white">{{ $this->totalServices }}</p>
                    <div class="flex gap-2 mt-1 text-[10px] text-violet-200/70">
                        <span>{{ $this->assignedServices }} assigned</span>
                        <span>&middot;</span>
                        <span>{{ $this->unassignedServices }} unassigned</span>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 shadow-lg shadow-emerald-600/20 p-5">
                <div class="absolute top-0 right-0 w-24 h-24 translate-x-6 -translate-y-6 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-emerald-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Technicians</div>
                    <p class="text-2xl font-bold text-white">{{ $this->totalTechnicians }}</p>
                    <div class="flex gap-2 mt-1 text-[10px] text-emerald-200/70">
                        <span>{{ $this->technicianPerformance->count() }} active</span>
                        <span>&middot;</span>
                        <span>{{ $this->unassignedTechnicians->count() }} idle</span>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-700 shadow-lg shadow-amber-600/20 p-5">
                <div class="absolute top-0 right-0 w-24 h-24 translate-x-6 -translate-y-6 rounded-full bg-white/10"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 text-amber-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Projects</div>
                    <p class="text-2xl font-bold text-white">{{ $this->dueProjects->count() + $this->overdueProjects->count() }}</p>
                    <div class="flex gap-2 mt-1 text-[10px] text-amber-200/70">
                        <span>{{ $this->dueProjects->count() - $this->overdueProjects->count() }} due</span>
                        <span>&middot;</span>
                        <span class="font-semibold text-white">{{ $this->overdueProjects->count() }} overdue</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-5">Users by Role</h3>
                @php $roleCounts = $this->usersByRole; @endphp
                @if (count($roleCounts) > 0)
                    @php
                        $maxRole = max($roleCounts);
                        $colors = ['superadmin' => 'from-purple-500 to-purple-600', 'admin' => 'from-blue-500 to-blue-600', 'technician' => 'from-amber-500 to-orange-600', 'client' => 'from-emerald-500 to-emerald-600'];
                        $icons = ['superadmin' => 'shield-check', 'admin' => 'cog', 'technician' => 'wrench', 'client' => 'user'];
                    @endphp
                    <div class="space-y-4">
                        @foreach ($roleCounts as $role => $count)
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <span class="text-zinc-700 dark:text-zinc-300 capitalize font-medium">{{ $role }}</span>
                                    <span class="text-xs text-zinc-400">{{ $count }}</span>
                                </div>
                                <div class="h-2.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r {{ $colors[$role] ?? 'from-zinc-500 to-zinc-600' }}" style="width: {{ $maxRole > 0 ? ($count / $maxRole) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-400 text-center py-8">No users.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-5">Service Status</h3>
                @php $statuses = $this->serviceStatusBreakdown; @endphp
                @if (count($statuses) > 0)
                    @php
                        $maxStatus = max($statuses);
                        $statusColors = ['pending' => 'from-amber-400 to-amber-600', 'in_progress' => 'from-blue-400 to-blue-600', 'completed' => 'from-emerald-400 to-emerald-600', 'cancelled' => 'from-rose-400 to-rose-600'];
                    @endphp
                    <div class="space-y-4">
                        @foreach ($statuses as $status => $count)
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1.5">
                                    <span class="text-zinc-700 dark:text-zinc-300 capitalize font-medium">{{ str_replace('_', ' ', $status) }}</span>
                                    <span class="text-xs text-zinc-400">{{ $count }}</span>
                                </div>
                                <div class="h-2.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r {{ $statusColors[$status] ?? 'from-zinc-400 to-zinc-600' }}" style="width: {{ $maxStatus > 0 ? ($count / $maxStatus) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-400 text-center py-8">No services yet.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6">
                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-5">Idle Technicians</h3>
                @php $idleTechs = $this->unassignedTechnicians; @endphp
                @if (count($idleTechs) > 0)
                    <div class="space-y-3">
                        @foreach ($idleTechs as $tech)
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/20">
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-amber-200 dark:bg-amber-800 text-amber-700 dark:text-amber-300 text-xs font-semibold">{{ $tech->initials() }}</span>
                                <div>
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $tech->name }}</p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400">No active assignments</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400 mb-2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <p class="text-sm text-zinc-400">All technicians are assigned.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Technician Performance --}}
        @php $techs = $this->technicianPerformance; @endphp
        @if (count($techs) > 0)
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Technician Performance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-700/30 text-zinc-500 dark:text-zinc-400 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="text-left px-6 py-3">Technician</th>
                                <th class="text-center px-6 py-3">Assigned</th>
                                <th class="text-center px-6 py-3">Assessed</th>
                                <th class="text-center px-6 py-3">Quoted</th>
                                <th class="text-center px-6 py-3">In Project</th>
                                <th class="text-center px-6 py-3">Completed</th>
                                <th class="text-right px-6 py-3">Efficiency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($techs as $tech)
                                @php $efficiency = $tech->assigned_services_count > 0 ? round(($tech->completed_count / $tech->assigned_services_count) * 100) : 0; @endphp
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-white text-xs font-semibold">{{ $tech->initials() }}</span>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $tech->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-zinc-800 dark:text-zinc-100">{{ $tech->assigned_services_count }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tech->assessed_count > 0 ? 'bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500' }}">{{ $tech->assessed_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tech->quoted_count > 0 ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500' }}">{{ $tech->quoted_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tech->project_count > 0 ? 'bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500' }}">{{ $tech->project_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tech->completed_count > 0 ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500' }}">{{ $tech->completed_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="w-16 h-1.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full {{ $efficiency >= 50 ? 'bg-emerald-500' : ($efficiency >= 25 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $efficiency }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold {{ $efficiency >= 50 ? 'text-emerald-600 dark:text-emerald-400' : ($efficiency >= 25 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') }}">{{ $efficiency }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Due Projects --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Due Projects</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">{{ count($this->dueProjects) }}</span>
                    </div>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($this->dueProjects as $project)
                        <div class="px-6 py-3 flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">{{ $project->name }}</p>
                                <p class="text-xs text-zinc-400">
                                    {{ $project->bookService?->user?->name ?? 'N/A' }}
                                    &middot; {{ $project->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="text-xs text-amber-600 dark:text-amber-400 font-medium shrink-0">{{ $project->created_at->diffInDays(now()) }}d</span>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-zinc-400">No due projects.</div>
                    @endforelse
                </div>
            </div>

            {{-- Overdue Projects --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Overdue Projects</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400">{{ count($this->overdueProjects) }}</span>
                    </div>
                </div>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse ($this->overdueProjects as $project)
                        <div class="px-6 py-3 flex items-center gap-3 bg-rose-50/30 dark:bg-rose-900/5">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100 truncate">{{ $project->name }}</p>
                                <p class="text-xs text-zinc-400">
                                    {{ $project->bookService?->user?->name ?? 'N/A' }}
                                    &middot; {{ $project->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="text-xs text-rose-600 dark:text-rose-400 font-medium shrink-0">{{ $project->created_at->diffInDays(now()) }}d</span>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-zinc-400">No overdue projects. All on track!</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

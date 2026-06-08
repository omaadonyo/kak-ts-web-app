<?php

use App\Models\UserLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('User Activity Logs')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $actionFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    #[Computed]
    public function logs()
    {
        return UserLog::with('user')
            ->when($this->search, fn($q) => $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$this->search}%")))
            ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function uniqueActions()
    {
        return UserLog::select('action')->distinct()->pluck('action');
    }

    #[Computed]
    public function loginCount()
    {
        return UserLog::where('action', 'login')->count();
    }

    #[Computed]
    public function logoutCount()
    {
        return UserLog::where('action', 'logout')->count();
    }

    #[Computed]
    public function activeToday()
    {
        return UserLog::whereDate('created_at', today())
            ->distinct('user_id')
            ->count('user_id');
    }

    #[Computed]
    public function sessionDurations()
    {
        $logins = UserLog::where('action', 'login')
            ->whereNotNull('user_id')
            ->latest()
            ->take(50)
            ->get();

        $durations = [];
        foreach ($logins as $login) {
            $logout = UserLog::where('user_id', $login->user_id)
                ->where('action', 'logout')
                ->where('created_at', '>', $login->created_at)
                ->first();
            if ($logout) {
                $durations[] = $login->created_at->diffInMinutes($logout->created_at);
            }
        }
        return $durations;
    }

    public function clearAll()
    {
        UserLog::truncate();
    }

    public function getAvgSessionProperty()
    {
        $durations = $this->sessionDurations;
        return count($durations) > 0 ? round(array_sum($durations) / count($durations)) : 0;
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-zinc-50 via-white to-zinc-50 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 py-8 md:py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Activity Logs</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1">User login, logout, and platform activity tracking.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 shadow-lg shadow-emerald-600/20 p-5">
                <div class="relative">
                    <div class="text-emerald-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Logins</div>
                    <p class="text-2xl font-bold text-white">{{ number_format($this->loginCount) }}</p>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-600 to-rose-800 shadow-lg shadow-rose-600/20 p-5">
                <div class="relative">
                    <div class="text-rose-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Logouts</div>
                    <p class="text-2xl font-bold text-white">{{ number_format($this->logoutCount) }}</p>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 to-violet-800 shadow-lg shadow-violet-600/20 p-5">
                <div class="relative">
                    <div class="text-violet-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Active Today</div>
                    <p class="text-2xl font-bold text-white">{{ $this->activeToday }}</p>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-600 to-cyan-800 shadow-lg shadow-cyan-600/20 p-5">
                <div class="relative">
                    <div class="text-cyan-100/80 text-xs font-semibold uppercase tracking-wider mb-1">Avg Session</div>
                    <p class="text-2xl font-bold text-white">{{ $this->avgSession > 0 ? $this->avgSession . 'm' : '--' }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <flux:input icon="magnifying-glass" placeholder="Search user..." class="max-w-xs" wire:model.live="search" />
            <select wire:model.live="actionFilter" class="rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2.5 shadow-sm">
                <option value="">All Actions</option>
                @foreach ($this->uniqueActions as $action)
                    <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                @endforeach
            </select>
            <input type="date" wire:model.live="dateFrom" class="rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2.5 shadow-sm">
            <span class="text-xs text-zinc-400">to</span>
            <input type="date" wire:model.live="dateTo" class="rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2.5 shadow-sm">
            <flux:button size="sm" variant="danger" wire:confirm="Clear all logs permanently?" wire:click="clearAll" class="ml-auto">Clear All</flux:button>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/30 text-zinc-500 dark:text-zinc-400 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-6 py-3">User</th>
                            <th class="text-left px-6 py-3">Action</th>
                            <th class="text-left px-6 py-3">Description</th>
                            <th class="text-left px-6 py-3">IP Address</th>
                            <th class="text-left px-6 py-3">Device</th>
                            <th class="text-left px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach ($this->logs as $log)
                            @php
                                $actionColors = [
                                    'login' => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 ring-emerald-600/20',
                                    'logout' => 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 ring-rose-600/20',
                                    'created' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 ring-blue-600/20',
                                    'updated' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 ring-amber-600/20',
                                    'deleted' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 ring-red-600/20',
                                ];
                                $badgeColor = $actionColors[$log->action] ?? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 ring-zinc-500/20';
                                $ua = $log->user_agent ?? '';
                                $isMobile = str_contains($ua, 'Mobile') || str_contains($ua, 'Android');
                            @endphp
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $log->action === 'login' ? 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : ($log->action === 'logout' ? 'bg-rose-100 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400' : 'bg-zinc-100 dark:bg-zinc-700') }} text-xs font-semibold">
                                            {{ $log->user?->initials() ?? '--' }}
                                        </span>
                                        <div>
                                            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $log->user?->name ?? 'System' }}</span>
                                            @if ($log->user)
                                                <span class="text-xs text-zinc-400 block">{{ $log->user->role }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset {{ $badgeColor }}">
                                        @if ($log->action === 'login')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                                        @elseif ($log->action === 'logout')
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                        @endif
                                        {{ ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400 max-w-xs truncate text-xs">{{ $log->description ?? '-' }}</td>
                                <td class="px-6 py-3">
                                    <span class="text-xs text-zinc-400 font-mono bg-zinc-50 dark:bg-zinc-700/30 px-2 py-1 rounded">{{ $log->ip_address ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-3 text-xs text-zinc-500">
                                    @if ($ua)
                                        <span class="inline-flex items-center gap-1">
                                            @if ($isMobile)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                            @endif
                                            {{ Str::limit($ua, 40) }}
                                        </span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-600">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs text-zinc-500 whitespace-nowrap">
                                    <span class="block">{{ $log->created_at->format('d M Y') }}</span>
                                    <span class="text-zinc-400">{{ $log->created_at->format('H:i:s') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($this->logs->isEmpty())
                <div class="px-6 py-16 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600 mx-auto mb-3"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <p class="text-sm text-zinc-400">No activity logs recorded yet.</p>
                    <p class="text-xs text-zinc-500 mt-1">Login/logout events will appear here once users start interacting.</p>
                </div>
            @endif
        </div>

        <div class="mt-6">
            {{ $this->logs->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
</div>

<?php

use App\Models\BookService;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Reports')] class extends Component {

    #[Computed]
    public function totalServices()
    {
        return BookService::count();
    }

    #[Computed]
    public function totalProjects()
    {
        return Project::count();
    }

    #[Computed]
    public function totalRevenue()
    {
        return Invoice::where('status', 'paid')->sum('total');
    }

    #[Computed]
    public function pendingRevenue()
    {
        return Invoice::whereIn('status', ['sent', 'overdue'])->sum('total');
    }

    #[Computed]
    public function totalQuotations()
    {
        return Quotation::count();
    }

    #[Computed]
    public function acceptedQuotations()
    {
        return Quotation::where('status', 'accepted')->count();
    }

    #[Computed]
    public function servicesByStatus()
    {
        return [
            'pending' => BookService::where('status', 'pending')->count(),
            'in_progress' => BookService::where('status', 'in_progress')->count(),
            'completed' => BookService::where('status', 'completed')->count(),
        ];
    }

    #[Computed]
    public function invoicesByStatus()
    {
        return [
            'draft' => Invoice::where('status', 'draft')->count(),
            'sent' => Invoice::where('status', 'sent')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'overdue' => Invoice::where('status', 'overdue')->count(),
        ];
    }

    #[Computed]
    public function usersByRole()
    {
        return [
            'clients' => User::where('role', 'client')->count(),
            'technicians' => User::where('role', 'technician')->count(),
            'admins' => User::where('role', 'admin')->count(),
        ];
    }

    #[Computed]
    public function recentServices()
    {
        return BookService::with('user')->latest()->take(5)->get();
    }

    #[Computed]
    public function recentInvoices()
    {
        return Invoice::with('bookService.user')->latest()->take(5)->get();
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Reports</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Overview of all business metrics and activity.</p>
        </div>

        @can('manage-users')
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Services</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mt-1">{{ $this->totalServices }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Projects</p>
                    <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mt-1">{{ $this->totalProjects }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Revenue</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($this->totalRevenue, 2) }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Pending Revenue</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">${{ number_format($this->pendingRevenue, 2) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Services by Status</h3>
                    @php $sbs = $this->servicesByStatus; @endphp
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500 dark:text-zinc-400">Pending</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $sbs['pending'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full" style="width: {{ $this->totalServices > 0 ? ($sbs['pending'] / max($this->totalServices, 1)) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500 dark:text-zinc-400">In Progress</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $sbs['in_progress'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ $this->totalServices > 0 ? ($sbs['in_progress'] / max($this->totalServices, 1)) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500 dark:text-zinc-400">Completed</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $sbs['completed'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $this->totalServices > 0 ? ($sbs['completed'] / max($this->totalServices, 1)) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Invoices by Status</h3>
                    @php $ibs = $this->invoicesByStatus; @endphp
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500 dark:text-zinc-400">Draft</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ibs['draft'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-zinc-400 rounded-full" style="width: {{ array_sum($ibs) > 0 ? ($ibs['draft'] / max(array_sum($ibs), 1)) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500 dark:text-zinc-400">Sent</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ibs['sent'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" style="width: {{ array_sum($ibs) > 0 ? ($ibs['sent'] / max(array_sum($ibs), 1)) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500 dark:text-zinc-400">Paid</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ibs['paid'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ array_sum($ibs) > 0 ? ($ibs['paid'] / max(array_sum($ibs), 1)) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-zinc-500 dark:text-zinc-400">Overdue</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ibs['overdue'] }}</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full" style="width: {{ array_sum($ibs) > 0 ? ($ibs['overdue'] / max(array_sum($ibs), 1)) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Quotations</h3>
                    <div class="flex items-center gap-6">
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Total</p>
                            <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100">{{ $this->totalQuotations }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Accepted</p>
                            <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $this->acceptedQuotations }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">Conversion</p>
                            <p class="text-xl font-bold text-zinc-800 dark:text-zinc-100">{{ $this->totalQuotations > 0 ? round(($this->acceptedQuotations / $this->totalQuotations) * 100) : 0 }}%</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Users by Role</h3>
                    @php $ubr = $this->usersByRole; @endphp
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Clients</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ubr['clients'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Technicians</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ubr['technicians'] }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Admins</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ubr['admins'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Recent Service Requests</h3>
                    <div class="space-y-3">
                        @forelse ($this->recentServices as $service)
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-6 h-6 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 flex items-center justify-center text-xs font-medium capitalize">{{ substr($service->service_type, 0, 2) }}</span>
                                    <span class="truncate text-zinc-600 dark:text-zinc-400">{{ $service->user->name }} — <span class="capitalize">{{ $service->service_type }}</span></span>
                                </div>
                                <span class="text-xs text-zinc-400 shrink-0">{{ $service->created_at->diffForHumans() }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">No service requests yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Recent Invoices</h3>
                    <div class="space-y-3">
                        @forelse ($this->recentInvoices as $invoice)
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-mono text-xs text-zinc-400 bg-zinc-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded shrink-0">{{ $invoice->invoice_number }}</span>
                                    <span class="truncate text-zinc-600 dark:text-zinc-400">{{ $invoice->bookService?->user?->name ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">${{ number_format($invoice->total, 2) }}</span>
                                    @php
                                        $s = $invoice->status;
                                        $c = $s === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : ($s === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-zinc-400');
                                    @endphp
                                    <span class="text-xs capitalize {{ $c }}">{{ $s }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-400 dark:text-zinc-500">No invoices yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>

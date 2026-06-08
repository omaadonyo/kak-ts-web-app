<?php

use App\Models\Subscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Subscriptions')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function delete($id)
    {
        Subscription::findOrFail($id)->delete();
        session()->flash('message', 'Subscription deleted.');
    }

    #[Computed]
    public function subscriptions()
    {
        return Subscription::with('user')
            ->when($this->search, fn ($q) => $q->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Subscriptions</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1">Manage platform subscriptions.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <flux:input icon="magnifying-glass" placeholder="Search user..." class="max-w-xs" wire:model.live="search" />
            <select wire:model.live="statusFilter" class="rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="trialing">Trialing</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-700/30 text-zinc-500 dark:text-zinc-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-6 py-3">User</th>
                        <th class="text-left px-6 py-3">Plan</th>
                        <th class="text-left px-6 py-3">Amount</th>
                        <th class="text-left px-6 py-3">Status</th>
                        <th class="text-left px-6 py-3">Period</th>
                        <th class="text-right px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach ($this->subscriptions as $sub)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $sub->user?->name ?? 'Deleted' }}</span>
                                    <span class="text-xs text-zinc-400">{{ $sub->user?->email }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 capitalize text-zinc-700 dark:text-zinc-300">{{ $sub->plan }}</td>
                            <td class="px-6 py-4 font-medium text-zinc-800 dark:text-zinc-100">UGX {{ number_format($sub->amount, 0) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $sub->status === 'active' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($sub->status === 'trialing' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : ($sub->status === 'expired' ? 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400')) }}">
                                    {{ ucfirst($sub->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $sub->start_date->format('d M Y') }} &ndash; {{ $sub->end_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <flux:button size="xs" variant="danger" wire:confirm="Delete this subscription?" wire:click="delete({{ $sub->id }})">Delete</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($this->subscriptions->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-zinc-400">No subscriptions found.</div>
            @endif
        </div>

        <div class="mt-6">
            {{ $this->subscriptions->links() }}
        </div>
    </div>
</div>

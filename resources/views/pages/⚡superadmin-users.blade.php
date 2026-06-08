<?php

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Manage Users')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';

    public function delete($id)
    {
        $user = User::findOrFail($id);
        if ($user->isSuperAdmin()) {
            session()->flash('error', 'Cannot delete a super admin.');
            return;
        }
        $user->delete();
        session()->flash('message', 'User deleted.');
    }

    #[Computed]
    public function users()
    {
        return User::where('id', '!=', auth()->id())
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->latest()
            ->paginate(15);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-12 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">All Users</h1>
                <p class="text-zinc-500 dark:text-zinc-400 mt-1">Manage platform users, roles, and access.</p>
            </div>
        </div>

        @if (session('message'))
            <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm">{{ session('message') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">{{ session('error') }}</div>
        @endif

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <flux:input icon="magnifying-glass" placeholder="Search name or email..." class="max-w-xs" wire:model.live="search" />
            <select wire:model.live="roleFilter" class="rounded-xl border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-sm px-3 py-2">
                <option value="">All Roles</option>
                <option value="superadmin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="technician">Technician</option>
                <option value="client">Client</option>
            </select>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-700/30 text-zinc-500 dark:text-zinc-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="text-left px-6 py-3">Name</th>
                        <th class="text-left px-6 py-3">Email</th>
                        <th class="text-left px-6 py-3">Role</th>
                        <th class="text-left px-6 py-3">Joined</th>
                        <th class="text-right px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach ($this->users as $user)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-700/20 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-700 text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                                        {{ $user->initials() }}
                                    </span>
                                    <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $user->role === 'superadmin' ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400' : ($user->role === 'admin' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : ($user->role === 'technician' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400')) }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-zinc-500 dark:text-zinc-400">{{ $user->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <flux:button size="xs" variant="danger" wire:confirm="Delete this user? This cannot be undone." wire:click="delete({{ $user->id }})">Delete</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($this->users->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-zinc-400">No users found.</div>
            @endif
        </div>

        <div class="mt-6">
            {{ $this->users->links() }}
        </div>
    </div>
</div>

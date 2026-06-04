<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use WithPagination;

    public ?int $editingUserId = null;
    public string $newRole = '';

    #[Computed]
    public function users()
    {
        return User::with('parentCompany')
            ->where('id', '!=', Auth::id())
            ->latest()
            ->paginate(20);
    }

    public function startEdit(int $id, string $currentRole): void
    {
        $this->authorize('manage-users');
        $this->editingUserId = $id;
        $this->newRole = $currentRole;
    }

    public function saveRole(): void
    {
        $this->authorize('manage-users');
        $this->validate(['newRole' => 'required|in:client,technician,admin']);

        $user = User::findOrFail($this->editingUserId);
        $user->update(['role' => $this->newRole]);

        $this->editingUserId = null;
        $this->newRole = '';
        Flux::toast(variant: 'success', text: 'User role updated.');
    }

    public function cancelEdit(): void
    {
        $this->editingUserId = null;
        $this->newRole = '';
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Users</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Manage all registered users and their roles.</p>
        </div>

        @can('manage-users')
            <div class="w-full overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Email</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Phone</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Role</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Type</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Company</th>
                            <th class="text-right py-3 px-4 font-semibold text-zinc-600 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->users as $user)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 flex items-center justify-center text-xs font-bold uppercase">
                                            {{ substr($user->name, 0, 2) }}
                                        </span>
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400">{{ $user->email }}</td>
                                <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400">{{ $user->phone ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    @if ($editingUserId === $user->id)
                                        <select wire:model="newRole" class="text-xs border border-zinc-200 dark:border-zinc-600 rounded-lg px-2 py-1 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                                            <option value="client">Client</option>
                                            <option value="technician">Technician</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    @else
                                        @php
                                            $roleStyles = [
                                                'admin' => 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                                                'technician' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                                'client' => 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize {{ $roleStyles[$user->role] ?? '' }}">
                                            {{ $user->role }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 capitalize">{{ $user->client_type ?? '—' }}</td>
                                <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400">{{ $user->parentCompany?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-right">
                                    @if ($editingUserId === $user->id)
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="saveRole" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 px-2 py-1">Save</button>
                                            <button wire:click="cancelEdit" class="text-xs font-medium text-zinc-400 hover:text-zinc-600 px-2 py-1">Cancel</button>
                                        </div>
                                    @else
                                        <button wire:click="startEdit({{ $user->id }}, '{{ $user->role }}')" class="text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors">Edit Role</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $this->users->links() }}
            </div>
        @endcan
    </div>
</div>

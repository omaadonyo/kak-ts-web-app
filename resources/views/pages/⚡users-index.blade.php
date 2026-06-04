<?php

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 20;
    public ?int $editingUserId = null;
    public string $newRole = '';
    public bool $showAddForm = false;
    public string $addName = '';
    public string $addEmail = '';
    public string $addPassword = '';
    public string $addRole = 'client';
    public string $addClientType = 'individual';
    public string $addPhone = '';

    protected $queryString = ['search', 'perPage'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::with('parentCompany')
            ->where('id', '!=', Auth::id())
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $s = $this->search;
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('role', 'like', "%{$s}%")
                  ->orWhere('client_type', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate($this->perPage);
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
        User::findOrFail($this->editingUserId)->update(['role' => $this->newRole]);
        $this->editingUserId = null;
        $this->newRole = '';
        Flux::toast(variant: 'success', text: 'User role updated.');
    }

    public function cancelEdit(): void
    {
        $this->editingUserId = null;
        $this->newRole = '';
    }

    public function addUser(): void
    {
        $this->authorize('manage-users');
        $this->validate([
            'addName' => 'required|string|max:255',
            'addEmail' => 'required|email|max:255|unique:users,email',
            'addPassword' => 'required|string|min:8',
            'addRole' => 'required|in:client,technician,admin',
            'addClientType' => 'required|in:individual,company',
            'addPhone' => 'nullable|string|max:20',
        ]);

        User::create([
            'name' => $this->addName,
            'email' => $this->addEmail,
            'password' => Hash::make($this->addPassword),
            'role' => $this->addRole,
            'client_type' => $this->addRole === 'client' ? $this->addClientType : null,
            'phone' => $this->addPhone ?: null,
        ]);

        $this->reset(['addName', 'addEmail', 'addPassword', 'addRole', 'addClientType', 'addPhone', 'showAddForm']);
        Flux::toast(variant: 'success', text: 'User created successfully.');
    }

    public function exportCsv()
    {
        $this->authorize('manage-users');
        $users = $this->exportQuery()->get();
        $headers = ['ID', 'Name', 'Email', 'Phone', 'Role', 'Client Type', 'Company', 'Created At'];
        $rows = $users->map(fn($u) => [
            $u->id, $u->name, $u->email, $u->phone ?? '',
            $u->role, $u->client_type ?? '', $u->parentCompany?->name ?? '',
            $u->created_at->format('Y-m-d H:i'),
        ]);
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $headers);
        foreach ($rows as $row) fputcsv($csv, $row);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        return response()->streamDownload(fn() => print $content, 'users.csv');
    }

    public function exportPdf()
    {
        $this->authorize('manage-users');
        $users = $this->exportQuery()->get();
        $pdf = Pdf::loadView('exports.users', compact('users'));
        return response()->streamDownload(fn() => print $pdf->output(), 'users.pdf');
    }

    private function exportQuery()
    {
        return User::where('id', '!=', Auth::id())
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $s = $this->search;
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            }))
            ->latest();
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
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <div class="relative flex-1 w-full">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" wire:model.live.debounce="search" placeholder="Search by name, email, phone, role..."
                               class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20 focus:border-zinc-900 dark:focus:border-zinc-400">
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select wire:model.live="perPage" class="border border-zinc-200 dark:border-zinc-600 rounded-xl px-3 py-2.5 text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800">
                            <option value="10">10/page</option>
                            <option value="20">20/page</option>
                            <option value="50">50/page</option>
                            <option value="100">100/page</option>
                        </select>
                        <button wire:click="exportCsv" class="p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-zinc-500 dark:text-zinc-400" title="Export CSV">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        </button>
                        <button wire:click="exportPdf" class="p-2.5 rounded-xl border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors text-zinc-500 dark:text-zinc-400" title="Export PDF">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        </button>
                        <flux:button wire:click="$toggle('showAddForm')" icon="plus" variant="primary">{{ $showAddForm ? 'Cancel' : 'Add User' }}</flux:button>
                    </div>
                </div>

                @if ($showAddForm)
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Add New User</h3>
                        <form wire:submit="addUser" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Name</label>
                                <input type="text" wire:model="addName" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                                @error('addName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Email</label>
                                <input type="email" wire:model="addEmail" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                                @error('addEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Password</label>
                                <input type="password" wire:model="addPassword" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                                @error('addPassword') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Role</label>
                                <select wire:model="addRole" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30">
                                    <option value="client">Client</option>
                                    <option value="technician">Technician</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Client Type</label>
                                <select wire:model="addClientType" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30">
                                    <option value="individual">Individual</option>
                                    <option value="company">Company</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Phone</label>
                                <input type="text" wire:model="addPhone" class="w-full border border-zinc-200 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
                                <flux:button type="submit" variant="primary">Create User</flux:button>
                            </div>
                        </form>
                    </div>
                @endif

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

                @if ($this->users->hasPages())
                    <div class="mt-6">{{ $this->users->links(data: ['scrollTo' => false]) }}</div>
                @endif
            </div>
        @endcan
    </div>
</div>

<?php

use App\Models\Backup;
use App\Notifications\BackupCompleted;
use App\Services\BackupService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Database Backups')] class extends Component {
    use WithPagination;

    public bool $creating = false;

    #[Computed]
    public function backups()
    {
        return Backup::latest()->paginate(25);
    }

    public function createBackup(BackupService $service): void
    {
        $this->creating = true;

        try {
            $backup = $service->create();
            auth()->user()->notify(new BackupCompleted($backup));
            Flux::toast(variant: 'success', text: "Backup created: {$backup->filename} ({$backup->size_for_humans})");
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: 'Backup failed: ' . $e->getMessage());
        }

        $this->creating = false;
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Database Backups</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Create and download MySQL database backups.</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-6">
            <div class="text-center">
                <flux:button wire:click="createBackup" variant="primary" :disabled="$creating" class="w-full sm:w-auto">
                    @if ($creating)
                        Creating backup...
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline mr-1.5"><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Create New Backup
                    @endif
                </flux:button>
            </div>

            @forelse ($this->backups as $backup)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-5 transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ $backup->filename }}</p>
                                <div class="flex items-center gap-3 text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                    <span>{{ $backup->created_at->format('M d, Y \a\t h:i A') }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $backup->size_for_humans }}</span>
                                    <span>&middot;</span>
                                    <span class="inline-flex items-center gap-1 {{ $backup->status === 'completed' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $backup->status === 'completed' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                        {{ ucfirst($backup->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if ($backup->status === 'completed')
                            <a href="{{ route('superadmin.backups.download', $backup) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Download
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-16 px-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-700 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-1">No backups yet</h3>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">Click "Create New Backup" to generate your first database backup.</p>
                </div>
            @endforelse

            @if ($this->backups->hasPages())
                <div class="mt-6">{{ $this->backups->links(data: ['scrollTo' => false]) }}</div>
            @endif
        </div>
    </div>
</div>

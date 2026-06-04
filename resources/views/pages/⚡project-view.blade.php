<?php

use App\Models\BookService;
use App\Models\Project;
use App\Models\ProjectComment;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project')] class extends Component {
    public BookService $bookService;
    public Project $project;
    public int $progress = 0;
    public string $status = '';
    public string $newComment = '';

    public function mount(BookService $bookService): void
    {
        $this->bookService = $bookService;
        $this->project = $bookService->project;
        $this->progress = $this->project->progress;
        $this->status = $this->project->status;
    }

    #[Computed]
    public function comments()
    {
        return $this->project->comments()->with('user')->latest()->get();
    }

    public function updateProgress(): void
    {
        $this->validate(['progress' => ['required', 'integer', 'min:0', 'max:100']]);
        $this->project->update([
            'progress' => $this->progress,
            'status' => $this->progress >= 100 ? 'completed' : ($this->progress > 0 ? 'in_progress' : 'not_started'),
        ]);
        $this->status = $this->project->fresh()->status;
        Flux::toast(variant: 'success', text: 'Progress updated.');
    }

    public function addComment(): void
    {
        $this->validate(['newComment' => ['required', 'string', 'max:2000']]);
        ProjectComment::create([
            'project_id' => $this->project->id,
            'user_id' => Auth::id(),
            'content' => $this->newComment,
        ]);
        $this->newComment = '';
        Flux::toast(variant: 'success', text: 'Comment added.');
    }

    public function markCompleted(): void
    {
        $this->project->update(['progress' => 100, 'status' => 'completed']);
        $this->progress = 100;
        $this->status = 'completed';
        Flux::toast(variant: 'success', text: 'Project completed.');
    }
}; ?>

<div class="w-full max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $project->name }}</flux:heading>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    {{ $status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($status === 'in_progress' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400') }}">
                    {{ str_replace('_', ' ', $status) }}
                </span>
            </div>
            <flux:subheading>{{ $bookService->service_type }} &mdash; {{ $bookService->location }}</flux:subheading>
        </div>
    </div>

    @if ($project->description)
        <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $project->description }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Progress</h3>
            <span class="text-lg font-bold text-zinc-800 dark:text-zinc-100">{{ $progress }}%</span>
        </div>
        <div class="w-full h-2.5 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
            <div class="h-full bg-zinc-800 dark:bg-zinc-400 rounded-full transition-all duration-500 ease-out" style="width: {{ $progress }}%"></div>
        </div>

        <form wire:submit="updateProgress" class="flex items-center gap-4 mt-4">
            <input type="range" min="0" max="100" wire:model.live="progress"
                   class="flex-1 accent-zinc-800 dark:accent-zinc-400 h-2 cursor-pointer">
            <flux:button type="submit" size="sm" variant="primary">Update</flux:button>
            @if ($status !== 'completed' && $progress < 100)
                <button type="button" wire:click="markCompleted" wire:confirm="Mark project as completed?"
                        class="text-xs text-zinc-400 dark:text-zinc-500 hover:text-red-500 dark:hover:text-red-400 transition-colors">Mark Complete</button>
            @endif
        </form>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Comments ({{ count($this->comments) }})</h3>
        </div>

        <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
            <form wire:submit="addComment" class="flex gap-3">
                <input type="text" wire:model="newComment" placeholder="Add a comment..."
                       class="flex-1 border border-zinc-200 dark:border-zinc-600 rounded-lg px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-800/20 dark:focus:ring-zinc-400/20 focus:border-zinc-800 dark:focus:border-zinc-400">
                <flux:button type="submit" variant="primary" class="shrink-0">Post</flux:button>
            </form>
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
            @forelse ($this->comments as $comment)
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $comment->user->name }}</span>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ $comment->content }}</p>
                </div>
            @empty
                <div class="px-6 py-8 text-center">
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No comments yet. Start the conversation.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

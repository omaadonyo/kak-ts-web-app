<?php

use App\Models\BookService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project Report')] class extends Component {
    public BookService $bookService;

    public function mount(BookService $bookService): void
    {
        $this->bookService = $bookService->load(['user', 'assignedTo', 'assessment.assessedBy', 'quotation', 'project.milestones', 'project.comments.user', 'invoice.payments']);
    }

    public function exportPdf()
    {
        $bs = $this->bookService;
        $company = ['name' => config('app.name'), 'email' => config('mail.from.address'), 'phone' => '+1 555-123-4567'];
        $pdf = Pdf::loadView('exports.project-report', compact('bs', 'company'));
        $pdf->setPaper('a4');
        return response()->streamDownload(fn() => print $pdf->output(), 'project-report-' . $bs->id . '.pdf');
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Project Report</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Full profile of the service project.</p>
            <button wire:click="exportPdf" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-zinc-200 dark:border-zinc-600 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Export PDF
            </button>
        </div>

        @php $bs = $this->bookService; $project = $bs->project; @endphp

        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-100">Service Details</h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium capitalize
                        {{ $project?->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($project?->status === 'in_progress' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400') }}">
                        {{ str_replace('_', ' ', $project?->status ?? 'N/A') }}
                    </span>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Service Type</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200 capitalize">{{ $bs->service_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Location</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bs->location }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Client</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bs->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Email</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bs->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Phone</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bs->user->phone ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Assigned Technician</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bs->assignedTo->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Date Created</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200">{{ $bs->created_at->format('F d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs">Status</dt>
                        <dd class="font-medium text-zinc-800 dark:text-zinc-200 capitalize">{{ $bs->status }}</dd>
                    </div>
                </dl>
                @if ($bs->notes)
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                        <dt class="text-zinc-400 dark:text-zinc-500 text-xs mb-1">Notes</dt>
                        <dd class="text-sm text-zinc-600 dark:text-zinc-400">{{ $bs->notes }}</dd>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4">Assessment</h2>
                @if ($bs->assessment)
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                        <p><strong class="text-zinc-700 dark:text-zinc-300">Assessed by:</strong> {{ $bs->assessment->assessedBy->name ?? 'N/A' }}</p>
                        <p class="mt-1"><strong class="text-zinc-700 dark:text-zinc-300">Findings:</strong></p>
                        <p class="mt-1 leading-relaxed">{{ $bs->assessment->findings }}</p>
                    </div>
                    @if ($bs->assessment->photos && count($bs->assessment->photos) > 0)
                        <div class="flex gap-2 flex-wrap">
                            @foreach ($bs->assessment->photos as $photo)
                                <img src="{{ asset('storage/' . $photo) }}" alt="" class="w-20 h-20 rounded-lg object-cover border border-zinc-100 dark:border-zinc-700">
                            @endforeach
                        </div>
                    @endif
                @else
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No assessment has been completed yet.</p>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4">Quotation</h2>
                @if ($bs->quotation)
                    <div class="space-y-3">
                        @foreach ($bs->quotation->line_items as $item)
                            <div class="flex items-center justify-between text-sm py-2 border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                                <div>
                                    <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ $item['description'] ?? 'Item' }}</p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">Qty: {{ $item['quantity'] ?? 1 }} &times; UGX {{ number_format($item['unit_price'] ?? 0, 2) }}</p>
                                </div>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">UGX {{ number_format($item['total'] ?? 0, 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between pt-2 text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">UGX {{ number_format($bs->quotation->subtotal, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Tax</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">UGX {{ number_format($bs->quotation->tax, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-base font-bold pt-2 border-t border-zinc-200 dark:border-zinc-600">
                            <span class="text-zinc-800 dark:text-zinc-100">Total</span>
                            <span class="text-zinc-800 dark:text-zinc-100">UGX {{ number_format($bs->quotation->total, 2) }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No quotation has been generated yet.</p>
                @endif
            </div>

            @if ($project)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4">Progress</h2>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-1 h-3 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                            <div class="h-full bg-zinc-800 dark:bg-zinc-400 rounded-full transition-all" style="width: {{ $project->progress }}%"></div>
                        </div>
                        <span class="text-lg font-bold text-zinc-800 dark:text-zinc-100">{{ $project->progress }}%</span>
                    </div>
                    @if ($project->milestones->count() > 0)
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Milestones</h3>
                        <div class="space-y-2">
                            @foreach ($project->milestones as $milestone)
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold
                                        {{ $milestone->status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500' }}">
                                        {{ $milestone->status === 'completed' ? '✓' : $loop->iteration }}
                                    </span>
                                    <span class="flex-1 text-zinc-600 dark:text-zinc-400">{{ $milestone->name }}</span>
                                    @if ($milestone->due_date)
                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">Due {{ $milestone->due_date->format('M d') }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @if ($bs->invoice)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4">Invoice</h2>
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Invoice #</span>
                        <span class="font-mono font-medium text-zinc-700 dark:text-zinc-300">{{ $bs->invoice->invoice_number }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="text-zinc-500 dark:text-zinc-400">Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize
                            {{ $bs->invoice->status === 'paid' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : ($bs->invoice->status === 'overdue' ? 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400') }}">
                            {{ $bs->invoice->status }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-base font-bold pt-2 border-t border-zinc-200 dark:border-zinc-600">
                        <span class="text-zinc-800 dark:text-zinc-100">Total</span>
                        <span class="text-zinc-800 dark:text-zinc-100">UGX {{ number_format($bs->invoice->total, 2) }}</span>
                    </div>
                </div>
            @endif

            @if ($project && $project->comments->count() > 0)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
                    <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-100 mb-4">Comments</h2>
                    <div class="space-y-3">
                        @foreach ($project->comments as $comment)
                            <div class="flex items-start gap-3 text-sm">
                                <span class="w-7 h-7 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 flex items-center justify-center text-xs font-bold uppercase shrink-0">
                                    {{ substr($comment->user->name, 0, 2) }}
                                </span>
                                <div>
                                    <p class="font-medium text-zinc-700 dark:text-zinc-300">{{ $comment->user->name }} <span class="text-xs text-zinc-400 dark:text-zinc-500 font-normal">{{ $comment->created_at->diffForHumans() }}</span></p>
                                    <p class="text-zinc-600 dark:text-zinc-400 mt-0.5">{{ $comment->content }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

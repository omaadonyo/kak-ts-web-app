<?php

use App\Models\Assessment;
use App\Models\BookService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Assessment Report')] class extends Component {
    use WithFileUploads;

    public BookService $bookService;
    public ?Assessment $assessment = null;
    public string $findings = '';
    public array $photos = [];
    public array $photoPreviews = [];

    public bool $isEditable = false;

    public function mount(BookService $bookService): void
    {
        $this->bookService = $bookService;
        $this->assessment = $bookService->assessment;
        $this->isEditable = Auth::user()->isAdmin() || Auth::user()->isTechnician();
        if ($this->assessment) {
            $this->findings = $this->assessment->findings;
        }
    }

    public function rules(): array
    {
        return [
            'findings' => ['required', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ];
    }

    public function updatedPhotos(): void
    {
        $this->photoPreviews = [];
        foreach ($this->photos as $photo) {
            $this->photoPreviews[] = $photo->temporaryUrl();
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
        unset($this->photoPreviews[$index]);
        $this->photoPreviews = array_values($this->photoPreviews);
    }

    public function save(): void
    {
        $this->validate();

        $paths = $this->assessment?->photos ?? [];
        foreach ($this->photos as $photo) {
            $paths[] = $photo->store('assessments', 'public');
        }

        if ($this->assessment) {
            $this->assessment->update(['findings' => $this->findings, 'photos' => $paths]);
            Flux::toast(variant: 'success', text: 'Assessment updated.');
        } else {
            Assessment::create([
                'book_service_id' => $this->bookService->id,
                'assessed_by' => Auth::id(),
                'findings' => $this->findings,
                'photos' => $paths,
                'status' => 'completed',
            ]);
            Flux::toast(variant: 'success', text: 'Assessment submitted.');
        }

        $this->redirect(route('book-services'), navigate: true);
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M12 3a9 9 0 0 0-9 9v1h2.5a2.5 2.5 0 0 1 0 5H3v1a9 9 0 0 0 9 9h.5v-2.5a2.5 2.5 0 0 1 5 0V23h1a9 9 0 0 0 9-9v-1h-2.5a2.5 2.5 0 0 1 0-5H21v-1a9 9 0 0 0-9-9z"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Assessment Report</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 capitalize">{{ $bookService->service_type }} &mdash; {{ $bookService->location }}</p>
        </div>

        <div class="w-full md:w-1/2 md:mx-auto space-y-6">
            @if ($bookService->photos && count($bookService->photos) > 0)
                @php
                    $servicePhotos = array_map(fn($p) => asset('storage/' . $p), $bookService->photos);
                @endphp
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8">
                    <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4">Service Request Photos</h2>
                    <div x-data="{
                        activeSlide: 0,
                        images: @js($servicePhotos),
                        prev() { this.activeSlide = this.activeSlide === 0 ? this.images.length - 1 : this.activeSlide - 1; },
                        next() { this.activeSlide = this.activeSlide === this.images.length - 1 ? 0 : this.activeSlide + 1; }
                    }" class="space-y-3">
                        <div class="relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-700/50">
                            <div class="w-full aspect-[4/3]">
                                <template x-for="(src, i) in images" :key="i">
                                    <div x-show="activeSlide === i"
                                         x-transition:enter="transition ease-out duration-300"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         class="w-full h-full">
                                        <img :src="src" alt="" class="w-full h-full object-contain" loading="lazy">
                                    </div>
                                </template>
                            </div>
                            <button x-show="images.length > 1" @click="prev()"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <button x-show="images.length > 1" @click="next()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                            <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-lg font-medium"
                                 x-show="images.length > 1"
                                 x-text="`${activeSlide + 1} / ${images.length}`"></div>
                        </div>
                        <div class="flex justify-center gap-1.5" x-show="images.length > 1">
                            <template x-for="(_, i) in images" :key="'dot-'+i">
                                <button @click="activeSlide = i"
                                        :class="activeSlide === i ? 'bg-zinc-800 dark:bg-zinc-200 w-5' : 'bg-zinc-300 dark:bg-zinc-600 w-2'"
                                        class="h-2 rounded-full transition-all duration-300"></button>
                            </template>
                        </div>
                    </div>
                </div>
            @endif

            @if ($isEditable)
                <form wire:submit="save" class="space-y-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8 space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-1">Assessment Notes</h2>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">Document your findings and observations.</p>
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Findings <span class="text-zinc-400">*</span></label>
                            <textarea wire:model="findings" placeholder="Describe the assessment findings in detail..."
                                      rows="6"
                                      class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20 focus:border-zinc-900 dark:focus:border-zinc-400 transition-all duration-200 resize-none"></textarea>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Additional Photos <span class="text-zinc-400">(optional)</span></label>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">Add more photos if needed.</p>
                            </div>

                            <div class="relative flex flex-col items-center justify-center py-8 px-4 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 cursor-pointer transition-all duration-200 hover:border-zinc-400 dark:hover:border-zinc-500 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30"
                                 onclick="document.getElementById('assessment-photos').click()" wire:ignore>
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600 mb-2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">Click to upload</p>
                            </div>

                            <input id="assessment-photos" type="file" multiple accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" wire:model="photos" class="hidden">

                            @if (!empty($photoPreviews))
                                <div x-data="{
                                    activeSlide: 0,
                                    images: @js(array_values($photoPreviews)),
                                    init() {
                                        $wire.$watch('photoPreviews', (value) => {
                                            if (value && value.length) {
                                                this.images = Object.values(value);
                                                this.activeSlide = 0;
                                            }
                                        });
                                    },
                                    prev() { this.activeSlide = this.activeSlide === 0 ? this.images.length - 1 : this.activeSlide - 1; },
                                    next() { this.activeSlide = this.activeSlide === this.images.length - 1 ? 0 : this.activeSlide + 1; }
                                }" class="space-y-3">
                                    <div class="relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-700/50">
                                        <div class="w-full aspect-[4/3]">
                                            <template x-for="(src, i) in images" :key="i">
                                                <div x-show="activeSlide === i"
                                                     x-transition:enter="transition ease-out duration-300"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     class="w-full h-full">
                                                    <img :src="src" alt="" class="w-full h-full object-contain">
                                                </div>
                                            </template>
                                        </div>
                                        <button type="button" x-show="images.length > 1" @click="prev()"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                        </button>
                                        <button type="button" x-show="images.length > 1" @click="next()"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                        </button>
                                        <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-lg font-medium"
                                             x-show="images.length > 1"
                                             x-text="`${activeSlide + 1} / ${images.length}`"></div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex gap-1.5" x-show="images.length > 1">
                                            <template x-for="(_, i) in images" :key="'dot-'+i">
                                                <button type="button" @click="activeSlide = i"
                                                        :class="activeSlide === i ? 'bg-zinc-800 dark:bg-zinc-200 w-5' : 'bg-zinc-300 dark:bg-zinc-600 w-2'"
                                                        class="h-2 rounded-full transition-all duration-300"></button>
                                            </template>
                                        </div>
                                        <div class="flex gap-2">
                                            <template x-for="(src, i) in images" :key="'thumb-'+i">
                                                <div class="relative cursor-pointer" @click="activeSlide = i">
                                                    <img :src="src" alt=""
                                                         class="w-9 h-9 rounded-lg object-cover border-2 transition-all duration-200"
                                                         :class="activeSlide === i ? 'border-zinc-800 dark:border-zinc-200' : 'border-transparent opacity-60 hover:opacity-100'">
                                                </div>
                                            </template>
                                            @foreach ($photoPreviews as $index => $preview)
                                                <button type="button" wire:click="removePhoto({{ $index }})"
                                                        class="w-9 h-9 rounded-lg bg-red-500/80 text-white flex items-center justify-center text-sm hover:bg-red-600 transition-colors"
                                                        title="Remove photo {{ $index + 1 }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <flux:button href="{{ route('book-services') }}" variant="ghost" wire:navigate>Back</flux:button>
                        <flux:button variant="primary" type="submit">{{ $assessment ? 'Update Assessment' : 'Submit Assessment' }}</flux:button>
                    </div>
                </form>
            @else
                @if ($assessment)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8 space-y-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-1">Assessment Findings</h2>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $assessment->findings }}</div>

                        @if ($assessment->photos && count($assessment->photos) > 0)
                            @php $assessmentPhotos = array_map(fn($p) => asset('storage/' . $p), $assessment->photos); @endphp
                            <div class="pt-4 border-t border-zinc-100 dark:border-zinc-700">
                                <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Assessment Photos</h3>
                                <div x-data="{
                                    activeSlide: 0,
                                    images: @js($assessmentPhotos),
                                    prev() { this.activeSlide = this.activeSlide === 0 ? this.images.length - 1 : this.activeSlide - 1; },
                                    next() { this.activeSlide = this.activeSlide === this.images.length - 1 ? 0 : this.activeSlide + 1; }
                                }" class="space-y-3">
                                    <div class="relative overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-700/50">
                                        <div class="w-full aspect-[4/3]">
                                            <template x-for="(src, i) in images" :key="i">
                                                <div x-show="activeSlide === i" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="w-full h-full">
                                                    <img :src="src" alt="" class="w-full h-full object-contain" loading="lazy">
                                                </div>
                                            </template>
                                        </div>
                                        <button type="button" x-show="images.length > 1" @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                        </button>
                                        <button type="button" x-show="images.length > 1" @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                        </button>
                                        <div class="absolute top-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-lg font-medium" x-show="images.length > 1" x-text="`${activeSlide + 1} / ${images.length}`"></div>
                                    </div>
                                    <div class="flex justify-center gap-1.5" x-show="images.length > 1">
                                        <template x-for="(_, i) in images" :key="'dot-'+i">
                                            <button type="button" @click="activeSlide = i" :class="activeSlide === i ? 'bg-zinc-800 dark:bg-zinc-200 w-5' : 'bg-zinc-300 dark:bg-zinc-600 w-2'" class="h-2 rounded-full transition-all duration-300"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex">
                        <flux:button href="{{ route('book-services') }}" variant="ghost" wire:navigate>Back</flux:button>
                    </div>
                @else
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8 text-center">
                        <p class="text-zinc-500 dark:text-zinc-400">Assessment has not been completed yet.</p>
                        <flux:button href="{{ route('book-services') }}" variant="ghost" wire:navigate class="mt-4">Back</flux:button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

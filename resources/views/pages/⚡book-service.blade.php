<?php

use App\Models\BookService;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Book a Service')] class extends Component {
    use WithFileUploads;

    public string $service_type = '';
    public string $location = '';
    public string $notes = '';
    public array $photos = [];
    public array $photoPreviews = [];
    public int $step = 1;
    public ?int $client_id = null;

    public function rules(): array
    {
        $rules = [
            'service_type' => ['required', 'in:plumbing,electricals,carpentry'],
            'location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['required', 'array', 'min:2', 'max:5'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:20480'],
        ];
        if (Auth::user()->isAdmin() || Auth::user()->isCompany()) {
            $rules['client_id'] = ['required', 'exists:users,id'];
        }
        return $rules;
    }

    public function updatedPhotos(): void
    {
        $this->validateOnly('photos');
        $this->validateOnly('photos.*');
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

    public function goStep(int $s): void
    {
        if ($s < $this->step) { $this->step = $s; return; }
        if ($s === 2) { $this->validate(['service_type' => ['required', 'in:plumbing,electricals,carpentry']]); }
        if ($s === 3) { $this->validate(['service_type' => ['required', 'in:plumbing,electricals,carpentry'], 'location' => ['required', 'string', 'max:255']]); }
        $this->step = $s;
    }

    public function save(): void
    {
        $this->validate();

        $paths = [];
        foreach ($this->photos as $photo) {
            $paths[] = $photo->store('book-services', 'public');
        }

        BookService::create([
            'user_id' => (Auth::user()->isAdmin() || Auth::user()->isCompany()) ? $this->client_id : Auth::id(),
            'service_type' => $this->service_type,
            'location' => $this->location,
            'notes' => $this->notes,
            'photos' => $paths,
            'status' => 'pending',
        ]);

        Flux::toast(variant: 'success', text: 'Service request submitted successfully.');
        $this->reset(['service_type', 'location', 'notes', 'photos', 'photoPreviews', 'step', 'client_id']);
        $this->step = 1;
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-zinc-50 to-white dark:from-zinc-900 dark:to-zinc-900 py-8 md:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-zinc-900 dark:bg-white shadow-lg shadow-zinc-900/10 dark:shadow-black/20 mb-5">
                <svg class="w-8 h-8 text-white dark:text-zinc-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Book a Service</h1>
            <p class="text-zinc-500 dark:text-zinc-400 mt-2 max-w-sm mx-auto">Tell us what needs fixing and we'll take care of the rest.</p>
        </div>

        <div class="flex items-center justify-center gap-2 sm:gap-4 mb-10 text-xs font-medium">
            <button type="button" @click="$wire.goStep(1)" class="flex items-center gap-2 {{ $step === 1 ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400' }} transition-colors">
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $step === 1 ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500' }}">1</span>
                <span class="hidden sm:inline">Service</span>
            </button>
            <span class="w-10 h-px bg-zinc-200 dark:bg-zinc-700"></span>
            <button type="button" @click="$wire.goStep(2)" class="flex items-center gap-2 {{ $step === 2 ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400' }} transition-colors">
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $step === 2 ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500' }}">2</span>
                <span class="hidden sm:inline">Details</span>
            </button>
            <span class="w-10 h-px bg-zinc-200 dark:bg-zinc-700"></span>
            <button type="button" @click="$wire.goStep(3)" class="flex items-center gap-2 {{ $step === 3 ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400' }} transition-colors">
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $step === 3 ? 'bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500' }}">3</span>
                <span class="hidden sm:inline">Photos</span>
            </button>
        </div>

        <div class="w-full md:w-1/2 md:mx-auto">
            @php $showClientSelector = Auth::user()->isAdmin() || Auth::user()->isCompany(); @endphp
            @if ($showClientSelector)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8 mb-6">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Client <span class="text-zinc-400">*</span></label>
                    <select wire:model="client_id" class="mt-1 w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                        <option value="">Select a client...</option>
                        @foreach (Auth::user()->isAdmin() ? \App\Models\User::where('role', 'client')->orderBy('name')->get() : Auth::user()->companyUsers()->orderBy('name')->get() as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->email }})</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1.5 text-sm text-red-500 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            @endif

            <form wire:submit="save" class="space-y-6">

                @if ($step === 1)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-1">What do you need help with?</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">Choose the type of service you require.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="relative flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 cursor-pointer transition-all duration-200 has-[:checked]:border-zinc-900 dark:has-[:checked]:border-zinc-300 has-[:checked]:bg-zinc-50 dark:has-[:checked]:bg-zinc-700/50 hover:border-zinc-400 dark:hover:border-zinc-500 hover:shadow-sm text-center"
                                   x-data>
                                <input type="radio" name="service_type" value="plumbing" wire:model="service_type" class="sr-only">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600 dark:text-blue-400"><path d="M12 22a8 8 0 0 0 8-8c0-4.42-3.58-8-8-8-3.5 0-6.5 2.25-7.5 5.5C3.5 14 6 16.5 9.5 16.5c2 0 3.75-.83 5-2.17"/><path d="M9.5 16.5c-1.5 0-2.5-1-3-2"/><path d="M12 6v2"/><path d="M14 8h-4"/></svg>
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Plumbing</span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">Pipes, faucets, drains &amp; water systems</p>
                                </div>
                            </label>
                            <label class="relative flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 cursor-pointer transition-all duration-200 has-[:checked]:border-zinc-900 dark:has-[:checked]:border-zinc-300 has-[:checked]:bg-zinc-50 dark:has-[:checked]:bg-zinc-700/50 hover:border-zinc-400 dark:hover:border-zinc-500 hover:shadow-sm text-center"
                                   x-data>
                                <input type="radio" name="service_type" value="electricals" wire:model="service_type" class="sr-only">
                                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600 dark:text-amber-400"><path d="M15 14c.2-1 .7-1.7 1-2 1-1 1.5-2.5 1.5-3.5C17.5 5.5 15 3 12 3S6.5 5.5 6.5 8.5c0 1 .5 2.5 1.5 3.5.3.3.8 1 1 2"/><path d="M9 14h6"/><path d="M12 14v7"/></svg>
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Electricals</span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">Wiring, switches, lights &amp; power</p>
                                </div>
                            </label>
                            <label class="relative flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 cursor-pointer transition-all duration-200 has-[:checked]:border-zinc-900 dark:has-[:checked]:border-zinc-300 has-[:checked]:bg-zinc-50 dark:has-[:checked]:bg-zinc-700/50 hover:border-zinc-400 dark:hover:border-zinc-500 hover:shadow-sm text-center"
                                   x-data>
                                <input type="radio" name="service_type" value="carpentry" wire:model="service_type" class="sr-only">
                                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600 dark:text-emerald-400"><path d="M13 14H9l-3 4h12l-3-4Z"/><path d="M9.5 14 12 4 14.5 14"/><path d="M13 14v2"/><path d="M11 14v2"/></svg>
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Carpentry</span>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 leading-relaxed">Furniture, shelves, repairs &amp; woodwork</p>
                                </div>
                            </label>
                        </div>
                        @error('service_type')
                            <p class="mt-3 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <div class="mt-8 flex justify-end">
                            <button type="button" @click="$wire.goStep(2)"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-xl text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm">
                                Continue
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if ($step === 2)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8 space-y-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-1">Tell us more</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">Where and what needs to be done?</p>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Location <span class="text-zinc-400">*</span></label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <input type="text" wire:model="location" placeholder="e.g. 123 Main St, City"
                                       class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl pl-10 pr-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20 focus:border-zinc-900 dark:focus:border-zinc-400 transition-all duration-200">
                            </div>
                            @error('location')
                                <p class="mt-1.5 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Notes <span class="text-zinc-400">(optional)</span></label>
                            <textarea wire:model="notes" placeholder="Describe what needs to be done in detail..."
                                      rows="4"
                                      class="w-full border border-zinc-200 dark:border-zinc-600 rounded-xl px-4 py-2.5 text-sm text-zinc-800 dark:text-zinc-200 bg-transparent dark:bg-zinc-700/30 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20 focus:border-zinc-900 dark:focus:border-zinc-400 transition-all duration-200 resize-none"></textarea>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <button type="button" @click="$wire.goStep(1)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                Back
                            </button>
                            <button type="button" @click="$wire.goStep(3)"
                                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 rounded-xl text-sm font-medium hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm">
                                Continue
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if ($step === 3)
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700/50 shadow-sm p-6 md:p-8 space-y-6">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-1">Add photos</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">Upload at least 2 photos of the work area (max 5).</p>

                        <div class="relative flex flex-col items-center justify-center py-10 px-4 rounded-xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 cursor-pointer transition-all duration-200 hover:border-zinc-400 dark:hover:border-zinc-500 hover:bg-zinc-50/50 dark:hover:bg-zinc-700/30"
                             onclick="document.getElementById('photo-upload').click()"
                             wire:ignore>
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-300 dark:text-zinc-600 mb-3"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 font-medium">Click to upload photos</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">JPEG, PNG, JPG, GIF, WebP &middot; 20MB max each</p>
                        </div>

                        <input id="photo-upload" type="file" multiple accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" wire:model="photos" class="hidden">

                        @error('photos')
                            <p class="text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @error('photos.*')
                            <p class="text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror

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
                            }" x-show="images.length > 0" class="space-y-3">
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

                                    @verbatim
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
                                    @endverbatim
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex gap-1.5" x-show="images.length > 1">
                                        <template x-for="(_, i) in images" :key="'dot-'+i">
                                            <button type="button" @click="activeSlide = i"
                                                    :class="activeSlide === i ? 'bg-zinc-800 dark:bg-zinc-200 w-5' : 'bg-zinc-300 dark:bg-zinc-600 w-2'"
                                                    class="h-2 rounded-full transition-all duration-300"></button>
                                        </template>
                                    </div>
                                    <div class="flex gap-2 flex-wrap">
                                        <template x-for="(src, i) in images" :key="'thumb-'+i">
                                            <div class="relative cursor-pointer" @click="activeSlide = i">
                                                <img :src="src" alt=""
                                                     class="w-10 h-10 rounded-lg object-cover border-2 transition-all duration-200"
                                                     :class="activeSlide === i ? 'border-zinc-800 dark:border-zinc-200' : 'border-transparent opacity-60 hover:opacity-100'">
                                            </div>
                                        </template>
                                        @php
                                            $previewCount = count($photoPreviews);
                                        @endphp
                                        @if ($previewCount > 0)
                                            @foreach ($photoPreviews as $index => $preview)
                                                <button type="button" wire:click="removePhoto({{ $index }})"
                                                        class="w-10 h-10 rounded-lg bg-red-500/80 text-white flex items-center justify-center text-sm hover:bg-red-600 transition-colors"
                                                        title="Remove photo {{ $index + 1 }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                </button>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>

                                <p class="text-xs text-zinc-400 dark:text-zinc-500" x-text="`${images.length}/5 photo(s) uploaded`"></p>
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-2">
                            <button type="button" @click="$wire.goStep(2)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                Back
                            </button>
                            <flux:button variant="primary" type="submit">Submit Request</flux:button>
                        </div>
                    </div>
                @endif

            </form>
            <p class="text-center text-xs text-zinc-400 dark:text-zinc-500 mt-6">By submitting, you agree to our terms of service.</p>
        </div>
    </div>
</div>

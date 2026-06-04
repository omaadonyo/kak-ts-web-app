<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $client_type = '';
    public ?int $parent_company_id = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->client_type = $user->client_type ?? '';
        $this->parent_company_id = $user->parent_company_id;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate(array_merge($this->profileRules($user->id), [
            'phone' => ['nullable', 'string', 'max:20'],
            'client_type' => ['nullable', 'in:individual,company'],
            'parent_company_id' => ['nullable', 'exists:users,id'],
        ]));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function clientCompanies(): \Illuminate\Support\Collection
    {
        return \App\Models\User::where('role', 'client')
            ->where('client_type', 'company')
            ->where('id', '!=', Auth::id())
            ->get();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="phone" :label="__('Phone')" type="text" autocomplete="tel" />

            @if (Auth::user()->role === 'client')
                <flux:select wire:model="client_type" :label="__('Client Type')" placeholder="{{ __('Select type') }}">
                    <flux:select.option value="individual">{{ __('Individual') }}</flux:select.option>
                    <flux:select.option value="company">{{ __('Company') }}</flux:select.option>
                </flux:select>

                @if ($client_type === 'company')
                    <flux:select wire:model="parent_company_id" :label="__('Parent Company')" placeholder="{{ __('Select parent company') }}">
                        <flux:select.option value="">{{ __('None (this is a parent company)') }}</flux:select.option>
                        @foreach ($this->clientCompanies as $company)
                            <flux:select.option :value="$company->id">{{ $company->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            @endif

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>

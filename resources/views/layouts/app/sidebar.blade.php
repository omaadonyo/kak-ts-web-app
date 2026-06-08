<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @can('book-service')
                        <flux:sidebar.item icon="plus" :href="route('book-service')" :current="request()->routeIs('book-service')" wire:navigate>
                            {{ __('Book a Service') }}
                        </flux:sidebar.item>
                    @endcan

                    <flux:sidebar.item icon="list-bullet" :href="route('book-services')" :current="request()->routeIs('book-services*')" wire:navigate>
                        {{ auth()->user()->isClient() ? __('My Services') : (auth()->user()->isTechnician() ? __('Assigned Services') : __('All Services')) }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Documents')">
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('assessments.index')" :current="request()->routeIs('assessments.*')" wire:navigate>
                        {{ __('Assessments') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('quotations.index')" :current="request()->routeIs('quotations.*')" wire:navigate>
                        {{ __('Quotations') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="credit-card" :href="route('invoices.index')" :current="request()->routeIs('invoices.*')" wire:navigate>
                        {{ __('Invoices') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="currency-dollar" :href="route('transactions.index')" :current="request()->routeIs('transactions.*')" wire:navigate>
                        {{ __('Transactions') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Management')">
                    @can('manage-users')
                        <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                            {{ __('Reports') }}
                        </flux:sidebar.item>
                    @endcan
                    @can('manage-company-users')
                        <flux:sidebar.item icon="user-group" :href="route('company.users.index')" :current="request()->routeIs('company.users.*')" wire:navigate>
                            {{ __('My Team') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            @can('superadmin')
                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Super Admin')">
                        <flux:sidebar.item icon="chart-bar" :href="route('superadmin.dashboard')" :current="request()->routeIs('superadmin.dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('superadmin.users')" :current="request()->routeIs('superadmin.users')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="currency-dollar" :href="route('superadmin.sales')" :current="request()->routeIs('superadmin.sales')" wire:navigate>
                            {{ __('Sales Reports') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-text" :href="route('superadmin.logs')" :current="request()->routeIs('superadmin.logs')" wire:navigate>
                            {{ __('Logs') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="circle-stack" :href="route('superadmin.backups')" :current="request()->routeIs('superadmin.backups*')" wire:navigate>
                            {{ __('Backups') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                </flux:sidebar.nav>
            @endcan

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

<style>
    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #a1a1aa; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #71717a; }
    .dark ::-webkit-scrollbar-thumb { background: #52525b; }
    .dark ::-webkit-scrollbar-thumb:hover { background: #71717a; }
    * { scrollbar-width: thin; scrollbar-color: #a1a1aa transparent; }
    .dark * { scrollbar-color: #52525b transparent; }
</style>

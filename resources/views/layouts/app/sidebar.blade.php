<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <x-module-selector :modules="[
                    [
                        'name' => 'Dashboard',
                        'items' => [
                            ['name' => 'Overview', 'icon' => 'home', 'href' => route('dashboard'), 'route' => 'dashboard'],
                            ['name' => 'Analytics', 'icon' => 'chart-bar', 'href' => '#', 'route' => ''],
                        ],
                    ],
                    [
                        'name' => 'Management',
                        'items' => [
                            ['name' => 'Users', 'icon' => 'users', 'href' => route('users.index'), 'route' => 'users.*'],
                        ],
                    ],
                ]" />
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <x-mobile-user-menu />

        <div class="lg:ps-72">
            {{ $slot }}
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

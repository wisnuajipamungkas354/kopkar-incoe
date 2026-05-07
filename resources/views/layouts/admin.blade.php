<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
        <flux:sidebar sticky collapsible class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    href="#"
                    logo="{{ asset('img/kki-icon-2-light.png') }}"
                    logo:dark="{{ asset('img/kki-icon-2-dark.png') }}"
                />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>
            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" href="{{ url('/admin') }}" wire:navigate>Dashboard</flux:sidebar.item>
                <flux:sidebar.item icon="inbox" badge="12" href="{{ url('/admin/anggota')}}" wire:navigate>Anggota</flux:sidebar.item>
                <flux:sidebar.item icon="document-text" href="{{ url('/admin/ppob') }}" wire:navigate>PPOB</flux:sidebar.item>
                <flux:sidebar.group expandable icon="star" heading="Simpanan" class="grid">
                    <flux:sidebar.item href="#">Pokok</flux:sidebar.item>
                    <flux:sidebar.item href="#">Wajib</flux:sidebar.item>
                    <flux:sidebar.item href="#">Sukarela</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
            <flux:sidebar.spacer />
            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
                <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
            </flux:sidebar.nav>
            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile avatar="https://fluxui.dev/img/demo/user.png" name="Olivia Martin" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <flux:menu.radio checked>Olivia Martin</flux:menu.radio>
                        <flux:menu.radio>Truly Delta</flux:menu.radio>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:dropdown position="top" align="start">
                <flux:profile avatar="/img/demo/user.png" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <flux:menu.radio checked>Olivia Martin</flux:menu.radio>
                        <flux:menu.radio>Truly Delta</flux:menu.radio>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>
        <flux:main>
            {{ $slot }}
        </flux:main>
        @livewireScripts
        @fluxScripts
    </body>
</html>

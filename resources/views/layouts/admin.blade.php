<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <script>
            (() => {
        
                if (! localStorage.getItem('flux.appearance')) {
        
                    const prefersDark = window.matchMedia(
                        '(prefers-color-scheme: dark)'
                    ).matches;
        
                    localStorage.setItem(
                        'flux.appearance',
                        prefersDark ? 'dark' : 'light'
                    );
                }
        
            })();
        </script>
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
                <flux:sidebar.item icon="home" href="{{ url('/admin') }}" wire:navigate :current="request()->is('admin')">Dashboard</flux:sidebar.item>
                
                <flux:sidebar.item icon="users" badge="12" href="{{ url('/admin/anggota')}}" wire:navigate :current="request()->is('admin/anggota')">Anggota</flux:sidebar.item>
                
                <flux:sidebar.item icon="wallet" href="{{ url('/admin/simpanan-sukarela') }}" wire:navigate :current="request()->is('admin/simpanan-sukarela')">Simpanan Sukarela</flux:sidebar.item>
                <flux:sidebar.item icon="arrow-up-tray" href="{{ url('/admin/tarik-saldo') }}" wire:navigate :current="request()->is('admin/tarik-saldo')">Tarik Saldo</flux:sidebar.item>

                <flux:sidebar.item icon="banknotes" href="{{ url('/admin/pembiayaan-pinjaman') }}" wire:navigate :current="request()->is('admin/pembiayaan-pinjaman') || request()->is('admin/pembiayaan-pinjaman/*')">Pinjaman</flux:sidebar.item>

                <flux:sidebar.group expandable icon="qr-code" heading="Pembayaran" class="grid">
                    <flux:sidebar.item href="{{ url('/admin/ppob') }}" wire:navigate :current="request()->is('admin/ppob')">PPOB</flux:sidebar.item>
                    <flux:sidebar.item href="{{ url('/admin/lazis') }}" wire:navigate :current="request()->is('admin/lazis')">Lazis</flux:sidebar.item>
                </flux:sidebar.group>
                
                <flux:sidebar.group expandable icon="document-check" heading="Persetujuan" class="grid">
                    <flux:sidebar.item wire:navigate href="#">Pengajuan Pinjaman</flux:sidebar.item>
                    <flux:sidebar.item wire:navigate href="{{ url('admin/persetujuan/penarikan-saldo') }}">Pengajuan Penarikan</flux:sidebar.item>
                    <flux:sidebar.item wire:navigate href="{{ url('admin/persetujuan/simpanan-sukarela') }}">Pengajuan Perubahan SS</flux:sidebar.item>
                    <flux:sidebar.item wire:navigate href="{{ url('admin/persetujuan/registrasi-anggota') }}">Pendaftaran Anggota</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
            <flux:sidebar.spacer />
            <flux:sidebar.nav>
                <div x-data class="mt-2">
                    <flux:button variant="subtle" x-on:click="$flux.dark = ! $flux.dark" class="w-full flex justify-start items-center">
                        <flux:icon.sun x-show="$flux.appearance === 'light'" variant="outline" class="w-5 h-5" />
                        <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="outline" class="w-5 h-5" />
                        <span class="ml-3" x-show="$flux.appearance === 'light'">Mode Gelap</span>
                        <span class="ml-3" x-show="$flux.appearance === 'dark'">Mode Terang</span>
                    </flux:button>
                </div>
            </flux:sidebar.nav>
            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile avatar="https://fluxui.dev/img/demo/user.png" name="{{ auth()->user()->name ?? 'Administrator' }}" />
                <flux:menu>
                    <flux:menu.item icon="user" class="font-semibold">{{ auth()->user()->name ?? 'Administrator' }}</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ url('/logout') }}">Logout</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:button variant="subtle" size="sm" x-data x-on:click="$flux.dark = ! $flux.dark" class="rounded-full !px-2 mr-2">
                <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
                <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
            </flux:button>
            <flux:dropdown position="top" align="start">
                <flux:profile avatar="/img/demo/user.png" />
                <flux:menu>
                    <flux:menu.item icon="user" class="font-semibold">{{ auth()->user()->name ?? 'Administrator' }}</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ url('/logout') }}">Logout</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>
        <flux:main>
            {{ $slot }}
        </flux:main>
        <flux:toast />
        @livewireScripts
        @fluxScripts
    </body>
</html>

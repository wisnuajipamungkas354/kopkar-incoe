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
                {{-- Dashboard --}}
                <flux:sidebar.item icon="home" href="{{ url('/admin') }}" wire:navigate :current="request()->is('admin')">Dashboard</flux:sidebar.item>

                {{-- Persetujuan --}}
                <flux:sidebar.group expandable icon="clipboard-document-check" heading="Persetujuan" class="grid" :expanded="request()->is('admin/persetujuan*')">
                    <flux:sidebar.item icon="user-plus" href="{{ url('/admin/persetujuan/registrasi-anggota') }}" wire:navigate :current="request()->is('admin/persetujuan/registrasi-anggota')">Registrasi Anggota</flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" href="{{ url('/admin/persetujuan/simpanan-sukarela') }}" wire:navigate :current="request()->is('admin/persetujuan/simpanan-sukarela')">Simpanan Sukarela</flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" href="{{ url('/admin/persetujuan/penarikan-saldo') }}" wire:navigate :current="request()->is('admin/persetujuan/penarikan-saldo')">Penarikan Saldo</flux:sidebar.item>
                    <flux:sidebar.item icon="building-library" href="{{ url('/admin/persetujuan/pembiayaan') }}" wire:navigate :current="request()->is('admin/persetujuan/pembiayaan')">Pembiayaan</flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" href="{{ url('/admin/persetujuan/pinjaman') }}" wire:navigate :current="request()->is('admin/persetujuan/pinjaman')">Pinjaman</flux:sidebar.item>
                    <flux:sidebar.item icon="heart" href="{{ url('/admin/persetujuan/lazis') }}" wire:navigate :current="request()->is('admin/persetujuan/lazis')">LAZIS</flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Simpanan & Penarikan --}}
                <flux:sidebar.group expandable icon="wallet" heading="Simpanan & Penarikan" class="grid" :expanded="request()->is('admin/simpanan-sukarela') || request()->is('admin/tarik-saldo')">
                    <flux:sidebar.item icon="banknotes" href="{{ url('/admin/simpanan-sukarela') }}" wire:navigate :current="request()->is('admin/simpanan-sukarela')">Simpanan Sukarela</flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" href="{{ url('/admin/tarik-saldo') }}" wire:navigate :current="request()->is('admin/tarik-saldo')">Penarikan Saldo</flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Pembiayaan & Pinjaman --}}
                <flux:sidebar.item icon="building-library" href="{{ url('/admin/pembiayaan') }}" wire:navigate :current="request()->is('admin/pembiayaan') || request()->is('admin/pembiayaan/*')">Pembiayaan</flux:sidebar.item>
                <flux:sidebar.item icon="credit-card" href="{{ url('/admin/pinjaman') }}" wire:navigate :current="request()->is('admin/pinjaman') || request()->is('admin/pinjaman/*')">Pinjaman</flux:sidebar.item>

                {{-- Payroll & Potongan --}}
                <flux:sidebar.group expandable icon="calculator" heading="Payroll & Potongan" class="grid" :expanded="request()->is('admin/potongan-payroll') || request()->is('admin/ppob') || request()->is('admin/lazis')">
                    <flux:sidebar.item icon="table-cells" href="{{ url('/admin/potongan-payroll') }}" wire:navigate :current="request()->is('admin/potongan-payroll')">Potongan Payroll</flux:sidebar.item>
                    <flux:sidebar.item icon="bolt" href="{{ url('/admin/ppob') }}" wire:navigate :current="request()->is('admin/ppob')">PPOB</flux:sidebar.item>
                    <flux:sidebar.item icon="heart" href="{{ url('/admin/lazis') }}" wire:navigate :current="request()->is('admin/lazis')">LAZIS</flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Kas Koperasi --}}
                <flux:sidebar.item icon="building-library" href="{{ url('/admin/mutasi-kas') }}" wire:navigate :current="request()->is('admin/mutasi-kas')">Kas Koperasi</flux:sidebar.item>

                {{-- Laporan --}}
                <flux:sidebar.group expandable icon="chart-bar" heading="Laporan" class="grid">
                    <flux:sidebar.item icon="document-chart-bar" href="{{ url('/admin/laporan/simpanan') }}" wire:navigate :current="request()->is('admin/laporan/simpanan')">Laporan Simpanan</flux:sidebar.item>
                    <flux:sidebar.item icon="document-chart-bar" href="{{ url('/admin/laporan/pinjaman') }}" wire:navigate :current="request()->is('admin/laporan/pinjaman')">Laporan Pinjaman</flux:sidebar.item>
                    <flux:sidebar.item icon="document-chart-bar" href="{{ url('/admin/laporan/kas') }}" wire:navigate :current="request()->is('admin/laporan/kas')">Laporan Kas</flux:sidebar.item>
                    <flux:sidebar.item icon="document-chart-bar" href="{{ url('/admin/laporan/payroll') }}" wire:navigate :current="request()->is('admin/laporan/payroll')">Laporan Payroll</flux:sidebar.item>
                    <flux:sidebar.item icon="document-chart-bar" href="{{ url('/admin/laporan/shu') }}" wire:navigate :current="request()->is('admin/laporan/shu')">Laporan SHU</flux:sidebar.item>
                </flux:sidebar.group>

                {{-- Master Data --}}
                <flux:sidebar.group expandable icon="circle-stack" heading="Master Data" class="grid" :expanded="request()->is('admin/employee*') || request()->is('admin/koperasi-staff*') || request()->is('admin/koperasi-management*') || request()->is('admin/nama-bank') || request()->is('admin/anggota') || request()->is('admin/anggota/*') || request()->is('admin/kategori-ppob')">
                    <flux:sidebar.item icon="users" href="{{ url('/admin/anggota') }}" wire:navigate :current="request()->is('admin/anggota') || request()->is('admin/anggota/*')">Anggota</flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" href="{{ url('/admin/employee') }}" wire:navigate :current="request()->is('admin/employee') || request()->is('admin/employee/*')">Karyawan</flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" href="{{ url('/admin/koperasi-staff') }}" wire:navigate :current="request()->is('admin/koperasi-staff') || request()->is('admin/koperasi-staff/*')">Staff Koperasi</flux:sidebar.item>
                    <flux:sidebar.item icon="identification" href="{{ url('/admin/koperasi-management') }}" wire:navigate :current="request()->is('admin/koperasi-management') || request()->is('admin/koperasi-management/*')">Pengurus Koperasi</flux:sidebar.item>
                    <flux:sidebar.item icon="credit-card" href="{{ url('/admin/nama-bank') }}" wire:navigate :current="request()->is('admin/nama-bank')">Nama Bank</flux:sidebar.item>
                    <flux:sidebar.item icon="bolt" href="{{ url('/admin/kategori-ppob') }}" wire:navigate :current="request()->is('admin/kategori-ppob')">Kategori PPOB</flux:sidebar.item>
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

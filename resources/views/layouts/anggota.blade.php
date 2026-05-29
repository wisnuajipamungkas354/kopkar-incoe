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
        @pwaHead
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
      <flux:header container class="max-lg:hidden bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
          <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
          <flux:brand href="#" logo="{{ asset('img/kki-icon-2-light.png') }}" class="max-lg:hidden dark:hidden" />
          <flux:brand href="#" logo="{{ asset('img/kki-icon-2-dark.png') }}" class="max-lg:hidden! hidden dark:flex" />
          <flux:navbar class="-mb-px max-lg:hidden">
              <flux:navbar.item icon="home" href="{{ url('anggota') }}" wire:navigate :current="request()->is('anggota')">Beranda</flux:navbar.item>
              <flux:navbar.item icon="wallet" href="{{ url('anggota/dompet') }}" wire:navigate :current="request()->is('anggota/dompet')">Dompet</flux:navbar.item>
              <flux:navbar.item icon="banknotes" href="{{ url('anggota/pembiayaan-pinjaman') }}" wire:navigate :current="request()->is('anggota/pembiayaan-pinjaman') || request()->is('anggota/pembiayaan-pinjaman/*')">Pinjaman</flux:navbar.item>
              <flux:navbar.item icon="qr-code" href="{{ url('anggota/pembayaran') }}" wire:navigate :current="request()->is('anggota/pembayaran') || request()->is('anggota/pembayaran/*')">Pembayaran</flux:navbar.item>
          </flux:navbar>
          <flux:spacer />
          <flux:navbar class="me-4">
              <flux:button variant="subtle" size="sm" x-data x-on:click="$flux.dark = ! $flux.dark" class="rounded-full !px-2" tabindex="-1">
                <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
                <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
            </flux:button>
          </flux:navbar>
          <flux:dropdown position="top" align="start">
              <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />
              <flux:menu>
                  <flux:menu.item icon="user" class="font-semibold">{{ auth()->user()->userable->nama_lengkap ?? 'Anggota Koperasi' }}</flux:menu.item>
                  <flux:menu.item icon="user-circle" href="{{ url('anggota/profile') }}" wire:navigate>Profil Saya</flux:menu.item>
                  <flux:menu.separator />
                  <flux:menu.item icon="arrow-right-start-on-rectangle" href="{{ url('/logout') }}">Logout</flux:menu.item>
              </flux:menu>
          </flux:dropdown>
      </flux:header>
      <flux:sidebar sticky collapsible="mobile" class="hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
          <flux:sidebar.header>
              <flux:sidebar.brand
                  href="#"
                  logo="{{ asset('img/kki-icon-2-light.png') }}"
                  logo:dark="{{ asset('img/kki-icon-2-dark.png') }}"
              />
              <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
          </flux:sidebar.header>
          <flux:sidebar.nav>
              <flux:sidebar.item icon="home" href="{{ url('anggota') }}" wire:navigate :current="request()->is('anggota')">Beranda</flux:sidebar.item>
              <flux:sidebar.item icon="wallet" href="{{ url('anggota/dompet') }}" wire:navigate :current="request()->is('anggota/dompet')">Dompet</flux:sidebar.item>
              <flux:sidebar.item icon="banknotes" href="{{ url('anggota/pembiayaan-pinjaman') }}" wire:navigate :current="request()->is('anggota/pembiayaan-pinjaman') || request()->is('anggota/pembiayaan-pinjaman/*')">Pinjaman</flux:sidebar.item>
              <flux:sidebar.group expandable heading="Pembayaran" class="grid">
                  <flux:sidebar.item href="{{ url('anggota/ppob') }}" wire:navigate>PPOB</flux:sidebar.item>
                  <flux:sidebar.item href="{{ url('anggota/lazis') }}" wire:navigate>Lazis</flux:sidebar.item>
              </flux:sidebar.group>
          </flux:sidebar.nav>
          <flux:sidebar.spacer />
          <flux:sidebar.nav>
              <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
              <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
          </flux:sidebar.nav>
      </flux:sidebar>
      <flux:main container class="max-lg:pb-24">
         <!-- Mobile Top Bar -->
         <div class="lg:hidden flex items-center justify-between mb-6">
             <div class="flex items-center gap-2">
                 <img src="{{ asset('img/kki-icon-2-light.png') }}" class="h-8 dark:hidden" alt="Logo">
                 <img src="{{ asset('img/kki-icon-2-dark.png') }}" class="h-8 hidden dark:block" alt="Logo">
             </div>
             <flux:button variant="subtle" size="sm" x-data x-on:click="$flux.dark = ! $flux.dark" class="rounded-full !px-2" tabindex="-1">
                 <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
                 <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
             </flux:button>
         </div>

         {{ $slot }}
      </flux:main>

      <!-- Bottom Navigation Bar (Mobile Only) -->
      <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800 z-50 px-6 py-3 flex justify-between items-center pb-safe">
          <a href="{{ url('anggota') }}" wire:navigate class="flex flex-col items-center gap-1 transition-colors {{ request()->is('anggota') ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
              <flux:icon name="home" variant="{{ request()->is('anggota') ? 'solid' : 'outline' }}" class="w-6 h-6" />
              <span class="text-[10px] font-medium">Beranda</span>
          </a>
          
          <a href="{{ url('anggota/dompet') }}" wire:navigate class="flex flex-col items-center gap-1 transition-colors {{ request()->is('anggota/dompet') ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
              <flux:icon name="wallet" variant="{{ request()->is('anggota/dompet') ? 'solid' : 'outline' }}" class="w-6 h-6" />
              <span class="text-[10px] font-medium">Dompet</span>
          </a>

          <a href="{{ url('anggota/pembiayaan-pinjaman') }}" wire:navigate class="flex flex-col items-center gap-1 transition-colors {{ request()->is('anggota/pembiayaan-pinjaman') || request()->is('anggota/pembiayaan-pinjaman/*') ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
              <flux:icon name="banknotes" variant="{{ request()->is('anggota/pembiayaan-pinjaman') || request()->is('anggota/pembiayaan-pinjaman/*') ? 'solid' : 'outline' }}" class="w-6 h-6" />
              <span class="text-[10px] font-medium">Pinjaman</span>
          </a>

          <a href="{{ url('anggota/pembayaran') }}" wire:navigate class="flex flex-col items-center gap-1 transition-colors {{ request()->is('anggota/pembayaran') || request()->is('anggota/pembayaran/*') ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
              <flux:icon name="banknotes" variant="{{ request()->is('anggota/pembayaran') || request()->is('anggota/pembayaran/*') ? 'solid' : 'outline' }}" class="w-6 h-6" />
              <span class="text-[10px] font-medium">Bayar</span>
          </a>

          <!-- <flux:dropdown position="top" align="center">
              <button class="flex flex-col items-center gap-1 transition-colors {{ request()->is('anggota/ppob') || request()->is('anggota/lazis') ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                  <flux:icon name="qr-code" variant="{{ request()->is('anggota/ppob') || request()->is('anggota/lazis') ? 'solid' : 'outline' }}" class="w-6 h-6" />
                  <span class="text-[10px] font-medium">Bayar</span>
              </button>
              
              <flux:menu>
                  <flux:menu.item href="{{ url('anggota/ppob') }}" wire:navigate>PPOB</flux:menu.item>
                  <flux:menu.item href="{{ url('anggota/lazis') }}" wire:navigate>Lazis</flux:menu.item>
              </flux:menu>
          </flux:dropdown> -->

          <a href="{{ url('anggota/profile') }}" wire:navigate class="flex flex-col items-center gap-1 transition-colors {{ request()->is('anggota/profile') ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
              <flux:icon name="user" variant="{{ request()->is('anggota/profile') ? 'solid' : 'outline' }}" class="w-6 h-6" />
              <span class="text-[10px] font-medium">Profil</span>
          </a>
      </div>
        @livewireScripts
        @fluxScripts
        @laravelPwa
        @pwaUpdateNotifier
        @pwaInstallButton
    </body>
</html>

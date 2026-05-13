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
      <flux:header container class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
          <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
          <flux:brand href="#" logo="{{ asset('img/kki-icon-2-light.png') }}" class="max-lg:hidden dark:hidden" />
          <flux:brand href="#" logo="{{ asset('img/kki-icon-2-dark.png') }}" class="max-lg:hidden! hidden dark:flex" />
          <flux:navbar class="-mb-px max-lg:hidden">
              <flux:navbar.item icon="home" href="{{ url('anggota') }}" wire:navigate current>Home</flux:navbar.item>
              <flux:dropdown class="max-lg:hidden">
                  <flux:navbar.item icon="wallet" icon:trailing="chevron-down">Simpanan</flux:navbar.item>
                  <flux:navmenu>
                      <flux:navmenu.item href="{{ url('anggota/simpanan-pokok') }}" wire:navigate>Pokok</flux:navmenu.item>
                      <flux:navmenu.item href="{{ url('anggota/simpanan-wajib') }}" wire:navigate>Wajib</flux:navmenu.item>
                      <flux:navmenu.item href="{{ url('anggota/simpanan-sukarela') }}" wire:navigate>Sukarela</flux:navmenu.item>
                  </flux:navmenu>
              </flux:dropdown>
              <flux:separator vertical variant="subtle" class="my-2"/>
              <flux:dropdown class="max-lg:hidden">
                  <flux:navbar.item icon="document-text" icon:trailing="chevron-down">Pengajuan</flux:navbar.item>
                  <flux:navmenu>
                      <flux:navmenu.item href="#">Pembiayaan & Pinjaman</flux:navmenu.item>
                      <flux:navmenu.item href="#">Tarik Saldo</flux:navmenu.item>
                      <flux:navmenu.item href="#">PPOB</flux:navmenu.item>
                      <flux:navmenu.item href="#">Lazis</flux:navmenu.item>
                  </flux:navmenu>
              </flux:dropdown>
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
                  <flux:menu.radio.group>
                      <flux:menu.radio checked>Olivia Martin</flux:menu.radio>
                      <flux:menu.radio>Truly Delta</flux:menu.radio>
                  </flux:menu.radio.group>
                  <flux:menu.separator />
                  <flux:menu.item icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
              </flux:menu>
          </flux:dropdown>
      </flux:header>
      <flux:sidebar sticky collapsible="mobile" class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
          <flux:sidebar.header>
              <flux:sidebar.brand
                  href="#"
                  logo="{{ asset('img/kki-icon-2-light.png') }}"
                  logo:dark="{{ asset('img/kki-icon-2-dark.png') }}"
              />
              <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
          </flux:sidebar.header>
          <flux:sidebar.nav>
              <flux:sidebar.item icon="home" href="#" current>Home</flux:sidebar.item>
              <flux:sidebar.group expandable heading="Simpanan" class="grid">
                  <flux:sidebar.item href="{{ url('anggota/simpanan-pokok') }}">Pokok</flux:sidebar.item>
                  <flux:sidebar.item href="{{ url('anggota/simpanan-wajib') }}">Wajib</flux:sidebar.item>
                  <flux:sidebar.item href="{{ url('anggota/simpanan-sukarela') }}">Sukarela</flux:sidebar.item>
              </flux:sidebar.group>
              <flux:sidebar.group expandable heading="Pengajuan" class="grid">
                  <flux:sidebar.item href="#">Pembiayaan & Pinjaman</flux:sidebar.item>
                  <flux:navmenu.item href="#">Tarik Saldo</flux:navmenu.item>
                  <flux:sidebar.item href="#">PPOB</flux:sidebar.item>
                  <flux:sidebar.item href="#">Lazis</flux:sidebar.item>
              </flux:sidebar.group>
          </flux:sidebar.nav>
          <flux:sidebar.spacer />
          <flux:sidebar.nav>
              <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
              <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
          </flux:sidebar.nav>
      </flux:sidebar>
      <flux:main container>
         {{ $slot }}
      </flux:main>
        @livewireScripts
        @fluxScripts
    </body>
</html>

<?php

use Livewire\Component;
use Livewire\Layout;
use Livewire\Attributes\Validate;

new #[Layout('layouts::app')] class extends Component
{
    #[Validate('required', 'Username wajib diisi')]
    public $username;
    #[Validate('required', 'Password wajib diisi')]
    public $password;
    public $remember;

    public function login()
    {
        $this->validate();

        $credentials = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        $remember = $this->remember ?? false;

        if (! Auth::attempt($credentials, $remember)) {
            $this->addError('username', 'Username atau password salah.');

            return;
        }

        $user = Auth::user();

        // Cek status akun aktif
        if (! $user->status_user === 1) {

            Auth::logout();

            $this->addError('username', 'Akun anda tidak aktif.');

            return;
        }

        // Cek approval anggota
        if (! $user->ext_is_approved) {

            Auth::logout();

            $this->addError('username', 'Akun anda belum disetujui.');

            return;
        }

        Session::regenerate();

        // Redirect sesuai kebutuhan
        return redirect()->intended('admin/');
    }
};
?>

<!-- Wrapper utama ditambahkan Alpine.js untuk logika Dark Mode -->
<div class="relative w-full max-w-sm mx-auto mt-10">
    <!-- Tombol Toggle Dark/Light Mode -->
    <div class="absolute -top-12 right-0">
        <flux:button variant="subtle" size="sm" x-data x-on:click="$flux.dark = ! $flux.dark" class="rounded-full !px-2" tabindex="-1">
            <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
            <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
        </flux:button>
    </div>

    <!-- Area Logo Dinamis (Light & Dark) -->
    <div class="flex justify-center mb-6 mt-4">
        <!-- Logo Light Mode -->
        <img x-show="$flux.appearance === 'light'" 
             src="{{ asset('img/kki-icon-2-light.png') }}" 
             alt="Logo KKI" 
             class="h-20 w-auto">
             
        <!-- Logo Dark Mode (x-cloak mencegah gambar berkedip saat pertama kali dimuat) -->
        <img x-show="$flux.appearance === 'dark'"
             src="{{ asset('img/kki-icon-2-dark.png') }}" 
             alt="Logo KKI Dark" 
             class="h-20 w-auto">
    </div>

    <!-- Card Login Flux UI -->
    <flux:card>
        <form wire:submit="login" class="space-y-6">
            
            <div class="text-center mb-4">
                <flux:heading size="xl">Selamat Datang</flux:heading>
                <flux:subheading>Silakan masuk ke akun Anda</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Username</flux:label>
                <flux:input type="text" wire:model="username" placeholder="Masukkan username" required autofocus tabindex="1"/>
                <flux:error name="username" />
            </flux:field>

            <flux:field>
                <div class="flex items-center justify-between mb-1">
                    <flux:label>Password</flux:label>
                    <a href="/forgot-password" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300" tabindex="-1">
                        Lupa password?
                    </a>
                </div>
                <flux:input type="password" wire:model="password" placeholder="••••••••" required tabindex="2" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="remember" label="Ingat saya" tabindex="3" />
            </flux:field>

            <div class="mt-6">
                <flux:button type="submit" variant="primary" class="w-full">
                    Masuk
                </flux:button>
            </div>

        </form>
    </flux:card>
    
    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        Belum menjadi anggota? 
        <a href="{{ url('/register')}}" wire:navigate class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
            Daftar sekarang
        </a>
    </p>
</div>
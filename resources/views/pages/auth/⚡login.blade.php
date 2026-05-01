<?php

use Livewire\Component;
use Livewire\Layout;

new #[Layout('layouts::app')] class extends Component
{
    //
};
?>

<!-- Wrapper utama ditambahkan Alpine.js untuk logika Dark Mode -->
<div class="relative w-full max-w-sm mx-auto mt-10"
     x-data="{
        theme: localStorage.getItem('theme') || 
               (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        toggleTheme() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', this.theme);
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
        }
     }"
>
    <!-- Tombol Toggle Dark/Light Mode -->
    <div class="absolute -top-12 right-0">
        <flux:button variant="subtle" size="sm" @click="toggleTheme" class="rounded-full !px-2">
            <svg x-show="theme === 'light'" class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <svg x-show="theme === 'dark'" x-cloak class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </flux:button>
    </div>

    <!-- Area Logo Dinamis (Light & Dark) -->
    <div class="flex justify-center mb-6 mt-4">
        <!-- Logo Light Mode -->
        <img x-show="theme === 'light'" 
             src="{{ asset('img/kki-icon-2-light.png') }}" 
             alt="Logo KKI" 
             class="h-20 w-auto">
             
        <!-- Logo Dark Mode (x-cloak mencegah gambar berkedip saat pertama kali dimuat) -->
        <img x-show="theme === 'dark'" x-cloak 
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
                <flux:input type="text" wire:model="username" placeholder="Masukkan username" required autofocus />
                <flux:error name="username" />
            </flux:field>

            <flux:field>
                <div class="flex items-center justify-between mb-1">
                    <flux:label>Password</flux:label>
                    <a href="/forgot-password" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                        Lupa password?
                    </a>
                </div>
                <flux:input type="password" wire:model="password" placeholder="••••••••" required />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="remember" label="Ingat saya" />
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
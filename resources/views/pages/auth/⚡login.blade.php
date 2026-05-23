<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\Layout;
use Livewire\Attributes\Validate;

new #[Layout('layouts::app')] class extends Component
{
    #[Validate('required', message: 'Username wajib diisi')]
    #[Validate('min:4', message: 'Username minimal 4 karakter')]
    public $username;
    #[Validate('required', message: 'Password wajib diisi')]
    #[Validate('min:8', message: 'Password minimal 8 karakter')]
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

        $isActiveStaff = false;
        $isActiveManagement = false;
        $isActiveMember = false;
        $isPendingMember = false;

        if ($user->isKoperasiStaff()) {
            if ($user->userable && $user->userable->employment_status === 'active') {
                $isActiveStaff = true;
            }
        } elseif ($user->isEmployee()) {
            $employee = $user->userable;
            if ($employee) {
                // Cek management aktif
                if ($employee->koperasiManagements()->where('status', 'active')->exists()) {
                    $isActiveManagement = true;
                }
                
                // Cek member aktif & approved
                $member = $employee->koperasiMember;
                if ($member) {
                    if ($member->status === 'active' && $member->is_approved) {
                        $isActiveMember = true;
                    } elseif ($member->status === 'pending' || !$member->is_approved) {
                        $isPendingMember = true;
                    }
                }
            }
        }

        // Jika staff atau management aktif, arahkan ke admin
        if ($isActiveStaff || $isActiveManagement) {
            Session::regenerate();
            return redirect()->intended('admin/');
        }

        // Jika member aktif (dan bukan admin aktif), arahkan ke anggota
        if ($isActiveMember) {
            Session::regenerate();
            return redirect()->intended('anggota/');
        }

        // Jika tidak memiliki akses yang valid/aktif, paksa logout dan tampilkan error
        Auth::logout();

        if ($isPendingMember) {
            $this->addError('username', 'Akun anda belum di approve oleh ketua.');
        } else {
            $this->addError('username', 'Akun anda tidak aktif.');
        }

        return;
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

            @if (session('status'))
                <div class="bg-green-50 text-green-700 p-3 rounded-lg text-sm text-center">
                    {{ session('status') }}
                </div>
            @endif

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
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts::anggota')] class extends Component
{
    #[Computed]
    public function totalSimpananWajib()
    {
        // Dummy data, ganti dengan query aktual ke database nantinya
        return 2500000;
    }

    #[Computed]
    public function totalSimpananSukarela()
    {
        // Dummy data, ganti dengan query aktual ke database nantinya
        return 1250000;
    }

    #[Computed]
    public function totalSimpananLainnya()
    {
        // Dummy data, ganti dengan query aktual ke database nantinya
        return 250000;
    }

    #[Computed]
    public function pinjamanAktif()
    {
        // Dummy data, ganti dengan query aktual ke database nantinya
        return 5000000;
    }

    #[Computed]
    public function sisaCicilan()
    {
        // Dummy data, ganti dengan query aktual ke database nantinya
        return 2000000;
    }
};
?>

<div>
    <flux:heading size="xl" level="1">Dashboard Anggota</flux:heading>
    <flux:text class="mt-2 mb-6 text-base">Berikut adalah ringkasan simpanan dan pinjaman Anda saat ini.</flux:text>
    <flux:separator variant="subtle" />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-xl">
                <flux:icon name="wallet" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Simpanan</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->totalSimpananWajib, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-xl">
                <flux:icon name="banknotes" class="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo Simpanan Sukarela</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->totalSimpananSukarela, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-xl">
                <flux:icon name="ticket" class="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">SHU & Simpanan Lain</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->totalSimpananLainnya, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-red-100 dark:bg-red-900/40 rounded-xl">
                <flux:icon name="credit-card" class="w-8 h-8 text-red-600 dark:text-red-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pinjaman Aktif</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->pinjamanAktif, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/40 rounded-xl">
                <flux:icon name="clock" class="w-8 h-8 text-orange-600 dark:text-orange-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sisa Cicilan</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->sisaCicilan, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>
    </div>
</div>
<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="relative w-full max-w-3xl mx-auto mt-10 mb-20">
    <!-- Tombol Toggle Dark/Light Mode -->
    <div class="absolute -top-12 right-0">
        <flux:button variant="subtle" size="sm" x-data x-on:click="$flux.dark = ! $flux.dark" class="rounded-full !px-2">
            <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
            <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
        </flux:button>
    </div>

    <flux:card class="max-w-lg w-full text-center py-10 px-8 mx-auto my-auto">
        <!-- Icon Berhasil (Custom Flux Style) -->
        <div class="mb-6 flex justify-center">
            <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-4">
                <flux:icon.check circle variant="solid" class="text-green-600 dark:text-green-400 size-12" />
            </div>
        </div>

        <!-- Heading & Deskripsi -->
        <flux:heading size="xl" class="mb-2">Pendaftaran Berhasil!</flux:heading>
        
        <flux:text size="md" class="mb-8">
            Terima kasih, <b>{{ request('nama') ?? 'Calon Anggota' }}</b>. <br> 
            Data pendaftaran Anda telah kami terima dan sedang dalam proses verifikasi oleh tim Koperasi Konsumen Incoe (KKI).
        </flux:text>

        <!-- Informasi Tambahan (Status Box) -->
        <div class="bg-zinc-50 dark:bg-white/5 border border-zinc-200 dark:border-white/10 rounded-xl p-4 mb-8 text-left">
            <div class="flex items-start gap-3">
                <flux:icon.information-circle class="text-zinc-400 size-5 mt-0.5" />
                <div class="space-y-1">
                    <flux:heading size="sm">Langkah Selanjutnya:</flux:heading>
                    <flux:text size="sm">
                        Kami akan menghubungi Anda melalui nomor <b>WhatsApp</b> yang telah didaftarkan untuk proses administrasi lebih lanjut.
                    </flux:text>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <flux:button href="/" wire:navigate variant="subtle" class="w-full sm:w-auto">
                Kembali ke Beranda
            </flux:button>
            
            <flux:button href="{{ url('login') }}" wire:navigate variant="primary" class="w-full sm:w-auto">
                Ke Halaman Login
            </flux:button>
        </div>

        <!-- Footer / Bantuan -->
        <flux:separator class="my-8" />
        
        <flux:text size="xs" class="text-zinc-500">
            Butuh bantuan? Hubungi Sekretariat KKI atau melalui <br>
            <a href="#" class="text-indigo-600 dark:text-indigo-400 hover:underline">Pusat Bantuan Digital</a>
        </flux:text>
    </flux:card>
</div>
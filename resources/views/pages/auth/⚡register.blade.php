<?php

use Livewire\Component;
use Livewire\Layout;

new #[Layout('layouts::app')] class extends Component
{
    // App\Livewire\Register.php
    public $nama_lengkap, $npk, $tempat_lahir, $tanggal_lahir, $alamat, $pendidikan_terakhir;
    public $no_wa, $email, $bank_rekening, $nama_ahli_waris, $persetujuan;

    // Tambahkan ini untuk memicu input "Sebutkan"
    public $hubungan_ahli_waris = ''; 
    public $hubungan_lainnya;
};
?>

<div class="relative w-full max-w-3xl mx-auto mt-10 mb-20"
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

    <!-- Area Logo Dinamis -->
    <div class="flex justify-center mb-6 mt-4">
        <img x-show="theme === 'light'" src="{{ asset('img/kki-icon-2-light.png') }}" alt="Logo KKI" class="h-20 w-auto">
        <img x-show="theme === 'dark'" x-cloak src="{{ asset('img/kki-icon-2-dark.png') }}" alt="Logo KKI Dark" class="h-20 w-auto">
    </div>

    <flux:card>
        <form wire:submit="register" class="space-y-6">
            
            <!-- Heading -->
            <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700 pb-4">
                <flux:heading size="xl">Pendaftaran Anggota Baru</flux:heading>
                <flux:subheading>Silakan lengkapi formulir di bawah ini untuk bergabung dengan KKI.</flux:subheading>
            </div>

            <!-- Grid 2 Kolom untuk Input Data -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <flux:field>
                    <flux:label>Nama Lengkap</flux:label>
                    <flux:input wire:model="nama_lengkap" placeholder="Masukkan nama lengkap sesuai KTP" required autofocus />
                </flux:field>

                <flux:field>
                    <flux:label>NPK</flux:label>
                    <flux:input wire:model="npk" placeholder="Nomor Pokok Karyawan" required />
                </flux:field>

                <flux:field>
                    <flux:label>Tempat Lahir</flux:label>
                    <flux:input wire:model="tempat_lahir" placeholder="Kota/Kabupaten kelahiran" required />
                </flux:field>

                <flux:field>
                    <flux:label>Tanggal Lahir</flux:label>
                    <flux:input type="date" wire:model="tanggal_lahir" required />
                </flux:field>

                <!-- Field Alamat memakan 2 kolom penuh di layar besar -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Alamat Lengkap</flux:label>
                        <flux:textarea wire:model="alamat" rows="3" placeholder="Masukkan alamat lengkap" required />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Pendidikan Terakhir</flux:label>
                    <flux:select wire:model="pendidikan_terakhir" placeholder="Pilih pendidikan terakhir" required>
                        <flux:select.option value="SMA/SMK">SMA / SMK Sederajat</flux:select.option>
                        <flux:select.option value="D3">Diploma 3 (D3)</flux:select.option>
                        <flux:select.option value="S1">Strata 1 (S1) / D4</flux:select.option>
                        <flux:select.option value="S2">Strata 2 (S2)</flux:select.option>
                        <flux:select.option value="S3">Strata 3 (S3)</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>No. WhatsApp</flux:label>
                    <flux:input type="tel" wire:model="no_wa" placeholder="08xxxxxxxxxx" required />
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="email@contoh.com" required />
                </flux:field>

                <flux:field>
                    <flux:label>Bank & Nomor Rekening</flux:label>
                    <flux:input wire:model="bank_rekening" placeholder="Contoh: BCA - 1234567890" required />
                </flux:field>

                <flux:field>
                    <flux:label>Nama Ahli Waris</flux:label>
                    <flux:input wire:model="nama_ahli_waris" placeholder="Nama lengkap ahli waris" required />
                </flux:field>

                <!-- Menggunakan wire:model.live agar reaktif saat memilih 'Lainnya' -->
                <flux:field>
                    <flux:label>Hubungan Ahli Waris</flux:label>
                    <flux:select wire:model.live="hubungan_ahli_waris" placeholder="Pilih hubungan" required>
                        <flux:select.option value="Istri/Suami">Istri / Suami</flux:select.option>
                        <flux:select.option value="Anak">Anak</flux:select.option>
                        <flux:select.option value="Orang Tua">Orang Tua</flux:select.option>
                        <flux:select.option value="Lainnya">Lainnya</flux:select.option>
                    </flux:select>
                </flux:field>

                <!-- Input tambahan jika memilih "Lainnya" -->
                @if($hubungan_ahli_waris === 'Lainnya')
                    <flux:field class="md:col-span-2">
                        <flux:label>Sebutkan Hubungan Ahli Waris</flux:label>
                        <flux:input wire:model="hubungan_lainnya" placeholder="Sebutkan hubungan spesifik (contoh: Saudara Kandung)" required />
                    </flux:field>
                @endif
                
            </div>

            <!-- Bagian Persetujuan -->
            
            <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:checkbox.group label="Persetujuan" variant="cards" class="flex-col">
                    <flux:checkbox value="setuju">
                        <flux:checkbox.indicator />
                        <div class="flex-1">
                            <flux:heading class="leading-4">Bersedia</flux:heading>
                            <flux:text size="sm" class="mt-2">Bersedia membayar Simpanan Pokok (SIMPOK) sebesar <b>Rp50.000,-/bulan</b> dan Simpanan Wajib (SIWA) sebesar <b>Rp150.000,-/bulan</b> serta bersedia memenuhi segala peraturan yang berlaku di Koperasi Konsumen Incoe (KKI).</flux:text>
                        </div>
                    </flux:checkbox>
                </flux:checkbox.group>
            </div>

            <!-- Catatan Informasi Tambahan -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mt-4">
                <flux:heading size="sm" class="text-blue-800 dark:text-blue-400 mb-2">Catatan Penting:</flux:heading>
                <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>Simpanan Pokok dapat disetor langsung ke Koperasi Karyawan Incoe (KKI).</li>
                    <li>Semua data wajib diisi dengan lengkap dan benar untuk memudahkan akses transaksi.</li>
                </ul>
            </div>

            <!-- Tombol Submit -->
            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="{{ url('login') }}" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary">Kirim Pendaftaran</flux:button>
            </div>

        </form>
    </flux:card>
</div>
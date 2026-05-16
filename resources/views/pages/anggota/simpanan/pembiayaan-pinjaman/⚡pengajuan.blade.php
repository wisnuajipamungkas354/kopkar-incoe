<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::anggota', ['title' => 'Formulir Pengajuan Pembiayaan & Pinjaman'])] class extends Component
{
    // Form State
    public $jenisPengajuan = 'pembiayaan';
    
    public $pembiayaanBarang = false;
    public $deskripsiBarang = '';
    public $nominalBarang = '';

    public $pembiayaanPendidikan = false;
    public $deskripsiPendidikan = '';
    public $nominalPendidikan = '';

    public $pembiayaanKesehatan = false;
    public $deskripsiKesehatan = '';
    public $nominalKesehatan = '';

    public $pembiayaanRenovasi = false;
    public $deskripsiRenovasi = '';
    public $nominalRenovasi = '';

    public $pembiayaanServis = false;
    public $deskripsiServis = '';
    public $nominalServis = '';

    public $pembiayaanLainnya = false;
    public $deskripsiLainnya = '';
    public $nominalLainnya = '';

    public $tenorPembiayaan = '';
    
    public $referensiNama = '';
    public $referensiTelp = '';
    public $referensiAlamat = '';

    public $nominalQard = '';
    public $tenorQard = '';

    public $nominalBon = '';

    public function submitPengajuan()
    {
        // Validasi dan penyimpanan data (dummy)
        
        // Tampilkan modal sukses
        $this->js("$flux.modal('sukses-pengajuan').show()");
    }
};
?>

<div>
    <div class="mb-6 hidden md:block">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="/anggota/simpanan/pembiayaan-pinjaman">Pembiayaan & Pinjaman</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Pengajuan Baru</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Pengajuan Pembiayaan & Pinjaman</flux:heading>
            <flux:text class="mt-2 text-base">Pilih kategori pengajuan dan lengkapi detail yang dibutuhkan.</flux:text>
        </div>
    </div>
    <flux:separator variant="subtle" />

    <form wire:submit.prevent="submitPengajuan" class="flex flex-col gap-6 mt-6">
        <x-ui.tabs wire:model="jenisPengajuan" class="overflow-x-auto">
            <x-ui.tab-list>
                <x-ui.tab name="pembiayaan">A. Pembiayaan</x-ui.tab>
                <x-ui.tab name="qard">B. Qard Hasan</x-ui.tab>
                <x-ui.tab name="bon">C. Bon Sementara</x-ui.tab>
            </x-ui.tab-list>

            <!-- A. PEMBIAYAAN -->
            <x-ui.tab-panel name="pembiayaan">
                <flux:card>
                    <div class="flex flex-col gap-6">
                        <div>
                            <flux:heading size="md">Detail Pembiayaan</flux:heading>
                            <flux:text size="sm" class="mt-1 text-zinc-500">Pilih jenis pembiayaan yang diajukan (Bisa lebih dari satu).</flux:text>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Pembiayaan Barang -->
                            <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <flux:checkbox wire:model.live="pembiayaanBarang" label="Pembiayaan Barang" />
                                @if($pembiayaanBarang)
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <flux:input wire:model="deskripsiBarang" label="Deskripsi Barang" placeholder="Contoh: Laptop / Kulkas..." required />
                                        <flux:input type="number" wire:model="nominalBarang" label="Nominal (Rp)" placeholder="Contoh: 5000000" required />
                                    </div>
                                @endif
                            </div>

                            <!-- Pembiayaan Pendidikan -->
                            <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <flux:checkbox wire:model.live="pembiayaanPendidikan" label="Pembiayaan Pendidikan" />
                                @if($pembiayaanPendidikan)
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <flux:input wire:model="deskripsiPendidikan" label="Deskripsi Pendidikan" placeholder="Contoh: SPP Anak Semester 2..." required />
                                        <flux:input type="number" wire:model="nominalPendidikan" label="Nominal (Rp)" placeholder="Contoh: 3000000" required />
                                    </div>
                                @endif
                            </div>

                            <!-- Pembiayaan Kesehatan -->
                            <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <flux:checkbox wire:model.live="pembiayaanKesehatan" label="Pembiayaan Kesehatan" />
                                @if($pembiayaanKesehatan)
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <flux:input wire:model="deskripsiKesehatan" label="Deskripsi Kesehatan" placeholder="Contoh: Biaya Rumah Sakit / Persalinan..." required />
                                        <flux:input type="number" wire:model="nominalKesehatan" label="Nominal (Rp)" placeholder="Contoh: 8000000" required />
                                    </div>
                                @endif
                            </div>

                            <!-- Pembiayaan Renovasi -->
                            <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <flux:checkbox wire:model.live="pembiayaanRenovasi" label="Pembiayaan Renovasi" />
                                @if($pembiayaanRenovasi)
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <flux:input wire:model="deskripsiRenovasi" label="Deskripsi Renovasi" placeholder="Contoh: Renovasi Atap Rumah..." required />
                                        <flux:input type="number" wire:model="nominalRenovasi" label="Nominal (Rp)" placeholder="Contoh: 10000000" required />
                                    </div>
                                @endif
                            </div>

                            <!-- Pembiayaan Servis Kendaraan -->
                            <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <flux:checkbox wire:model.live="pembiayaanServis" label="Pembiayaan Servis Kendaraan" />
                                @if($pembiayaanServis)
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <flux:input wire:model="deskripsiServis" label="Deskripsi Servis" placeholder="Contoh: Turun Mesin Mobil..." required />
                                        <flux:input type="number" wire:model="nominalServis" label="Nominal (Rp)" placeholder="Contoh: 4000000" required />
                                    </div>
                                @endif
                            </div>

                            <!-- Pembiayaan Lainnya -->
                            <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <flux:checkbox wire:model.live="pembiayaanLainnya" label="Pembiayaan Lainnya" />
                                @if($pembiayaanLainnya)
                                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <flux:input wire:model="deskripsiLainnya" label="Deskripsi Lainnya" placeholder="Sebutkan keperluan pembiayaan..." required />
                                        <flux:input type="number" wire:model="nominalLainnya" label="Nominal (Rp)" placeholder="Contoh: 2000000" required />
                                    </div>
                                @endif
                            </div>
                        </div>

                        <flux:separator variant="subtle" />

                        <div>
                            <flux:input type="number" wire:model="tenorPembiayaan" label="Tenor Pembiayaan (Bulan)" placeholder="Contoh: 12" required />
                        </div>

                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 flex flex-col gap-4">
                            <div>
                                <flux:heading size="sm">Referensi Pihak Ketiga</flux:heading>
                                <flux:text size="sm" class="text-zinc-500 mt-1">Isi referensi lembaga pendidikan, faskes, bengkel, toko, atau pihak lainnya.</flux:text>
                            </div>
                            <flux:input wire:model="referensiNama" label="Nama Lembaga/Toko/Pihak Ketiga" placeholder="Masukkan nama..." required />
                            <flux:input wire:model="referensiTelp" label="No Telp / WA" placeholder="Contoh: 081234567890" required />
                            <flux:textarea wire:model="referensiAlamat" label="Alamat" placeholder="Alamat lengkap pihak ketiga..." required />
                        </div>
                    </div>
                </flux:card>
            </x-ui.tab-panel>

            <!-- B. PINJAMAN QARD HASAN -->
            <x-ui.tab-panel name="qard">
                <flux:card>
                    <div class="flex flex-col gap-4">
                        <flux:heading size="md">Detail Pinjaman Qard Hasan</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">Pinjaman Qard Hasan maksimal Rp 5.000.000.</flux:text>
                        <flux:input type="number" wire:model="nominalQard" label="Nominal Qard (Max Rp 5.000.000)" max="5000000" placeholder="Contoh: 3000000" required />
                        <flux:input type="number" wire:model="tenorQard" label="Tenor Angsuran (Bulan)" placeholder="Contoh: 10" required />
                    </div>
                </flux:card>
            </x-ui.tab-panel>

            <!-- C. PINJAMAN BON SEMENTARA -->
            <x-ui.tab-panel name="bon">
                <flux:card>
                    <div class="flex flex-col gap-4">
                        <flux:heading size="md">Detail Pinjaman Bon Sementara</flux:heading>
                        <flux:text size="sm" class="text-orange-600 dark:text-orange-400">Penting: Pinjaman ini akan dipotong penuh satu kali pada siklus penggajian berikutnya.</flux:text>
                        <flux:input type="number" wire:model="nominalBon" label="Nominal Bon Sementara (Max Rp 1.000.000)" max="1000000" placeholder="Contoh: 1000000" required />
                    </div>
                </flux:card>
            </x-ui.tab-panel>
        </x-ui.tabs>

        <div class="flex justify-end gap-2 mt-4">
            <flux:button href="/anggota/pembiayaan-pinjaman" wire:navigate variant="ghost">Batal</flux:button>
            <flux:button type="submit" variant="primary" icon="paper-airplane">Kirim Pengajuan</flux:button>
        </div>
    </form>

    <!-- Modal Sukses -->
    <flux:modal name="sukses-pengajuan" class="md:w-md" :dismissible="false">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500 mb-2">
                <flux:icon name="check-circle" class="w-10 h-10" />
            </div>
            
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pengajuan pembiayaan/pinjaman Anda telah berhasil dikirim dan sedang menunggu proses verifikasi oleh pengurus.
            </flux:text>

            <div class="w-full mt-4">
                <flux:button href="/anggota/pembiayaan-pinjaman" wire:navigate variant="primary" class="w-full">Kembali ke Daftar Pinjaman</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

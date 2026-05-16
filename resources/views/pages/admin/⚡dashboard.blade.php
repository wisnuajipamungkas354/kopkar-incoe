<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

new #[Layout('layouts::admin', ['title' => 'Dashboard'])] class extends Component
{
    #[Computed]
    public function stats()
    {
        return [
            'total_anggota' => 1250,
            'total_simpanan' => 5450000000, // 5.45 M
            'total_pinjaman' => 3200000000, // 3.2 M
            'pendapatan_bulan_ini' => 150000000, // 150 Juta
            'pengajuan_pinjaman_pending' => 12,
            'pengajuan_tarik_saldo_pending' => 8,
            'anggota_baru_pending' => 5,
        ];
    }

    #[Computed]
    public function aktivitasTerbaru()
    {
        return collect([
            (object) [
                'id' => 1,
                'waktu' => '10 Menit yang lalu',
                'user' => 'Budi Santoso',
                'jenis' => 'Pengajuan Pinjaman',
                'keterangan' => 'Pembiayaan Elektronik Rp 5.000.000',
                'status' => 'Pending'
            ],
            (object) [
                'id' => 2,
                'waktu' => '1 Jam yang lalu',
                'user' => 'Siti Aminah',
                'jenis' => 'Tarik Saldo',
                'keterangan' => 'Simpanan Sukarela Rp 1.500.000',
                'status' => 'Pending'
            ],
            (object) [
                'id' => 3,
                'waktu' => '2 Jam yang lalu',
                'user' => 'Andi Wijaya',
                'jenis' => 'Anggota Baru',
                'keterangan' => 'Pendaftaran Anggota Koperasi',
                'status' => 'Pending'
            ],
            (object) [
                'id' => 4,
                'waktu' => 'Kemarin, 14:30',
                'user' => 'Ahmad Fauzi',
                'jenis' => 'Pembayaran PPOB',
                'keterangan' => 'Token Listrik Rp 102.000',
                'status' => 'Berhasil'
            ],
            (object) [
                'id' => 5,
                'waktu' => 'Kemarin, 09:15',
                'user' => 'Dewi Lestari',
                'jenis' => 'Setoran Simpanan',
                'keterangan' => 'Simpanan Sukarela Rp 500.000',
                'status' => 'Berhasil'
            ],
        ]);
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 md:gap-0">
        <div>
            <flux:heading size="xl" level="1">Dashboard</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Ringkasan data dan aktivitas Koperasi hari ini.</flux:text>
        </div>
        <div>
            <flux:button variant="primary" icon="document-arrow-down">Unduh Laporan</flux:button>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Notifikasi/Peringatan Pending Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <flux:card class="bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800 flex items-center gap-4">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-full text-orange-600 dark:text-orange-400">
                <flux:icon name="document-text" class="w-6 h-6" />
            </div>
            <div>
                <flux:heading size="lg" class="text-orange-800 dark:text-orange-200">{{ $this->stats['pengajuan_pinjaman_pending'] }} Pinjaman</flux:heading>
                <flux:text class="text-sm text-orange-600 dark:text-orange-400">Menunggu Persetujuan</flux:text>
            </div>
        </flux:card>

        <flux:card class="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-full text-blue-600 dark:text-blue-400">
                <flux:icon name="banknotes" class="w-6 h-6" />
            </div>
            <div>
                <flux:heading size="lg" class="text-blue-800 dark:text-blue-200">{{ $this->stats['pengajuan_tarik_saldo_pending'] }} Tarik Saldo</flux:heading>
                <flux:text class="text-sm text-blue-600 dark:text-blue-400">Menunggu Proses</flux:text>
            </div>
        </flux:card>

        <flux:card class="bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800 flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-full text-purple-600 dark:text-purple-400">
                <flux:icon name="user-plus" class="w-6 h-6" />
            </div>
            <div>
                <flux:heading size="lg" class="text-purple-800 dark:text-purple-200">{{ $this->stats['anggota_baru_pending'] }} Anggota Baru</flux:heading>
                <flux:text class="text-sm text-purple-600 dark:text-purple-400">Menunggu Verifikasi</flux:text>
            </div>
        </flux:card>
    </div>

    <!-- Main Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">Total Anggota</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ number_format($this->stats['total_anggota'], 0, ',', '.') }}</flux:heading>
                </div>
                <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-zinc-500">
                    <flux:icon name="users" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-1 text-sm text-green-600">
                <flux:icon name="arrow-trending-up" class="w-4 h-4" />
                <span>+12 bulan ini</span>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">Total Simpanan</flux:text>
                    <flux:heading size="xl" class="mt-2">Rp {{ number_format($this->stats['total_simpanan'] / 1000000000, 2, ',', '.') }} M</flux:heading>
                </div>
                <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-zinc-500">
                    <flux:icon name="wallet" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-1 text-sm text-green-600">
                <flux:icon name="arrow-trending-up" class="w-4 h-4" />
                <span>+2.4% bulan ini</span>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">Total Pinjaman</flux:text>
                    <flux:heading size="xl" class="mt-2">Rp {{ number_format($this->stats['total_pinjaman'] / 1000000000, 2, ',', '.') }} M</flux:heading>
                </div>
                <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-zinc-500">
                    <flux:icon name="credit-card" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-1 text-sm text-green-600">
                <flux:icon name="arrow-trending-up" class="w-4 h-4" />
                <span>+1.5% bulan ini</span>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex justify-between items-start">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">Laba / SHU (Bulan Ini)</flux:text>
                    <flux:heading size="xl" class="mt-2">Rp {{ number_format($this->stats['pendapatan_bulan_ini'] / 1000000, 0, ',', '.') }} Jt</flux:heading>
                </div>
                <div class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-zinc-500">
                    <flux:icon name="chart-bar" class="w-5 h-5" />
                </div>
            </div>
            <div class="mt-4 flex items-center gap-1 text-sm text-green-600">
                <flux:icon name="arrow-trending-up" class="w-4 h-4" />
                <span>+8.0% bulan ini</span>
            </div>
        </flux:card>
    </div>

    <!-- Recent Activities Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <div class="lg:col-span-2">
            <flux:card>
                <div class="flex justify-between items-center mb-4">
                    <flux:heading size="lg">Aktivitas & Transaksi Terbaru</flux:heading>
                    <flux:button variant="ghost" size="sm">Lihat Semua</flux:button>
                </div>
                <div class="overflow-x-auto">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Anggota</flux:table.column>
                            <flux:table.column>Jenis Aktivitas</flux:table.column>
                            <flux:table.column>Waktu</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Aksi</flux:table.column>
                        </flux:table.columns>
                        
                        <flux:table.rows>
                            @foreach($this->aktivitasTerbaru as $aktivitas)
                            <flux:table.row :key="$aktivitas->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                            {{ substr($aktivitas->user, 0, 1) }}
                                        </div>
                                        <span class="font-medium">{{ $aktivitas->user }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $aktivitas->jenis }}</span>
                                        <span class="text-xs text-zinc-500">{{ $aktivitas->keterangan }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $aktivitas->waktu }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($aktivitas->status === 'Pending')
                                        <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                    @elseif($aktivitas->status === 'Berhasil')
                                        <flux:badge color="green" size="sm" inset="top bottom">Berhasil</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button variant="ghost" size="sm" icon="eye"></flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-1 flex flex-col gap-6">
            <!-- Quick Actions -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Aksi Cepat</flux:heading>
                <div class="flex flex-col gap-2">
                    <flux:button variant="subtle" class="w-full justify-start" icon="plus-circle">Tambah Anggota Baru</flux:button>
                    <flux:button variant="subtle" class="w-full justify-start" icon="document-plus">Buat Pinjaman Baru</flux:button>
                    <flux:button variant="subtle" class="w-full justify-start" icon="banknotes">Setor Simpanan</flux:button>
                    <flux:button variant="subtle" class="w-full justify-start" icon="megaphone">Buat Pengumuman</flux:button>
                </div>
            </flux:card>

            <!-- Jadwal / Agenda -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Agenda Mendatang</flux:heading>
                <div class="space-y-4">
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center justify-center w-12 h-12 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl shrink-0">
                            <span class="text-xs font-semibold">MEI</span>
                            <span class="text-lg font-bold leading-none">20</span>
                        </div>
                        <div>
                            <flux:heading size="sm">Rapat Pengurus Koperasi</flux:heading>
                            <flux:text class="text-xs text-zinc-500 mt-0.5">Ruang Meeting A - 09:00 WIB</flux:text>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center justify-center w-12 h-12 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl shrink-0">
                            <span class="text-xs font-semibold">MEI</span>
                            <span class="text-lg font-bold leading-none">25</span>
                        </div>
                        <div>
                            <flux:heading size="sm">Tutup Buku Bulanan</flux:heading>
                            <flux:text class="text-xs text-zinc-500 mt-0.5">Sistem Koperasi - 23:59 WIB</flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
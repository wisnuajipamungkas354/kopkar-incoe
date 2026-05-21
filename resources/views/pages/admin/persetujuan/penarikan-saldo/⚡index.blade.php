<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Penarikan Saldo'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPengajuan = null;

    #[Computed]
    public function pengajuan()
    {
        // Data dummy, karena belum ada tabel database riwayat penarikan
        $data = collect([
            (object) [
                'id' => 1,
                'user' => (object) [
                    'username' => '001',
                    'nama_anggota' => 'Budi Santoso',
                    'seksi' => 'Produksi'
                ],
                'created_at' => '2026-05-18 09:30:00',
                'rincian' => [
                    'Simpanan Sukarela' => 500000,
                    'SHU' => 1000000
                ],
                'total_nominal' => 1500000,
                'bank_tujuan' => 'BCA',
                'nomor_rekening' => '1234567890',
                'keterangan' => 'Keperluan mendesak untuk biaya pendidikan.',
            ],
            (object) [
                'id' => 2,
                'user' => (object) [
                    'username' => '002',
                    'nama_anggota' => 'Ani Wijaya',
                    'seksi' => 'HRD'
                ],
                'created_at' => '2026-05-17 14:15:00',
                'rincian' => [
                    'Simpanan Lain-lain' => 250000
                ],
                'total_nominal' => 250000,
                'bank_tujuan' => 'Mandiri',
                'nomor_rekening' => '0987654321',
                'keterangan' => 'Renovasi rumah.',
            ]
        ]);

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->user->nama_anggota ?? '', $this->search) !== false || 
                       stripos($item->user->username ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPengajuan($id)
    {
        $this->selectedPengajuan = $this->pengajuan()->firstWhere('id', $id);
    }

    public function approve($id)
    {
        // Dummy logic persetujuan
        Flux::toast(
            heading: 'Berhasil disetujui',
            text: 'Pengajuan penarikan saldo anggota berhasil disetujui.',
            variant: 'success',
        );
    }

    public function tolak($id)
    {
        // Dummy logic penolakan
        Flux::toast(
            heading: 'Berhasil ditolak',
            text: 'Pengajuan penarikan saldo anggota telah ditolak.',
            variant: 'success',
        );
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Penarikan Saldo</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan penarikan saldo simpanan anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan Penarikan</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Anggota</flux:table.column>
                    <flux:table.column>Total Nominal</flux:table.column>
                    <flux:table.column>Bank Tujuan</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->user->username ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->user->nama_anggota ?? 'A', 0, 1) }}
                                    </div>
                                    <span class="font-medium">{{ $row->user->nama_anggota ?? 'Unknown' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="font-bold text-blue-600">Rp {{ number_format($row->total_nominal, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>{{ $row->bank_tujuan }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPengajuan({{ $row->id }})" x-on:click="$flux.modal('detail-pengajuan').show()">Detail</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-gray-500 py-6">Tidak ada pengajuan penarikan saldo yang tertunda.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail Pengajuan -->
    <flux:modal name="detail-pengajuan" class="md:w-xl">
        @if($selectedPengajuan)
            <div>
                <flux:heading size="lg">Detail Penarikan</flux:heading>
                <flux:text size="sm" class="mt-1">Periksa kembali rincian penarikan saldo anggota sebelum memberikan persetujuan.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Info Anggota -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedPengajuan->user->nama_anggota ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPengajuan->user->nama_anggota ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedPengajuan->user->username ?? '-' }} • {{ $selectedPengajuan->user->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <!-- Rincian Nominal -->
                <div>
                    <flux:text class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-3">Rincian Saldo Ditarik</flux:text>
                    <div class="space-y-2">
                        @foreach($selectedPengajuan->rincian as $jenis => $nominal)
                            <div class="flex justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $jenis }}</span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($nominal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="flex justify-between items-center mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40 rounded-lg">
                        <span class="font-bold text-blue-800 dark:text-blue-200">Total Penarikan</span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($selectedPengajuan->total_nominal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <!-- Info Bank & Keterangan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Bank Tujuan</flux:text>
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->bank_tujuan }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nomor Rekening</flux:text>
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->nomor_rekening }}</flux:text>
                    </div>
                </div>
                
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500 mb-1">Keterangan Penarikan</flux:text>
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                        <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">{{ $selectedPengajuan->keterangan }}</flux:text>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedPengajuan->id }})" x-on:click="$flux.modal('detail-pengajuan').close()">Tolak</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="approve({{ $selectedPengajuan->id }})" x-on:click="$flux.modal('detail-pengajuan').close()">Transfer & Setujui</flux:button>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pengajuan...
            </div>
        @endif
    </flux:modal>
</div>
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\SimpananSukarelaPengaturan;
use Flux\Flux;
use App\Mail\NotifikasiPerubahanSimpananSukarela;
use Illuminate\Support\Facades\Mail;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Perubahan Simpanan Sukarela'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPengajuan = null;

    #[Computed]
    public function pengajuan()
    {
        $data = SimpananSukarelaPengaturan::with('user')
                ->where('status_persetujuan', 'pending_approval')
                ->orderBy('updated_at', 'DESC')
                ->get();

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
        $pengajuan = SimpananSukarelaPengaturan::with('user')->find($id);
        
        $namaAnggota = $pengajuan->user->nama_anggota;
        $nominalSebelum = $pengajuan->nominal_rutin_saat_ini;
        $nominalSesudah = $pengajuan->nominal_baru_diajukan;
        $statusApprove = 'approved';

        if($pengajuan) {
            $pengajuan->update([
                'status_persetujuan' => $statusApprove,
                'nominal_rutin_saat_ini' => $nominalSesudah,
            ]);


            Mail::to($pengajuan->user->email)->send(new NotifikasiPerubahanSimpananSukarela($namaAnggota, $statusApprove, $nominalSebelum, $nominalSesudah));

            Flux::toast(
                heading: 'Berhasil di Approve',
                text: 'Perubahan nominal simpanan sukarela berhasil disetujui',
                variant: 'success',
            );

            Flux::modal('detail-pengajuan')->close();
        }
    }

    public function tolak($id)
    {
        $pengajuan = SimpananSukarelaPengaturan::with('user')->find($id);
        $namaAnggota = $pengajuan->user->nama_anggota;
        $statusApprove = 'rejected';

        if($pengajuan) {
            $pengajuan->update([
                'status_persetujuan' => $statusApprove,
            ]);

            Mail::to($pengajuan->user->email)->send(new NotifikasiPerubahanSimpananSukarela($namaAnggota, $statusApprove));

            Flux::toast(
                heading: 'Berhasil ditolak',
                text: 'Perubahan nominal simpanan sukarela telah ditolak',
                variant: 'success',
            );
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Simpanan Sukarela</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan perubahan nominal setoran rutin simpanan sukarela.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Pengajuan</flux:table.column>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Anggota</flux:table.column>
                    <flux:table.column>Nominal Lama</flux:table.column>
                    <flux:table.column>Nominal Baru</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->updated_at)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->user->username ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->user->nama_anggota ?? 'A', 0, 1) }}
                                    </div>
                                    <span class="font-medium">{{ $row->user->nama_anggota ?? 'Unknown' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>Rp {{ number_format($row->nominal_rutin_saat_ini, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell class="font-bold text-blue-600">Rp {{ number_format($row->nominal_baru_diajukan, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPengajuan({{ $row->id }})" x-on:click="$flux.modal('detail-pengajuan').show()">Detail</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-gray-500 py-6">Tidak ada pengajuan perubahan simpanan sukarela.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail -->
    <flux:modal name="detail-pengajuan" class="md:w-xl">
        @if($selectedPengajuan)
            <div>
                <flux:heading size="lg">Detail Pengajuan</flux:heading>
                <flux:text size="sm" class="mt-1">Periksa kembali pengajuan perubahan simpanan sukarela anggota.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedPengajuan->user->nama_anggota ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPengajuan->user->nama_anggota ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedPengajuan->user->username ?? '-' }} • {{ $selectedPengajuan->user->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nominal Lama</flux:text>
                        <flux:text class="text-lg font-medium text-zinc-800 dark:text-zinc-200">Rp {{ number_format($selectedPengajuan->nominal_rutin_saat_ini, 0, ',', '.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nominal Baru (Diajukan)</flux:text>
                        <flux:text class="text-lg font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($selectedPengajuan->nominal_baru_diajukan, 0, ',', '.') }}</flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <div class="space-y-3 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-900/40">
                    <div class="flex gap-3">
                        <flux:icon name="information-circle" class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" />
                        <flux:text class="text-sm text-blue-800 dark:text-blue-200">Jika disetujui, nominal setoran rutin bulanan anggota akan diperbarui menjadi nominal baru yang diajukan mulai bulan berikutnya.</flux:text>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedPengajuan->id }})" x-on:click="$flux.modal('detail-pengajuan').close()">Tolak</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="approve({{ $selectedPengajuan->id }})">Approve Perubahan</flux:button>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pengajuan...
            </div>
        @endif
    </flux:modal>
</div>
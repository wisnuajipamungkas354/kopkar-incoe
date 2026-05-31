<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pinjaman;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function pinjamans()
    {
        $query = Pinjaman::with('employee');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_pengajuan', 'like', '%' . $this->search . '%')
                  ->orWhere('jenis_pinjaman', 'like', '%' . $this->search . '%')
                  ->orWhere('status', 'like', '%' . $this->search . '%')
                  ->orWhereHas('employee', function ($eq) {
                      $eq->where('nama_lengkap', 'like', '%' . $this->search . '%')
                         ->orWhere('npk', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function delete($id)
    {
        $pinjaman = Pinjaman::find($id);
        if ($pinjaman) {
            $pinjaman->delete();
            $this->js("Flux.toast({ text: 'Data pinjaman berhasil dihapus.', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Kelola Pinjaman</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Kelola seluruh data pengajuan pinjaman karyawan/anggota.</flux:text>
        </div>
        <flux:button href="/admin/pinjaman/create" wire:navigate variant="primary" icon="plus">Tambah Pinjaman</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-4">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="lg" level="2">Daftar Pinjaman</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-80" placeholder="Cari nomor, NPK, nama, jenis, status..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-1" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->pinjamans">
                <flux:table.columns>
                    <flux:table.column>No. Pengajuan</flux:table.column>
                    <flux:table.column>Karyawan (NPK)</flux:table.column>
                    <flux:table.column>Jenis Pinjaman</flux:table.column>
                    <flux:table.column>Nominal Diajukan</flux:table.column>
                    <flux:table.column>Nominal Disetujui</flux:table.column>
                    <flux:table.column>Tenor</flux:table.column>
                    <flux:table.column>Angsuran</flux:table.column>
                    <flux:table.column>Bank & Rekening</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Tanggal Buat</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->pinjamans as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-white">{{ $row->nomor_pengajuan }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm">
                                    <span class="font-medium text-zinc-900 dark:text-white block">{{ $row->employee->nama_lengkap ?? '-' }}</span>
                                    <span class="text-xs text-zinc-400 block font-mono">NPK: {{ $row->employee->npk ?? '-' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->jenis_pinjaman === 'qard')
                                    <flux:badge color="emerald" size="sm" icon="hand-raised">Qard Hasan</flux:badge>
                                @elseif($row->jenis_pinjaman === 'bon')
                                    <flux:badge color="amber" size="sm" icon="credit-card">Bon Sementara</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ $row->jenis_pinjaman }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format($row->nominal_pengajuan, 0, ',', '.') }}
                            </flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">
                                @if($row->nominal_disetujui !== null)
                                    Rp {{ number_format($row->nominal_disetujui, 0, ',', '.') }}
                                @else
                                    <span class="text-zinc-400 text-xs italic">-</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $row->tenor_bulan }} Bulan</flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">
                                @if($row->nominal_angsuran !== null)
                                    Rp {{ number_format($row->nominal_angsuran, 0, ',', '.') }}
                                @else
                                    <span class="text-zinc-400 text-xs italic">-</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300 block">{{ $row->nama_bank }}</span>
                                    <span class="text-zinc-500 font-mono block">{{ $row->no_rekening }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'draft')
                                    <flux:badge color="zinc" size="sm" icon="pencil">Draft</flux:badge>
                                @elseif($row->status === 'diajukan')
                                    <flux:badge color="orange" size="sm" icon="clock">Diajukan</flux:badge>
                                @elseif($row->status === 'diproses')
                                    <flux:badge color="sky" size="sm" icon="clock">Diproses</flux:badge>
                                @elseif($row->status === 'ditolak')
                                    <flux:badge color="red" size="sm" icon="x-mark">Ditolak</flux:badge>
                                @elseif($row->status === 'dibatalkan')
                                    <flux:badge color="zinc" size="sm" icon="x-circle">Dibatalkan</flux:badge>
                                @elseif($row->status === 'berjalan')
                                    <flux:badge color="blue" size="sm" icon="arrow-path">Berjalan</flux:badge>
                                @elseif($row->status === 'lunas')
                                    <flux:badge color="green" size="sm" icon="check">Lunas</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ $row->status }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap text-xs text-zinc-500">
                                {{ $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" href="/admin/pinjaman/{{ $row->id }}/edit" wire:navigate>Edit / Detail</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus data pinjaman ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="11" class="text-center py-8 text-zinc-500">
                                Tidak ada data pinjaman yang ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>

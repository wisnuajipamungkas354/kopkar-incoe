<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pembiayaan;

new #[Layout('layouts::admin', ['title' => 'Daftar Pembiayaan'])] class extends Component
{
    use WithPagination;

    public $search       = '';
    public $perPage      = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function pembiayaans()
    {
        $query = Pembiayaan::with('employee');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_pengajuan', 'like', '%' . $this->search . '%')
                  ->orWhere('kategori_pembiayaan', 'like', '%' . $this->search . '%')
                  ->orWhere('status', 'like', '%' . $this->search . '%')
                  ->orWhereHas('employee', function ($eq) {
                      $eq->where('nama_lengkap', 'like', '%' . $this->search . '%')
                         ->orWhere('npk', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function deletePembiayaan($id)
    {
        $pembiayaan = Pembiayaan::find($id);
        if ($pembiayaan) {
            $pembiayaan->delete();
            $this->js("Flux.toast({ text: 'Data pembiayaan berhasil dihapus.', variant: 'success' })");
        }
    }
};
?>

<div>
    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Daftar Pembiayaan</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">Kelola seluruh data pembiayaan syariah anggota koperasi.</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button href="/admin/pembiayaan/create" wire:navigate variant="primary" icon="plus">Tambah Pembiayaan</flux:button>
        </div>
    </div>

    <flux:separator variant="subtle" />

    <div class="mt-5">
        <flux:card>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
                <flux:heading size="lg" level="2">Daftar Pembiayaan</flux:heading>
                <flux:input wire:model.live.debounce.300ms="search" size="sm" class="max-w-64" placeholder="Cari NPK, Nama, Nomor..." icon="magnifying-glass" />
            </div>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->pembiayaans">
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Nomor</flux:table.column>
                        <flux:table.column>Anggota</flux:table.column>
                        <flux:table.column>Kategori</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                        <flux:table.column>Tenor</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->pembiayaans as $row)
                            <flux:table.row :key="$row->id">
                                <flux:table.cell class="text-xs text-zinc-500 whitespace-nowrap">{{ $row->created_at->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs font-semibold">{{ $row->nomor_pengajuan }}</flux:table.cell>
                                <flux:table.cell>
                                    <span class="block font-medium text-sm text-zinc-900 dark:text-zinc-100">{{ $row->employee->nama_lengkap ?? '-' }}</span>
                                    <span class="block text-xs text-zinc-500">NPK: {{ $row->employee->npk ?? '-' }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $km = ['barang'=>['Pembelian Barang','blue'],'kendaraan'=>['Kendaraan','indigo'],'renovasi'=>['Renovasi','amber'],'pendidikan'=>['Pendidikan','purple'],'kesehatan'=>['Kesehatan','teal'],'lainnya'=>['Lainnya','zinc']];
                                        [$klabel, $kcolor] = $km[$row->kategori_pembiayaan] ?? [ucfirst($row->kategori_pembiayaan), 'zinc'];
                                    @endphp
                                    <flux:badge color="{{ $kcolor }}" size="sm">{{ $klabel }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                    Rp {{ number_format($row->nominal_disetujui ?? $row->nominal_pengajuan, 0, ',', '.') }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $row->tenor_bulan }} Bulan</flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $sc = ['diajukan'=>'orange','diproses'=>'sky','ditolak'=>'red','dibatalkan'=>'zinc','berjalan'=>'emerald','lunas'=>'green'];
                                        $c = $sc[$row->status] ?? 'zinc';
                                    @endphp
                                    <flux:badge color="{{ $c }}" size="sm">{{ ucfirst($row->status) }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                        <flux:menu>
                                            <flux:menu.item icon="pencil-square" href="/admin/pembiayaan/{{ $row->id }}/edit" wire:navigate>Edit Data</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" variant="danger" wire:click="deletePembiayaan({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus data ini?">Hapus Data</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center py-6 text-zinc-500">Data pembiayaan tidak ditemukan.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    </div>
</div>

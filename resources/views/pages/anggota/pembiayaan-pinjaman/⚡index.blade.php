<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts::anggota', ['title' => 'Pembiayaan dan Pinjaman'])] class extends Component
{
    use WithPagination;

    public $search = '';

    #[Computed]
    public function pembiayaanAktif()
    {
        return collect([
            (object) ['id' => 1, 'tanggal_pengajuan' => '2023-08-15', 'jenis' => 'Pembiayaan Barang', 'barang' => 'Laptop ASUS ROG', 'nominal' => 15000000, 'tenor' => 12, 'sisa_tenor' => 4, 'cicilan' => 1250000, 'status' => 'Aktif'],
            (object) ['id' => 2, 'tanggal_pengajuan' => '2024-01-10', 'jenis' => 'Pinjaman Uang', 'barang' => '-', 'nominal' => 5000000, 'tenor' => 10, 'sisa_tenor' => 6, 'cicilan' => 500000, 'status' => 'Aktif'],
        ]);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Pembiayaan & Pinjaman</flux:heading>
            <flux:text class="mt-2 text-base">Manajemen dan pengajuan pembiayaan barang serta pinjaman uang.</flux:text>
        </div>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <flux:button wire:navigate href="/anggota/pembiayaan-pinjaman/pengajuan" size="sm" variant="primary" icon="plus">Ajukan Pembiayaan & Pinjaman</flux:button>
        </div>
    </div>
    <flux:separator variant="subtle" />

    <flux:card class="flex flex-col mt-6">
        <div class="flex justify-between items-center mb-4">
            <flux:heading size="lg">Pembiayaan & Pinjaman Aktif</flux:heading>
            <div class="flex gap-2">
                <flux:button size="sm" variant="outline" icon="funnel">Filter</flux:button>
                <flux:input wire:model.live="search" size="sm" class="max-w-42" placeholder="Cari..." icon="magnifying-glass" />
            </div>
        </div>
        
        <flux:separator variant="subtle" class="mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Pengajuan</flux:table.column>
                    <flux:table.column>Jenis</flux:table.column>
                    <flux:table.column>Barang/Keterangan</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Cicilan/Bulan</flux:table.column>
                    <flux:table.column>Tenor</flux:table.column>
                    <flux:table.column>Sisa Tenor</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pembiayaanAktif as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->jenis === 'Pembiayaan Barang')
                                    <flux:badge color="blue" size="sm" inset="top bottom">{{ $row->jenis }}</flux:badge>
                                @else
                                    <flux:badge color="green" size="sm" inset="top bottom">{{ $row->jenis }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->barang }}</flux:table.cell>
                            <flux:table.cell>Rp {{ number_format($row->nominal, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>Rp {{ number_format($row->cicilan, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>{{ $row->tenor }} Bulan</flux:table.cell>
                            <flux:table.cell>{{ $row->sisa_tenor }} Bulan</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="emerald" size="sm" inset="top bottom">{{ $row->status }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center text-gray-500 py-4">Tidak ada data pembiayaan atau pinjaman aktif.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>


</div>
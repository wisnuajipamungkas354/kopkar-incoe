<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts::anggota')] class extends Component
{
    use WithPagination;

    public $search = '';

    #[Computed]
    public function simpanan()
    {
        // Dummy data for display purposes
        return collect([
            (object) ['id' => 1, 'tanggal' => '2023-10-01', 'tipe' => 'Bulanan', 'transaksi' => 'Setor', 'keterangan' => 'Potongan Gaji', 'jumlah' => 100000],
            (object) ['id' => 2, 'tanggal' => '2023-10-15', 'tipe' => 'Sekali Transfer', 'transaksi' => 'Setor', 'keterangan' => 'Transfer Bank BCA', 'jumlah' => 500000],
            (object) ['id' => 3, 'tanggal' => '2023-11-01', 'tipe' => 'Bulanan', 'transaksi' => 'Setor', 'keterangan' => 'Potongan Gaji', 'jumlah' => 100000],
            (object) ['id' => 4, 'tanggal' => '2023-12-05', 'tipe' => 'Sekali Transfer', 'transaksi' => 'Tarik', 'keterangan' => 'Tarik Tunai Keperluan Mendadak', 'jumlah' => 150000],
        ]);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Simpanan Sukarela</flux:heading>
            <flux:text class="mt-2 text-base">Manajemen data simpanan sukarela anggota.</flux:text>
        </div>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <flux:button size="sm" variant="primary" icon="plus">Tambah Data</flux:button>
            <flux:button size="sm" variant="outline" icon="arrow-down-tray">Export</flux:button>
        </div>
    </div>
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-4">
        <div class="flex justify-between items-center">
            <flux:button size="sm" variant="outline" icon="funnel">Filter</flux:button>
            <flux:input wire:model.live="search" size="sm" class="max-w-42" placeholder="Cari keterangan..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Tipe Simpanan</flux:table.column>
                    <flux:table.column>Transaksi</flux:table.column>
                    <flux:table.column>Keterangan</flux:table.column>
                    <flux:table.column>Jumlah</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->simpanan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->tipe === 'Bulanan')
                                    <flux:badge color="blue" size="sm" inset="top bottom">Bulanan</flux:badge>
                                @else
                                    <flux:badge color="purple" size="sm" inset="top bottom">Sekali Transfer</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->transaksi === 'Setor')
                                    <flux:badge color="green" size="sm" inset="top bottom">Setor</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm" inset="top bottom">Tarik</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->keterangan }}</flux:table.cell>
                            <flux:table.cell>Rp {{ number_format($row->jumlah, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button variant="ghost" size="sm" icon="pencil-square" inset="top bottom" tooltip="Edit" />
                                    <flux:button variant="ghost" color="danger" size="sm" icon="trash" inset="top bottom" tooltip="Hapus" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-gray-500 py-4">Tidak ada data simpanan.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>
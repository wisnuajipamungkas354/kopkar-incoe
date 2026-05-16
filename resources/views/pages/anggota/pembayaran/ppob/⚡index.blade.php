<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts::anggota', ['title' => 'PPOB'])] class extends Component
{
    use WithPagination;

    public $search = '';

    #[Computed]
    public function riwayatPpob()
    {
        $data = collect([
            (object) [
                'id' => 1,
                'tanggal' => '2023-12-05',
                'layanan' => 'Token Listrik',
                'nomor_pelanggan' => '123456789012',
                'nominal' => 102000,
                'status' => 'Berhasil',
            ],
            (object) [
                'id' => 2,
                'tanggal' => '2023-12-20',
                'layanan' => 'Pulsa Telkomsel',
                'nomor_pelanggan' => '081234567890',
                'nominal' => 51500,
                'status' => 'Berhasil',
            ],
            (object) [
                'id' => 3,
                'tanggal' => '2024-01-10',
                'layanan' => 'BPJS Kesehatan',
                'nomor_pelanggan' => '0001234567890',
                'nominal' => 152500,
                'status' => 'Ditolak',
            ],
            (object) [
                'id' => 4,
                'tanggal' => '2024-02-15',
                'layanan' => 'PDAM',
                'nomor_pelanggan' => '1122334455',
                'nominal' => 75000,
                'status' => 'Pending',
            ],
        ]);

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->layanan, $this->search) !== false || 
                       stripos($item->nomor_pelanggan, $this->search) !== false;
            });
        }

        return $data;
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">PPOB</flux:heading>
            <flux:text class="mt-2 text-base">Manajemen dan pengajuan pembayaran PPOB (Listrik, Pulsa, PDAM, dll).</flux:text>
        </div>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <flux:modal.trigger name="ajukan-ppob">
                <flux:button size="sm" variant="primary" icon="plus">Bayar PPOB</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Riwayat Transaksi PPOB</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari riwayat..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Layanan</flux:table.column>
                    <flux:table.column>No. Pelanggan</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->riwayatPpob as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="blue" size="sm" inset="top bottom">{{ $row->layanan }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->nomor_pelanggan }}</flux:table.cell>
                            <flux:table.cell class="font-medium">Rp {{ number_format($row->nominal, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'Berhasil')
                                    <flux:badge color="green" size="sm" inset="top bottom">Berhasil</flux:badge>
                                @elseif($row->status === 'Pending')
                                    <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm" inset="top bottom">Ditolak</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-gray-500 py-6">Tidak ada riwayat PPOB.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Pengajuan PPOB -->
    <flux:modal name="ajukan-ppob" class="md:w-[32rem] space-y-6">
        <div>
            <flux:heading size="lg">Bayar PPOB</flux:heading>
            <flux:text size="sm" class="mt-1">Pilih layanan PPOB yang ingin Anda bayar.</flux:text>
        </div>

        <form class="flex flex-col gap-4 mt-4">
            <flux:select label="Jenis Layanan" placeholder="Pilih Layanan">
                <flux:select.option>Token Listrik</flux:select.option>
                <flux:select.option>Pulsa Pascabayar/Prabayar</flux:select.option>
                <flux:select.option>BPJS Kesehatan</flux:select.option>
                <flux:select.option>PDAM</flux:select.option>
            </flux:select>

            <flux:input label="Nomor Pelanggan / HP" placeholder="Masukkan nomor pelanggan atau HP" required />
            
            <flux:input type="number" label="Nominal (Rp)" placeholder="Masukkan nominal" required />

            <div class="flex justify-end gap-2 mt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="primary" x-on:click="$flux.modal('ajukan-ppob').close()">Kirim Pengajuan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
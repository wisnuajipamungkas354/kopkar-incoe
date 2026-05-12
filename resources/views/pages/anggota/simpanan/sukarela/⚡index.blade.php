<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts::anggota')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $metodePembayaran = 'qris';
    public $nominalBaru = '';

    public function submitUbahSetoran()
    {
        // Dummy logic submission
        $this->reset('nominalBaru');
    }

    #[Computed]
    public function simpanan()
    {
        // Dummy data for display purposes
        return collect([
            (object) ['id' => 1, 'tanggal' => '2023-10-01', 'transaksi' => 'Setoran Rutin', 'metode' => 'Payroll', 'status' => 'Berhasil', 'nominal' => 100000],
            (object) ['id' => 2, 'tanggal' => '2023-10-15', 'transaksi' => 'Setoran Tambahan', 'metode' => 'Cash', 'status' => 'Berhasil', 'nominal' => 500000],
            (object) ['id' => 3, 'tanggal' => '2023-11-01', 'transaksi' => 'Setoran Rutin', 'metode' => 'Payroll', 'status' => 'Berhasil', 'nominal' => 100000],
            (object) ['id' => 4, 'tanggal' => '2023-12-05', 'transaksi' => 'Penarikan', 'metode' => 'Transfer', 'status' => 'Berhasil', 'nominal' => 150000],
            (object) ['id' => 5, 'tanggal' => '2023-12-10', 'transaksi' => 'Setoran Tambahan', 'metode' => 'Transfer', 'status' => 'Pending', 'nominal' => 200000],
        ]);
    }

    #[Computed]
    public function saldoSukarela()
    {
        return 1250000;
    }

    #[Computed]
    public function setoranRutin()
    {
        return 100000;
    }

    #[Computed]
    public function pengajuanPending()
    {
        return 1;
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
            <flux:modal.trigger name="ubah-setoran">
                <flux:button size="sm" variant="outline" icon="pencil-square">Ubah Setoran</flux:button>
            </flux:modal.trigger>
            <flux:button size="sm" variant="primary" icon="banknotes">Tarik Tunai</flux:button>
        </div>
    </div>
    <flux:separator variant="subtle" />
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 mb-6">
        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-xl">
                <flux:icon name="banknotes" class="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo Simpanan</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->saldoSukarela, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-xl">
                <flux:icon name="arrow-path" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Setoran Rutin</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->setoranRutin, 0, ',', '.') }}<span class="text-base font-normal text-zinc-400">/bulan</span></flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/40 rounded-xl">
                <flux:icon name="clock" class="w-8 h-8 text-orange-600 dark:text-orange-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pengajuan Pending</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $this->pengajuanPending }} <span class="text-base font-normal text-zinc-500">Berkas</span></flux:heading>
            </div>
        </flux:card>
    </div>

    <flux:card class="flex flex-col">
        <div class="flex justify-between items-center">
            <flux:button size="sm" variant="outline" icon="funnel">Filter</flux:button>
            <flux:input wire:model.live="search" size="sm" class="max-w-42" placeholder="Cari keterangan..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Transaksi</flux:table.column>
                    <flux:table.column>Metode</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->simpanan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->transaksi === 'Setoran Rutin')
                                    <flux:badge color="blue" size="sm" inset="top bottom">{{ $row->transaksi }}</flux:badge>
                                @elseif($row->transaksi === 'Setoran Tambahan')
                                    <flux:badge color="purple" size="sm" inset="top bottom">{{ $row->transaksi }}</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm" inset="top bottom">{{ $row->transaksi }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->metode }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'Berhasil')
                                    <flux:badge color="green" size="sm" inset="top bottom">Berhasil</flux:badge>
                                @else
                                    <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>Rp {{ number_format($row->nominal, 0, ',', '.') }}</flux:table.cell>
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

    <div class="mt-8">
        <div class="mb-4">
            <flux:heading size="lg" level="2">Pengajuan Simpanan Tambahan</flux:heading>
            <flux:text class="mt-1 text-base">Ajukan setoran simpanan sukarela tambahan (sekali transfer/insidental) di luar setoran rutin.</flux:text>
        </div>
        
        <flux:card>
            <form class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-4">
                    <flux:input label="Nominal Setoran (Rp)" placeholder="Contoh: 500000" type="number" />
                    <flux:select wire:model.live="metodePembayaran" label="Metode Pembayaran">
                        <option value="qris">QRIS (Otomatis & Tanpa Upload Bukti)</option>
                        <option value="transfer_mandiri">Transfer Bank Mandiri</option>
                        <option value="transfer_bca">Transfer Bank BCA</option>
                    </flux:select>
                </div>
                
                <div class="flex flex-col gap-4">
                    @if($metodePembayaran !== 'qris')
                        <flux:input type="file" label="Bukti Transfer" description="Format: JPG, PNG, atau PDF. Maks. 2MB" />
                    @else
                        <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800/50 text-sm text-purple-700 dark:text-purple-300 flex items-start gap-3">
                            <flux:icon name="qr-code" class="w-5 h-5 mt-0.5 shrink-0" />
                            <div>
                                <span class="font-semibold">Pembayaran via QRIS:</span> Transaksi diverifikasi otomatis. Kode QRIS akan ditampilkan setelah Anda menekan tombol di bawah.
                            </div>
                        </div>
                    @endif
                    <flux:textarea label="Keterangan (Opsional)" placeholder="Tambahkan catatan jika ada..." rows="2" />
                </div>
                
                <div class="md:col-span-2 flex justify-end mt-2">
                    @if($metodePembayaran === 'qris')
                        <flux:button variant="primary" icon="qr-code">Bayar via QRIS</flux:button>
                    @else
                        <flux:button variant="primary" icon="paper-airplane">Kirim Pengajuan</flux:button>
                    @endif
                </div>
            </form>
        </flux:card>
    </div>

    <!-- Modal Ubah Setoran -->
    <flux:modal name="ubah-setoran" class="md:w-[32rem] space-y-6">
        <div>
            <flux:heading size="lg">Pengajuan Perubahan Setoran</flux:heading>
            <flux:text size="sm" class="mt-1">Silakan masukkan nominal setoran rutin bulanan yang baru.</flux:text>
        </div>

        <form x-on:submit.prevent="$flux.modal('konfirmasi-ubah-setoran').show(); $flux.modal('ubah-setoran').close()" class="flex flex-col gap-4 mt-4">
            <flux:input 
                label="Nominal Saat Ini" 
                value="Rp {{ number_format($this->setoranRutin, 0, ',', '.') }}" 
                disabled 
            />
            
            <flux:input 
                wire:model.live="nominalBaru" 
                type="number" 
                label="Nominal Baru (Rp)" 
                placeholder="Contoh: 150000"
                autofocus
            />

            <div class="flex justify-end gap-2 mt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane">Kirim Pengajuan</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Konfirmasi Ubah Setoran -->
    <flux:modal name="konfirmasi-ubah-setoran" class="md:w-[28rem]">
        <div class="flex flex-col gap-6">
            <div>
                <div class="flex items-center gap-3 text-orange-500">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/40 rounded-full">
                        <flux:icon name="exclamation-triangle" class="w-6 h-6" />
                    </div>
                    <flux:heading size="lg">Konfirmasi Perubahan</flux:heading>
                </div>
                <flux:text size="sm" class="mt-4">
                    Apakah Anda yakin ingin mengajukan perubahan nominal setoran rutin bulanan Anda menjadi:
                </flux:text>
            </div>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl text-center border border-zinc-200 dark:border-zinc-800">
                <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Nominal Baru</div>
                <div class="text-2xl font-bold mt-1 text-zinc-800 dark:text-zinc-100">
                    Rp {{ $nominalBaru ? number_format((int)$nominalBaru, 0, ',', '.') : 0 }}
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button x-on:click="$flux.modal('ubah-setoran').show()" variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" color="orange" wire:click="submitUbahSetoran" x-on:click="$flux.modal('konfirmasi-ubah-setoran').close(); setTimeout(() => $flux.modal('sukses-ubah-setoran').show(), 300)">Ya, Ajukan Sekarang</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Sukses -->
    <flux:modal name="sukses-ubah-setoran" class="md:w-[28rem]">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500 mb-2">
                <flux:icon name="check-circle" class="w-10 h-10" />
            </div>
            
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pengajuan perubahan nominal setoran rutin bulanan Anda telah berhasil dikirim dan sedang menunggu proses verifikasi oleh pengurus.
            </flux:text>

            <div class="w-full mt-4">
                <flux:modal.close>
                    <flux:button variant="primary" class="w-full">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
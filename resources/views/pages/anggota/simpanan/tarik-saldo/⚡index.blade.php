<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Layout('layouts::anggota', ['title' => 'Tarik Saldo'])] class extends Component
{
    use WithPagination;

    public $search = '';
    
    // Properties for multi-source withdrawal
    public $tarikSukarela = false;
    public $tarikLain = false;
    public $tarikShu = false;

    public $nominalSukarela = '';
    public $nominalLain = '';
    public $nominalShu = '';

    public $bankTujuan = '';
    public $nomorRekening = '';
    public $keteranganTarik = '';

    #[Computed]
    public function simpananSukarela()
    {
        return 2500000;
    }

    #[Computed]
    public function simpananLain()
    {
        return 750000;
    }

    #[Computed]
    public function shu()
    {
        return 1850000;
    }

    #[Computed]
    public function totalNominalTarik()
    {
        $total = 0;
        if ($this->tarikSukarela) {
            $total += (int) $this->nominalSukarela;
        }
        if ($this->tarikLain) {
            $total += (int) $this->nominalLain;
        }
        if ($this->tarikShu) {
            $total += (int) $this->nominalShu;
        }
        return $total;
    }

    #[Computed]
    public function riwayatPenarikan()
    {
        $data = collect([
            (object) [
                'id' => 1,
                'tanggal' => '2023-12-05',
                'jenis' => 'Simpanan Sukarela',
                'nominal' => 500000,
                'metode' => 'BCA - 1234567890',
                'status' => 'Berhasil',
                'keterangan' => 'Keperluan mendesak'
            ],
            (object) [
                'id' => 2,
                'tanggal' => '2023-12-20',
                'jenis' => 'SHU',
                'nominal' => 1000000,
                'metode' => 'Mandiri - 0987654321',
                'status' => 'Berhasil',
                'keterangan' => 'Pencairan SHU Tahunan'
            ],
            (object) [
                'id' => 3,
                'tanggal' => '2024-01-10',
                'jenis' => 'Simpanan Lain-lain',
                'nominal' => 250000,
                'metode' => 'BRI - 1122334455',
                'status' => 'Ditolak',
                'keterangan' => 'Melebihi batas penarikan'
            ],
            (object) [
                'id' => 4,
                'tanggal' => '2024-02-15',
                'jenis' => 'Simpanan Sukarela',
                'nominal' => 750000,
                'metode' => 'BCA - 1234567890',
                'status' => 'Pending',
                'keterangan' => 'Biaya pendidikan anak'
            ],
        ]);

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->jenis, $this->search) !== false || 
                       stripos($item->keterangan, $this->search) !== false ||
                       stripos($item->metode, $this->search) !== false;
            });
        }

        return $data;
    }

    public function submitPengajuan()
    {
        // Dummy logic submission
        $this->reset([
            'tarikSukarela', 'tarikLain', 'tarikShu',
            'nominalSukarela', 'nominalLain', 'nominalShu',
            'bankTujuan', 'nomorRekening', 'keteranganTarik'
        ]);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Tarik Saldo</flux:heading>
            <flux:text class="mt-2 text-base">Manajemen dan pengajuan penarikan saldo simpanan sukarela, simpanan lain-lain, dan SHU.</flux:text>
        </div>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <flux:modal.trigger name="ajukan-penarikan">
                <flux:button size="sm" variant="primary" icon="arrow-down-tray">Ajukan Penarikan</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:separator variant="subtle" />

    <!-- Cards Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 mb-6">
        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/40 rounded-xl">
                <flux:icon name="banknotes" class="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Simpanan Sukarela</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->simpananSukarela, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-xl">
                <flux:icon name="wallet" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Simpanan Lain-lain</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->simpananLain, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/40 rounded-xl">
                <flux:icon name="gift" class="w-8 h-8 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sisa Hasil Usaha (SHU)</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->shu, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>
    </div>

    <!-- Table Section -->
    <flux:card class="flex flex-col">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Riwayat Penarikan Saldo</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari riwayat..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Jenis Saldo</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Rekening Tujuan</flux:table.column>
                    <flux:table.column>Keterangan</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->riwayatPenarikan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->jenis === 'Simpanan Sukarela')
                                    <flux:badge color="green" size="sm" inset="top bottom">{{ $row->jenis }}</flux:badge>
                                @elseif($row->jenis === 'SHU')
                                    <flux:badge color="purple" size="sm" inset="top bottom">{{ $row->jenis }}</flux:badge>
                                @else
                                    <flux:badge color="blue" size="sm" inset="top bottom">{{ $row->jenis }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">Rp {{ number_format($row->nominal, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>{{ $row->metode }}</flux:table.cell>
                            <flux:table.cell class="text-zinc-500 dark:text-zinc-400">{{ $row->keterangan }}</flux:table.cell>
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
                            <flux:table.cell colspan="6" class="text-center text-gray-500 py-6">Tidak ada riwayat penarikan saldo.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Pengajuan Penarikan -->
    <flux:modal name="ajukan-penarikan" class="md:w-lg space-y-6">
        <div>
            <flux:heading size="lg">Form Pengajuan Penarikan</flux:heading>
            <flux:text size="sm" class="mt-1">Pilih satu atau beberapa jenis saldo yang ingin Anda tarik sekaligus.</flux:text>
        </div>

        <form x-on:submit.prevent="$flux.modal('konfirmasi-pengajuan').show(); $flux.modal('ajukan-penarikan').close()" class="flex flex-col gap-4 mt-4">
            <div class="space-y-3">
                <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Pilih Saldo & Nominal:</flux:text>
                
                <!-- Sukarela -->
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2 transition-all mt-2">
                    <flux:checkbox wire:model.live="tarikSukarela" label="Simpanan Sukarela (Saldo: Rp {{ number_format($this->simpananSukarela, 0, ',', '.') }})" />
                    @if($tarikSukarela)
                        <div class="pt-1 pl-6">
                            <flux:input wire:model.live.debounce.500ms="nominalSukarela" type="number" size="sm" placeholder="Nominal Tarik Sukarela (Rp)" max="{{ $this->simpananSukarela }}" required />
                        </div>
                    @endif
                </div>

                <!-- Lain-lain -->
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2 transition-all">
                    <flux:checkbox wire:model.live="tarikLain" label="Simpanan Lain-lain (Saldo: Rp {{ number_format($this->simpananLain, 0, ',', '.') }})" />
                    @if($tarikLain)
                        <div class="pt-1 pl-6">
                            <flux:input wire:model.live.debounce.500ms="nominalLain" type="number" size="sm" placeholder="Nominal Tarik Lain-lain (Rp)" max="{{ $this->simpananLain }}" required />
                        </div>
                    @endif
                </div>

                <!-- SHU -->
                <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2 transition-all">
                    <flux:checkbox wire:model.live.debounce.500ms="tarikShu" label="Sisa Hasil Usaha / SHU (Saldo: Rp {{ number_format($this->shu, 0, ',', '.') }})" />
                    @if($tarikShu)
                        <div class="pt-1 pl-6">
                            <flux:input wire:model.live="nominalShu" type="number" size="sm" placeholder="Nominal Tarik SHU (Rp)" max="{{ $this->shu }}" required />
                        </div>
                    @endif
                </div>
            </div>

            <!-- Total Penarikan Display -->
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/40 flex justify-between items-center mt-1">
                <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Total Penarikan</span>
                <span class="text-lg font-bold text-blue-900 dark:text-blue-200">
                    Rp {{ number_format($this->totalNominalTarik, 0, ',', '.') }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-1">
                <flux:input 
                    wire:model.live="bankTujuan" 
                    label="Bank Tujuan" 
                    placeholder="Contoh: BCA"
                    required
                />

                <flux:input 
                    wire:model.live="nomorRekening" 
                    label="Nomor Rekening" 
                    placeholder="Contoh: 1234567890"
                    required
                />
            </div>

            <flux:textarea 
                wire:model.live="keteranganTarik" 
                label="Keterangan / Keperluan" 
                placeholder="Tuliskan tujuan penarikan saldo..."
                rows="2"
            />

            <div class="flex justify-end gap-2 mt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane" :disabled="$this->totalNominalTarik <= 0">Lanjut Konfirmasi</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Konfirmasi Pengajuan -->
    <flux:modal name="konfirmasi-pengajuan" class="md:w-[28rem]">
        <div class="flex flex-col gap-6">
            <div>
                <div class="flex items-center gap-3 text-blue-500">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-full">
                        <flux:icon name="question-mark-circle" class="w-6 h-6" />
                    </div>
                    <flux:heading size="lg">Konfirmasi Penarikan</flux:heading>
                </div>
                <flux:text size="sm" class="mt-4">
                    Apakah Anda yakin ingin mengajukan penarikan saldo dengan rincian sebagai berikut?
                </flux:text>
            </div>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-3 text-sm">
                <div>
                    <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">Rincian Penarikan</div>
                    <div class="space-y-1">
                        @if($tarikSukarela && $nominalSukarela)
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Simpanan Sukarela</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">Rp {{ number_format((int)$nominalSukarela, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($tarikLain && $nominalLain)
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Simpanan Lain-lain</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">Rp {{ number_format((int)$nominalLain, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($tarikShu && $nominalShu)
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">SHU</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-100">Rp {{ number_format((int)$nominalShu, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <div class="flex justify-between items-center pt-1">
                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">Total Tarikan</span>
                    <span class="font-bold text-base text-blue-600 dark:text-blue-400">
                        Rp {{ number_format($this->totalNominalTarik, 0, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-between pt-1">
                    <span class="text-zinc-500">Bank Tujuan</span>
                    <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $bankTujuan ?: '-' }} - {{ $nomorRekening ?: '-' }}</span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button x-on:click="$flux.modal('ajukan-penarikan').show()" variant="ghost">Kembali</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="submitPengajuan" x-on:click="$flux.modal('konfirmasi-pengajuan').close(); setTimeout(() => $flux.modal('sukses-pengajuan').show(), 300)">Ya, Kirim Pengajuan</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Sukses Pengajuan -->
    <flux:modal name="sukses-pengajuan" class="md:w-[28rem]">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500 mb-2">
                <flux:icon name="check-circle" class="w-10 h-10" />
            </div>
            
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Permintaan penarikan saldo Anda telah berhasil diajukan dan akan diproses oleh tim pengurus koperasi secepatnya.
            </flux:text>

            <div class="w-full mt-4">
                <flux:modal.close>
                    <flux:button variant="primary" class="w-full">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
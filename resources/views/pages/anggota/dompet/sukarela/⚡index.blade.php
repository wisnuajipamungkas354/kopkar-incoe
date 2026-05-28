<?php

use App\Models\PengajuanPerubahanPotonganPayroll;
use App\Models\PotonganPayrollEmployee;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\MutasiSaldoMember;
use Carbon\Carbon;
use Flux\Flux;

new #[Layout('layouts::anggota', ['title' => 'Simpanan Sukarela'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $employeeId;
    public $saldoSukarela = 0;
    public $nominalBaru = '';
    public $nominalSaatIni = 0;
    public $pengajuanPending = 0;
    public $activeTab = 'mutasi';

    public function mount()
    {
        $this->employeeId = auth('web')->user()->userable->id;
        $this->refreshStats();
    }

    public function refreshStats()
    {
        $latest = MutasiSaldoMember::where('employee_id', $this->employeeId)
            ->where('jenis_saldo', 'simpanan_sukarela')
            ->latest('id')
            ->first();
        $this->saldoSukarela = $latest ? $latest->saldo_sesudah : 0;

        $tanggalSekarang = Carbon::now()->format('Y-m-d');
        $getData = PotonganPayrollEmployee::where('jenis_potongan', 'simpanan_sukarela')
            ->where('employee_id', $this->employeeId)
            ->where('tanggal_mulai_berlaku', '<=', $tanggalSekarang)
            ->latest()
            ->first();
        $this->nominalSaatIni = $getData ? $getData->nominal : 0;

        $this->pengajuanPending = PengajuanPerubahanPotonganPayroll::where('jenis_potongan', 'simpanan_sukarela')
            ->where('employee_id', $this->employeeId)
            ->where('status', 'pending')
            ->count();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function submitUbahSetoran()
    {
        $this->validate([
            'nominalBaru' => 'required|numeric|min:0',
        ], [
            'nominalBaru.required' => 'Nominal baru wajib diisi.',
            'nominalBaru.numeric'  => 'Nominal baru harus berupa angka.',
            'nominalBaru.min'      => 'Nominal tidak boleh kurang dari Rp 0.',
        ]);

        $tanggalBerlaku = Carbon::now()->addMonths(1)->firstOfMonth()->format('Y-m-d');

        PengajuanPerubahanPotonganPayroll::create([
            'employee_id'      => $this->employeeId,
            'jenis_potongan'   => 'simpanan_sukarela',
            'nominal_lama'     => $this->nominalSaatIni,
            'nominal_baru'     => (int) $this->nominalBaru,
            'status'           => 'pending',
            'tanggal_berlaku'  => $tanggalBerlaku,
            'diajukan_oleh'    => auth('web')->user()->id,
            'tanggal_pengajuan'=> Carbon::now()->format('Y-m-d'),
        ]);

        $this->reset('nominalBaru');
        $this->refreshStats();

        Flux::modal('konfirmasi-ubah-setoran')->close();
        Flux::modal('sukses-ubah-setoran')->show();
    }

    #[Computed]
    public function mutasiSimpanan()
    {
        $query = MutasiSaldoMember::where('employee_id', $this->employeeId)
            ->where('jenis_saldo', 'simpanan_sukarela');

        if (!empty($this->search)) {
            $query->where('keterangan', 'like', '%' . $this->search . '%');
        }

        return $query->latest('id')->paginate(10);
    }

    #[Computed]
    public function daftarPengajuanSetoran()
    {
        $query = PengajuanPerubahanPotonganPayroll::where('employee_id', $this->employeeId)
            ->where('jenis_potongan', 'simpanan_sukarela');

        if (!empty($this->search)) {
            $query->where('status', 'like', '%' . $this->search . '%');
        }

        return $query->latest()->paginate(10);
    }
};
?>

<div class="space-y-6">
    {{-- ===== PAGE HEADER ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Simpanan Sukarela</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500 dark:text-zinc-400">Kelola simpanan sukarela, setoran rutin, dan setor tambahan.</flux:text>
        </div>
        <div class="flex gap-2 shrink-0">
            <flux:modal.trigger name="ubah-setoran">
                <flux:button size="sm" variant="outline" icon="pencil-square">Ubah Setoran Rutin</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:separator variant="subtle" />

    {{-- ===== SALDO HERO CARD ===== --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 dark:from-emerald-600 dark:to-teal-700 p-6 text-white shadow-lg">
        {{-- decorative circles --}}
        <div class="absolute -top-8 -right-8 w-40 h-40 rounded-full bg-white/10 pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-6 w-32 h-32 rounded-full bg-white/10 pointer-events-none"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-1">
                <flux:icon name="banknotes" class="w-5 h-5 opacity-80" />
                <span class="text-sm font-medium opacity-80">Saldo Simpanan Sukarela</span>
            </div>
            <div class="text-3xl sm:text-4xl font-bold tracking-tight mt-1">
                Rp {{ number_format($saldoSukarela, 0, ',', '.') }}
            </div>

            {{-- Sub-stats --}}
            <div class="flex flex-wrap gap-4 mt-5">
                <div class="bg-white/15 rounded-xl px-4 py-3 flex-1 min-w-[130px]">
                    <div class="text-xs font-medium opacity-75 uppercase tracking-wider">Setoran Rutin</div>
                    <div class="text-lg font-bold mt-0.5">
                        Rp {{ number_format($nominalSaatIni, 0, ',', '.') }}
                        <span class="text-xs font-normal opacity-75">/bln</span>
                    </div>
                </div>
                <div class="bg-white/15 rounded-xl px-4 py-3 flex-1 min-w-[130px]">
                    <div class="text-xs font-medium opacity-75 uppercase tracking-wider">Pengajuan Pending</div>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-lg font-bold">{{ $pengajuanPending }}</span>
                        <span class="text-xs font-normal opacity-75">berkas</span>
                        @if($pengajuanPending > 0)
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-orange-400 text-white text-[9px] font-bold">!</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== QUICK ACTION BUTTONS ===== --}}
    <div class="grid grid-cols-2 gap-3">
        <flux:modal.trigger name="ubah-setoran">
            <button class="group flex flex-col items-center justify-center gap-2 p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl hover:border-emerald-400 hover:shadow-md transition-all text-center w-full">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                    <flux:icon name="pencil-square" class="w-5 h-5" />
                </div>
                <div>
                    <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Ubah Setoran</div>
                    <div class="text-xs text-zinc-400">Rutin bulanan</div>
                </div>
            </button>
        </flux:modal.trigger>

        <flux:modal.trigger name="setor-tambahan">
            <button class="group flex flex-col items-center justify-center gap-2 p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl hover:border-blue-400 hover:shadow-md transition-all text-center w-full">
                <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                    <flux:icon name="plus-circle" class="w-5 h-5" />
                </div>
                <div>
                    <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Setor Tambahan</div>
                    <div class="text-xs text-zinc-400">Via QRIS instan</div>
                </div>
            </button>
        </flux:modal.trigger>
    </div>

    {{-- ===== TABS & TABLE ===== --}}
    <div class="flex border-b border-zinc-200 dark:border-zinc-700 gap-6">
        <button
            wire:click="switchTab('mutasi')"
            class="pb-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'mutasi' ? 'border-emerald-500 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >Riwayat Mutasi</button>
        <button
            wire:click="switchTab('pengajuan')"
            class="pb-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'pengajuan' ? 'border-emerald-500 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Pengajuan Setoran
            @if($pengajuanPending > 0)
                <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-orange-400 text-white text-[9px] font-bold">{{ $pengajuanPending }}</span>
            @endif
        </button>
    </div>

    <flux:card class="flex flex-col">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
            <flux:heading size="lg">
                {{ $activeTab === 'mutasi' ? 'Riwayat Mutasi Simpanan' : 'Riwayat Pengajuan Perubahan Setoran' }}
            </flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-56" placeholder="Cari..." icon="magnifying-glass" />
        </div>

        <flux:separator variant="subtle" class="mb-2" />

        @if($activeTab === 'mutasi')
            {{-- MOBILE: Card list --}}
            <div class="sm:hidden space-y-3">
                @forelse($this->mutasiSimpanan as $row)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 {{ $row->jenis_mutasi === 'kredit' ? 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
                            <flux:icon name="{{ $row->jenis_mutasi === 'kredit' ? 'arrow-down' : 'arrow-up' }}" class="w-4 h-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                    {{ $row->keterangan ?: ucfirst(str_replace('_', ' ', $row->sumber_transaksi)) }}
                                </span>
                                <span class="text-sm font-bold ml-2 shrink-0 {{ $row->jenis_mutasi === 'kredit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $row->jenis_mutasi === 'kredit' ? '+' : '-' }} Rp {{ number_format($row->nominal, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-zinc-400">{{ $row->created_at->format('d/m/Y') }}</span>
                                <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                @if($row->sumber_transaksi === 'payroll')
                                    <flux:badge color="blue" size="sm">Payroll</flux:badge>
                                @elseif($row->sumber_transaksi === 'penarikan_saldo')
                                    <flux:badge color="red" size="sm">Penarikan</flux:badge>
                                @elseif($row->sumber_transaksi === 'pembagian_shu')
                                    <flux:badge color="purple" size="sm">SHU</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ ucfirst(str_replace('_', ' ', $row->sumber_transaksi)) }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-10">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Tidak ada riwayat mutasi.</p>
                    </div>
                @endforelse
            </div>

            {{-- DESKTOP: Table --}}
            <div class="hidden sm:block overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Keterangan</flux:table.column>
                        <flux:table.column>Sumber</flux:table.column>
                        <flux:table.column>Saldo Sesudah</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->mutasiSimpanan as $row)
                            <flux:table.row :key="$row->id">
                                <flux:table.cell class="text-zinc-500">{{ $row->created_at->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $row->keterangan ?: '-' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($row->sumber_transaksi === 'payroll')
                                        <flux:badge color="blue" size="sm" inset="top bottom">Payroll</flux:badge>
                                    @elseif($row->sumber_transaksi === 'penarikan_saldo')
                                        <flux:badge color="red" size="sm" inset="top bottom">Penarikan</flux:badge>
                                    @elseif($row->sumber_transaksi === 'pembagian_shu')
                                        <flux:badge color="purple" size="sm" inset="top bottom">SHU</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ ucfirst(str_replace('_', ' ', $row->sumber_transaksi)) }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-zinc-600 dark:text-zinc-400">
                                    Rp {{ number_format($row->saldo_sesudah, 0, ',', '.') }}
                                </flux:table.cell>
                                <flux:table.cell class="{{ $row->jenis_mutasi === 'kredit' ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-red-600 dark:text-red-400 font-semibold' }}">
                                    {{ $row->jenis_mutasi === 'kredit' ? '+' : '-' }} Rp {{ number_format($row->nominal, 0, ',', '.') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-400 py-8">Tidak ada riwayat mutasi simpanan.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-4">{{ $this->mutasiSimpanan->links() }}</div>

        @else
            {{-- MOBILE: Card list pengajuan --}}
            <div class="sm:hidden space-y-3">
                @forelse($this->daftarPengajuanSetoran as $row)
                    <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-zinc-400">{{ \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</span>
                            @if($row->status === 'pending')
                                <flux:badge color="orange" size="sm">Pending</flux:badge>
                            @elseif($row->status === 'disetujui')
                                <flux:badge color="green" size="sm">Disetujui</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">Ditolak</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">Nominal Lama</span>
                            <span class="font-medium line-through text-zinc-400">Rp {{ number_format($row->nominal_lama, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">Nominal Baru</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_baru, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-500">Berlaku Mulai</span>
                            <span class="text-zinc-600 dark:text-zinc-400">{{ \Carbon\Carbon::parse($row->tanggal_berlaku)->translatedFormat('F Y') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-10">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Tidak ada pengajuan perubahan setoran.</p>
                    </div>
                @endforelse
            </div>

            {{-- DESKTOP: Table pengajuan --}}
            <div class="hidden sm:block overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tanggal Pengajuan</flux:table.column>
                        <flux:table.column>Nominal Lama</flux:table.column>
                        <flux:table.column>Nominal Baru</flux:table.column>
                        <flux:table.column>Berlaku Mulai</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->daftarPengajuanSetoran as $row)
                            <flux:table.row :key="'p-' . $row->id">
                                <flux:table.cell class="text-zinc-500">{{ \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-400 line-through">Rp {{ number_format($row->nominal_lama, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_baru, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal_berlaku)->translatedFormat('F Y') }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'pending')
                                        <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                    @elseif($row->status === 'disetujui')
                                        <flux:badge color="green" size="sm" inset="top bottom">Disetujui</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm" inset="top bottom">Ditolak</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-400 py-8">Tidak ada pengajuan perubahan setoran.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-4">{{ $this->daftarPengajuanSetoran->links() }}</div>
        @endif
    </flux:card>

    {{-- ===== MODAL SETOR TAMBAHAN (QRIS) ===== --}}
    <flux:modal name="setor-tambahan" class="md:w-xl space-y-4">
        <div>
            <flux:heading size="lg">Setor Simpanan Tambahan</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Setoran insidental di luar setoran rutin via QRIS instan — terverifikasi otomatis.</flux:text>
        </div>
        <flux:separator variant="subtle" />
        <livewire:qris />
    </flux:modal>

    {{-- ===== MODAL UBAH SETORAN RUTIN ===== --}}
    <flux:modal name="ubah-setoran" class="md:w-lg space-y-6">
        <div>
            <flux:heading size="lg">Ubah Setoran Rutin Bulanan</flux:heading>
            <flux:text size="sm" class="mt-1">Pengajuan akan berlaku pada bulan berikutnya setelah disetujui pengurus.</flux:text>
        </div>

        <form x-on:submit.prevent="$flux.modal('konfirmasi-ubah-setoran').show(); $flux.modal('ubah-setoran').close()" class="flex flex-col gap-4">
            <flux:input
                label="Setoran Rutin Saat Ini"
                value="Rp {{ number_format($nominalSaatIni, 0, ',', '.') }}/bulan"
                disabled
            />
            <flux:input
                wire:model.live="nominalBaru"
                type="number"
                label="Nominal Baru (Rp)"
                placeholder="Contoh: 200000"
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

    {{-- ===== MODAL KONFIRMASI UBAH SETORAN ===== --}}
    <flux:modal name="konfirmasi-ubah-setoran" class="md:w-md">
        <div class="flex flex-col gap-6">
            <div>
                <div class="flex items-center gap-3 text-orange-500">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/40 rounded-full">
                        <flux:icon name="exclamation-triangle" class="w-6 h-6" />
                    </div>
                    <flux:heading size="lg">Konfirmasi Perubahan</flux:heading>
                </div>
                <flux:text size="sm" class="mt-4">Apakah Anda yakin ingin mengajukan perubahan nominal setoran rutin bulanan menjadi:</flux:text>
            </div>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl text-center border border-zinc-200 dark:border-zinc-800">
                <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Nominal Baru</div>
                <div class="text-2xl font-bold mt-1 text-zinc-800 dark:text-zinc-100">
                    Rp {{ $nominalBaru ? number_format((int)$nominalBaru, 0, ',', '.') : 0 }}
                </div>
                <div class="text-xs text-zinc-400 mt-0.5">per bulan, berlaku mulai {{ \Carbon\Carbon::now()->addMonths(1)->firstOfMonth()->translatedFormat('F Y') }}</div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button x-on:click="$flux.modal('ubah-setoran').show()" variant="ghost">Kembali</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" color="orange" wire:click="submitUbahSetoran">Ya, Ajukan</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ===== MODAL SUKSES ===== --}}
    <flux:modal name="sukses-ubah-setoran" class="md:w-md">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500 mb-2">
                <flux:icon name="check-circle" class="w-10 h-10" />
            </div>
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pengajuan perubahan setoran rutin bulanan Anda telah dikirim dan sedang menunggu verifikasi pengurus.
            </flux:text>
            <div class="w-full mt-4">
                <flux:modal.close>
                    <flux:button variant="primary" class="w-full">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
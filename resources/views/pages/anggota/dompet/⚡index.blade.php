<?php

use App\Models\MutasiSaldoMember;
use App\Models\NamaBank;
use App\Models\PenarikanSaldo;
use App\Models\PengajuanPerubahanPotonganPayroll;
use App\Models\PotonganPayrollEmployee;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux\Flux;

new #[Layout('layouts::anggota', ['title' => 'Dompet'])] class extends Component
{
    use WithPagination;

    public $activeTab = 'mutasi'; // mutasi | pengajuan_setoran | pengajuan_tarik
    public $employeeId;
    public $userId;
    public $search = '';

    // Balances
    public $saldoSukarela = 0;
    public $saldoLain     = 0;
    public $saldoShu      = 0;
    public $nominalSaatIni = 0;

    // Form: Ubah Setoran
    public $nominalBaru = '';

    // Form: Tarik Saldo
    public $tarikSukarela   = false;
    public $tarikLain       = false;
    public $tarikShu        = false;
    public $nominalSukarela = '';
    public $nominalLain     = '';
    public $nominalShu      = '';
    public $namaBank        = '';
    public $noRekening      = '';
    public $namaPemilik     = '';
    public $keteranganTarik = '';

    public function mount()
    {
        $user = auth('web')->user();
        $this->userId     = $user->id;
        $this->employeeId = $user->userable->id;
        $this->refreshBalances();
    }

    public function refreshBalances()
    {
        $pick = fn($jenis) => MutasiSaldoMember::where('employee_id', $this->employeeId)
            ->where('jenis_saldo', $jenis)->latest('id')->value('saldo_sesudah') ?? 500000;

        $this->saldoSukarela = $pick('simpanan_sukarela');
        $this->saldoLain     = $pick('simpanan_lain_lain');
        $this->saldoShu      = $pick('shu');

        $this->nominalSaatIni = PotonganPayrollEmployee::where('jenis_potongan', 'simpanan_sukarela')
            ->where('employee_id', $this->employeeId)
            ->where('tanggal_mulai_berlaku', '<=', Carbon::now()->format('Y-m-d'))
            ->latest()->value('nominal') ?? 0;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ── Computed queries ──────────────────────────────────────────

    #[Computed]
    public function mutasiSukarela()
    {
        $q = MutasiSaldoMember::where('employee_id', $this->employeeId)
            ->where('jenis_saldo', 'simpanan_sukarela');
        if ($this->search) $q->where('keterangan', 'like', "%{$this->search}%");
        return $q->latest('id')->paginate(10);
    }

    #[Computed]
    public function pengajuanSetoran()
    {
        $q = PengajuanPerubahanPotonganPayroll::where('employee_id', $this->employeeId)
            ->where('jenis_potongan', 'simpanan_sukarela');
        if ($this->search) $q->where('status', 'like', "%{$this->search}%");
        return $q->latest()->paginate(10);
    }

    #[Computed]
    public function pengajuanTarik()
    {
        $q = PenarikanSaldo::where('employee_id', $this->employeeId);
        if ($this->search) {
            $q->where(fn($x) => $x
                ->where('nomor_pengajuan', 'like', "%{$this->search}%")
                ->orWhere('nama_bank', 'like', "%{$this->search}%"));
        }
        return $q->with('detailPenarikanSaldo')->latest()->paginate(10);
    }

    #[Computed]
    public function totalNominalTarik()
    {
        return ($this->tarikSukarela ? (int)$this->nominalSukarela : 0)
             + ($this->tarikLain     ? (int)$this->nominalLain     : 0)
             + ($this->tarikShu      ? (int)$this->nominalShu      : 0);
    }

    #[Computed]
    public function daftarBank()
    {
        return NamaBank::orderBy('nama_bank')->get();
    }

    #[Computed]
    public function pendingSetoran()
    {
        return PengajuanPerubahanPotonganPayroll::where('employee_id', $this->employeeId)
            ->where('jenis_potongan', 'simpanan_sukarela')
            ->where('status', 'pending')->count();
    }

    // ── Actions ───────────────────────────────────────────────────

    public function openTarikSaldo()
    {
        $employee = auth('web')->user()->userable;

        if ($employee) {
            $this->namaBank    = $this->namaBank    ?: ($employee->nama_bank               ?? '');
            $this->noRekening  = $this->noRekening  ?: ($employee->no_rekening              ?? '');
            $this->namaPemilik = $this->namaPemilik ?: ($employee->nama_pemilik_rekening     ?? '');
        }

        Flux::modal('ajukan-penarikan')->show();
    }

    public function submitUbahSetoran()
    {
        $this->validate(['nominalBaru' => 'required|numeric|min:0']);

        PengajuanPerubahanPotonganPayroll::create([
            'employee_id'       => $this->employeeId,
            'jenis_potongan'    => 'simpanan_sukarela',
            'nominal_lama'      => $this->nominalSaatIni,
            'nominal_baru'      => (int) $this->nominalBaru,
            'status'            => 'pending',
            'tanggal_berlaku'   => Carbon::now()->addMonths(1)->firstOfMonth()->format('Y-m-d'),
            'diajukan_oleh'     => $this->userId,
            'tanggal_pengajuan' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->reset('nominalBaru');
        $this->refreshBalances();
        Flux::modal('konfirmasi-ubah-setoran')->close();
        Flux::modal('sukses-ubah-setoran')->show();
    }

    public function getValidationRules()
    {
        $rules = [
            'namaBank'    => 'required|string|max:100',
            'noRekening'  => 'required|string|max:50',
            'namaPemilik' => 'required|string|max:150',
        ];

        if ($this->tarikSukarela) {
            $rules['nominalSukarela'] = 'required|numeric|min:1|max:' . $this->saldoSukarela;
        }
        if ($this->tarikLain) {
            $rules['nominalLain'] = 'required|numeric|min:1|max:' . $this->saldoLain;
        }
        if ($this->tarikShu) {
            $rules['nominalShu'] = 'required|numeric|min:1|max:' . $this->saldoShu;
        }

        return $rules;
    }

    public function getValidationMessages()
    {
        return [
            'nominalSukarela.max' => 'Nominal penarikan melebihi saldo sukarela Anda.',
            'nominalLain.max' => 'Nominal penarikan melebihi saldo lain-lain Anda.',
            'nominalShu.max' => 'Nominal penarikan melebihi saldo SHU Anda.',
            'nominalSukarela.min' => 'Nominal penarikan minimal Rp 1.',
            'nominalLain.min' => 'Nominal penarikan minimal Rp 1.',
            'nominalShu.min' => 'Nominal penarikan minimal Rp 1.',
            'nominalSukarela.required' => 'Nominal penarikan wajib diisi.',
            'nominalLain.required' => 'Nominal penarikan wajib diisi.',
            'nominalShu.required' => 'Nominal penarikan wajib diisi.',
            'namaBank.required' => 'Nama bank wajib diisi.',
            'noRekening.required' => 'Nomor rekening wajib diisi.',
            'namaPemilik.required' => 'Nama pemilik rekening wajib diisi.',
        ];
    }

    public function proceedToConfirmation()
    {
        $this->validate($this->getValidationRules(), $this->getValidationMessages());

        if ($this->totalNominalTarik <= 0) {
            Flux::toast(heading: 'Peringatan', text: 'Pilih minimal satu sumber saldo dengan nominal valid.', variant: 'warning');
            return;
        }

        $this->js("Flux.modal('konfirmasi-penarikan').show()");
        $this->js("Flux.modal('ajukan-penarikan').close()");
    }

    public function submitPenarikan()
    {
        $this->validate($this->getValidationRules(), $this->getValidationMessages());

        if ($this->totalNominalTarik <= 0) return;

        $penarikan = PenarikanSaldo::create([
            'nomor_pengajuan'       => 'TARIK-' . strtoupper(uniqid()),
            'employee_id'           => $this->employeeId,
            'total_penarikan'       => $this->totalNominalTarik,
            'no_rekening'           => $this->noRekening,
            'nama_bank'             => $this->namaBank,
            'nama_pemilik_rekening' => $this->namaPemilik,
            'status'                => 'diajukan',
            'diajukan_oleh'         => $this->userId,
            'diajukan_pada'         => Carbon::now(),
            'catatan'               => $this->keteranganTarik,
        ]);

        if ($this->tarikSukarela && (int)$this->nominalSukarela > 0)
            $penarikan->detailPenarikanSaldo()->create(['sumber_saldo' => 'simpanan_sukarela', 'nominal' => (int)$this->nominalSukarela]);
        if ($this->tarikLain && (int)$this->nominalLain > 0)
            $penarikan->detailPenarikanSaldo()->create(['sumber_saldo' => 'simpanan_lain_lain', 'nominal' => (int)$this->nominalLain]);
        if ($this->tarikShu && (int)$this->nominalShu > 0)
            $penarikan->detailPenarikanSaldo()->create(['sumber_saldo' => 'shu', 'nominal' => (int)$this->nominalShu]);

        $this->reset(['tarikSukarela','tarikLain','tarikShu','nominalSukarela','nominalLain','nominalShu','namaBank','noRekening','namaPemilik','keteranganTarik']);
        Flux::modal('konfirmasi-penarikan')->close();
        Flux::modal('sukses-penarikan')->show();
    }
};
?>

<div class="space-y-5">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-emerald-500 to-teal-600 dark:from-emerald-600 dark:to-teal-700 p-5 sm:p-6 text-white shadow-lg">
        {{-- decorative blobs --}}
        <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-white/10 pointer-events-none"></div>
        <div class="absolute -bottom-14 -left-8 w-36 h-36 rounded-full bg-white/10 pointer-events-none"></div>

        <div class="relative z-10">
            {{-- Label --}}
            <div class="flex items-center gap-1.5 mb-0.5">
                <flux:icon name="wallet" class="w-4 h-4 opacity-70" />
                <span class="text-sm font-medium opacity-75">Simpanan Sukarela</span>
            </div>

            {{-- Main balance --}}
            <div class="text-3xl sm:text-4xl font-bold tracking-tight">
                Rp {{ number_format($saldoSukarela, 0, ',', '.') }}
            </div>

            {{-- Sub-balances --}}
            <div class="flex flex-wrap gap-3 mt-4">
                <div class="flex-1 min-w-[120px] bg-white/15 rounded-xl px-3 py-2.5">
                    <div class="text-[10px] font-semibold opacity-70 uppercase tracking-wider">Simpanan Lain-lain</div>
                    <div class="text-base font-bold mt-0.5">Rp {{ number_format($saldoLain, 0, ',', '.') }}</div>
                </div>
                <div class="flex-1 min-w-[120px] bg-white/15 rounded-xl px-3 py-2.5">
                    <div class="text-[10px] font-semibold opacity-70 uppercase tracking-wider">SHU</div>
                    <div class="text-base font-bold mt-0.5">Rp {{ number_format($saldoShu, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════
         ACTION BUTTONS
    ═══════════════════════════════ --}}
    <div class="grid grid-cols-3 gap-3">
        {{-- Ubah Setoran --}}
        <flux:modal.trigger name="ubah-setoran">
            <button class="group flex flex-col items-center gap-2 p-3 sm:p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl hover:border-emerald-400 hover:shadow-md transition-all w-full">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                    <flux:icon name="pencil-square" class="w-5 h-5" />
                </div>
                <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 text-center leading-tight">Ubah Setoran</span>
            </button>
        </flux:modal.trigger>

        {{-- Tarik Saldo --}}
        <button wire:click="openTarikSaldo"
            class="group flex flex-col items-center gap-2 p-3 sm:p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl hover:border-red-400 hover:shadow-md transition-all w-full">
            <div class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950/40 flex items-center justify-center text-red-500 dark:text-red-400 group-hover:scale-110 transition-transform">
                <flux:icon name="arrow-up-tray" class="w-5 h-5" />
            </div>
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 text-center leading-tight">Tarik Saldo</span>
        </button>

        {{-- Setor Tambahan --}}
        <flux:modal.trigger name="setor-tambahan">
            <button class="group flex flex-col items-center gap-2 p-3 sm:p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl hover:border-blue-400 hover:shadow-md transition-all w-full">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                    <flux:icon name="plus-circle" class="w-5 h-5" />
                </div>
                <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 text-center leading-tight">Setor Tambahan</span>
            </button>
        </flux:modal.trigger>
    </div>

    {{-- ═══════════════════════════════
         TAB NAVIGATION
    ═══════════════════════════════ --}}
    <div class="flex border-b border-zinc-200 dark:border-zinc-700 gap-1 overflow-x-auto">
        @php
            $tabs = [
                'mutasi'             => 'Riwayat Mutasi',
                'pengajuan_setoran'  => 'Pengajuan Setoran',
                'pengajuan_tarik'    => 'Pengajuan Tarik',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <button wire:click="switchTab('{{ $key }}')"
                    class="shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 transition-all
                           {{ $activeTab === $key
                              ? 'border-emerald-500 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                              : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                {{ $label }}
                @if($key === 'pengajuan_setoran' && $this->pendingSetoran > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-orange-400 text-white text-[9px] font-bold">{{ $this->pendingSetoran }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ═══════════════════════════════
         TAB CONTENT
    ═══════════════════════════════ --}}
    <flux:card class="flex flex-col">
        {{-- Search --}}
        <div class="flex justify-between items-center mb-4">
            <flux:heading size="lg">{{ $tabs[$activeTab] }}</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-52" placeholder="Cari..." icon="magnifying-glass" />
        </div>
        <flux:separator variant="subtle" class="mb-3" />

        {{-- ── TAB: RIWAYAT MUTASI ─────────────── --}}
        @if($activeTab === 'mutasi')
            {{-- Mobile --}}
            <div class="sm:hidden space-y-2">
                @forelse($this->mutasiSukarela as $row)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0
                            {{ $row->jenis_mutasi === 'kredit' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' }}">
                            <flux:icon name="{{ $row->jenis_mutasi === 'kredit' ? 'arrow-down' : 'arrow-up' }}" class="w-4 h-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                    {{ $row->keterangan ?: ucfirst(str_replace('_', ' ', $row->sumber_transaksi)) }}
                                </span>
                                <span class="text-sm font-bold shrink-0 {{ $row->jenis_mutasi === 'kredit' ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                    {{ $row->jenis_mutasi === 'kredit' ? '+' : '-' }} Rp {{ number_format($row->nominal, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-zinc-400">{{ $row->created_at->format('d/m/Y') }}</span>
                                <span class="text-zinc-300 dark:text-zinc-600 text-xs">·</span>
                                @if($row->sumber_transaksi === 'payroll') <flux:badge color="blue" size="sm">Payroll</flux:badge>
                                @elseif($row->sumber_transaksi === 'penarikan_saldo') <flux:badge color="red" size="sm">Penarikan</flux:badge>
                                @elseif($row->sumber_transaksi === 'pembagian_shu') <flux:badge color="purple" size="sm">SHU</flux:badge>
                                @else <flux:badge color="zinc" size="sm">{{ ucfirst(str_replace('_',' ',$row->sumber_transaksi)) }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-10">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Belum ada mutasi simpanan sukarela.</p>
                    </div>
                @endforelse
            </div>
            {{-- Desktop --}}
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
                        @forelse($this->mutasiSukarela as $row)
                            <flux:table.row :key="$row->id">
                                <flux:table.cell class="text-zinc-400 text-sm">{{ $row->created_at->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="font-medium text-zinc-700 dark:text-zinc-300">{{ $row->keterangan ?: '-' }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->sumber_transaksi === 'payroll') <flux:badge color="blue" size="sm" inset="top bottom">Payroll</flux:badge>
                                    @elseif($row->sumber_transaksi === 'penarikan_saldo') <flux:badge color="red" size="sm" inset="top bottom">Penarikan</flux:badge>
                                    @elseif($row->sumber_transaksi === 'pembagian_shu') <flux:badge color="purple" size="sm" inset="top bottom">SHU</flux:badge>
                                    @else <flux:badge color="zinc" size="sm" inset="top bottom">{{ ucfirst(str_replace('_',' ',$row->sumber_transaksi)) }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="text-zinc-400 text-sm">Rp {{ number_format($row->saldo_sesudah, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="font-semibold {{ $row->jenis_mutasi === 'kredit' ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                    {{ $row->jenis_mutasi === 'kredit' ? '+' : '-' }} Rp {{ number_format($row->nominal, 0, ',', '.') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-400 py-8">Belum ada mutasi.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-4">{{ $this->mutasiSukarela->links() }}</div>

        {{-- ── TAB: PENGAJUAN SETORAN ──────────── --}}
        @elseif($activeTab === 'pengajuan_setoran')
            {{-- Mobile --}}
            <div class="sm:hidden space-y-2">
                @forelse($this->pengajuanSetoran as $row)
                    <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs text-zinc-400">{{ Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</span>
                            @if($row->status === 'pending') <flux:badge color="orange" size="sm">Pending</flux:badge>
                            @elseif($row->status === 'disetujui') <flux:badge color="green" size="sm">Disetujui</flux:badge>
                            @else <flux:badge color="red" size="sm">Ditolak</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center justify-around text-sm">
                            <div class="text-center">
                                <div class="text-[10px] text-zinc-400">Nominal Lama</div>
                                <div class="font-medium text-zinc-400 line-through">Rp {{ number_format($row->nominal_lama, 0, ',', '.') }}</div>
                            </div>
                            <flux:icon name="arrow-right" class="w-4 h-4 text-zinc-300" />
                            <div class="text-center">
                                <div class="text-[10px] text-zinc-400">Nominal Baru</div>
                                <div class="font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_baru, 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-zinc-400 text-center mt-1.5">Berlaku: {{ Carbon::parse($row->tanggal_berlaku)->translatedFormat('F Y') }}</div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-10">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Belum ada pengajuan perubahan setoran.</p>
                    </div>
                @endforelse
            </div>
            {{-- Desktop --}}
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
                        @forelse($this->pengajuanSetoran as $row)
                            <flux:table.row :key="'ps-'.$row->id">
                                <flux:table.cell class="text-zinc-400 text-sm">{{ Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-400 line-through text-sm">Rp {{ number_format($row->nominal_lama, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_baru, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell>{{ Carbon::parse($row->tanggal_berlaku)->translatedFormat('F Y') }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'pending') <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                    @elseif($row->status === 'disetujui') <flux:badge color="green" size="sm" inset="top bottom">Disetujui</flux:badge>
                                    @else <flux:badge color="red" size="sm" inset="top bottom">Ditolak</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-400 py-8">Belum ada pengajuan perubahan setoran.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-4">{{ $this->pengajuanSetoran->links() }}</div>

        {{-- ── TAB: PENGAJUAN TARIK ────────────── --}}
        @elseif($activeTab === 'pengajuan_tarik')
            {{-- Mobile --}}
            <div class="sm:hidden space-y-2">
                @forelse($this->pengajuanTarik as $row)
                    <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-mono text-zinc-400 truncate">{{ $row->nomor_pengajuan }}</span>
                            @if($row->status === 'diajukan') <flux:badge color="orange" size="sm">Diajukan</flux:badge>
                            @elseif($row->status === 'disetujui') <flux:badge color="blue" size="sm">Disetujui</flux:badge>
                            @elseif($row->status === 'diproses') <flux:badge color="cyan" size="sm">Diproses</flux:badge>
                            @elseif($row->status === 'selesai') <flux:badge color="green" size="sm">Selesai</flux:badge>
                            @else <flux:badge color="red" size="sm">Ditolak</flux:badge>
                            @endif
                        </div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-zinc-500">Total</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->total_penarikan, 0, ',', '.') }}</span>
                        </div>
                        <div class="text-xs text-zinc-400">{{ $row->nama_bank }} – {{ $row->no_rekening }} · {{ $row->created_at->format('d/m/Y') }}</div>
                        @if($row->detailPenarikanSaldo->count())
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                @foreach($row->detailPenarikanSaldo as $d)
                                    <span class="text-[10px] bg-zinc-200 dark:bg-zinc-800 text-zinc-500 px-2 py-0.5 rounded-full">
                                        {{ ucwords(str_replace('_',' ',$d->sumber_saldo)) }}: Rp {{ number_format($d->nominal, 0, ',', '.') }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-10">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Belum ada pengajuan penarikan.</p>
                    </div>
                @endforelse
            </div>
            {{-- Desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>No. Pengajuan</flux:table.column>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Rincian</flux:table.column>
                        <flux:table.column>Total</flux:table.column>
                        <flux:table.column>Rekening</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->pengajuanTarik as $row)
                            <flux:table.row :key="'pt-'.$row->id">
                                <flux:table.cell class="font-mono text-xs text-zinc-400">{{ $row->nomor_pengajuan }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-400 text-sm">{{ $row->created_at->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell>
                                    @foreach($row->detailPenarikanSaldo as $d)
                                        <div class="text-xs text-zinc-500">{{ ucwords(str_replace('_',' ',$d->sumber_saldo)) }}: <span class="font-medium text-zinc-700 dark:text-zinc-300">Rp {{ number_format($d->nominal, 0, ',', '.') }}</span></div>
                                    @endforeach
                                </flux:table.cell>
                                <flux:table.cell class="font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->total_penarikan, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">{{ $row->nama_bank }} – {{ $row->no_rekening }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'diajukan') <flux:badge color="orange" size="sm" inset="top bottom">Diajukan</flux:badge>
                                    @elseif($row->status === 'disetujui') <flux:badge color="blue" size="sm" inset="top bottom">Disetujui</flux:badge>
                                    @elseif($row->status === 'diproses') <flux:badge color="cyan" size="sm" inset="top bottom">Diproses</flux:badge>
                                    @elseif($row->status === 'selesai') <flux:badge color="green" size="sm" inset="top bottom">Selesai</flux:badge>
                                    @else <flux:badge color="red" size="sm" inset="top bottom">Ditolak</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center text-zinc-400 py-8">Belum ada pengajuan penarikan.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            <div class="mt-4">{{ $this->pengajuanTarik->links() }}</div>
        @endif

    </flux:card>


    {{-- ═══════════════════════════════════════════
         MODALS
    ═══════════════════════════════════════════ --}}

    {{-- Modal: Setor Tambahan (QRIS) --}}
    <flux:modal name="setor-tambahan" class="md:w-xl space-y-4">
        <div>
            <flux:heading size="lg">Setor Simpanan Tambahan</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Setoran insidental via QRIS — terverifikasi otomatis.</flux:text>
        </div>
        <flux:separator variant="subtle" />
        <livewire:qris />
    </flux:modal>

    {{-- Modal: Ubah Setoran --}}
    <flux:modal name="ubah-setoran" class="md:w-lg space-y-5">
        <div>
            <flux:heading size="lg">Ubah Setoran Rutin</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Berlaku mulai bulan depan setelah disetujui pengurus.</flux:text>
        </div>
        <form x-on:submit.prevent="$flux.modal('konfirmasi-ubah-setoran').show(); $flux.modal('ubah-setoran').close()" class="flex flex-col gap-4">
            <flux:input label="Setoran Saat Ini" value="Rp {{ number_format($nominalSaatIni, 0, ',', '.') }}/bulan" disabled />
            <flux:input wire:model.live="nominalBaru" type="number" label="Nominal Baru (Rp)" placeholder="Contoh: 200000" autofocus />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Lanjut</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: Konfirmasi Ubah Setoran --}}
    <flux:modal name="konfirmasi-ubah-setoran" class="md:w-md">
        <div class="flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/40 rounded-full text-orange-500">
                    <flux:icon name="exclamation-triangle" class="w-5 h-5" />
                </div>
                <flux:heading size="lg">Konfirmasi Perubahan</flux:heading>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl text-center border border-zinc-200 dark:border-zinc-800">
                <div class="text-xs text-zinc-400 uppercase tracking-wider">Nominal Baru</div>
                <div class="text-2xl font-bold mt-1 text-zinc-800 dark:text-zinc-100">
                    Rp {{ $nominalBaru ? number_format((int)$nominalBaru, 0, ',', '.') : 0 }}
                </div>
                <div class="text-xs text-zinc-400 mt-0.5">per bulan · berlaku {{ Carbon::now()->addMonths(1)->firstOfMonth()->translatedFormat('F Y') }}</div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button x-on:click="$flux.modal('ubah-setoran').show()" variant="ghost">Kembali</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" color="orange" wire:click="submitUbahSetoran">Ya, Ajukan</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Modal: Sukses Ubah Setoran --}}
    <flux:modal name="sukses-ubah-setoran" class="md:w-md">
        <div class="flex flex-col items-center gap-4 text-center py-4">
            <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500">
                <flux:icon name="check-circle" class="w-9 h-9" />
            </div>
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">Perubahan setoran rutin Anda sedang menunggu verifikasi pengurus.</flux:text>
            <flux:modal.close><flux:button variant="primary" class="w-full mt-2">Tutup</flux:button></flux:modal.close>
        </div>
    </flux:modal>

    {{-- Modal: Ajukan Penarikan --}}
    <flux:modal name="ajukan-penarikan" class="md:w-lg space-y-5">
        <div>
            <flux:heading size="lg">Tarik Saldo</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Pilih sumber saldo yang ingin ditarik dan masukkan rekening tujuan.</flux:text>
        </div>
        <form wire:submit.prevent="proceedToConfirmation" class="flex flex-col gap-4">
            <div class="space-y-2">
                <flux:text class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Sumber Saldo & Nominal</flux:text>

                <div class="p-3 rounded-xl border transition-colors {{ $tarikSukarela ? 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800' }}">
                    <flux:checkbox wire:model.live="tarikSukarela"
                        label="Simpanan Sukarela — Rp {{ number_format($saldoSukarela, 0, ',', '.') }}" />
                    @if($tarikSukarela)
                        <div class="pl-6 mt-2">
                            <flux:field>
                                <flux:input wire:model.live.debounce.400ms="nominalSukarela" type="number" size="sm" placeholder="Nominal (Rp)" :max="$saldoSukarela" />
                                <flux:error name="nominalSukarela" />
                            </flux:field>
                        </div>
                    @endif
                </div>

                <div class="p-3 rounded-xl border transition-colors {{ $tarikLain ? 'bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800' }}">
                    <flux:checkbox wire:model.live="tarikLain"
                        label="Simpanan Lain-lain — Rp {{ number_format($saldoLain, 0, ',', '.') }}" />
                    @if($tarikLain)
                        <div class="pl-6 mt-2">
                            <flux:field>
                                <flux:input wire:model.live.debounce.400ms="nominalLain" type="number" size="sm" placeholder="Nominal (Rp)" :max="$saldoLain" />
                                <flux:error name="nominalLain" />
                            </flux:field>
                        </div>
                    @endif
                </div>

                <div class="p-3 rounded-xl border transition-colors {{ $tarikShu ? 'bg-purple-50 dark:bg-purple-950/20 border-purple-200 dark:border-purple-900' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800' }}">
                    <flux:checkbox wire:model.live="tarikShu"
                        label="SHU — Rp {{ number_format($saldoShu, 0, ',', '.') }}" />
                    @if($tarikShu)
                        <div class="pl-6 mt-2">
                            <flux:field>
                                <flux:input wire:model.live.debounce.400ms="nominalShu" type="number" size="sm" placeholder="Nominal (Rp)" :max="$saldoShu" />
                                <flux:error name="nominalShu" />
                            </flux:field>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Total --}}
            <div class="flex justify-between items-center p-3 rounded-xl bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Total Penarikan</span>
                <span class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($this->totalNominalTarik, 0, ',', '.') }}</span>
            </div>

            <flux:separator variant="subtle" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>Nama Bank</flux:label>
                    <flux:select wire:model.live="namaBank" placeholder="Pilih bank...">
                        @foreach($this->daftarBank as $bank)
                            <flux:select.option value="{{ $bank->nama_bank }}">{{ $bank->nama_bank }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="namaBank" />
                </flux:field>
                <flux:field>
                    <flux:input wire:model.live="noRekening" label="Nomor Rekening" placeholder="Contoh: 1234567890" />
                    <flux:error name="noRekening" />
                </flux:field>
            </div>
            <flux:field>
                <flux:input wire:model.live="namaPemilik" label="Nama Pemilik Rekening" placeholder="Sesuai buku tabungan" />
                <flux:error name="namaPemilik" />
            </flux:field>
            <flux:textarea wire:model.live="keteranganTarik" label="Keterangan (opsional)" placeholder="Tujuan penarikan..." rows="2" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" :disabled="$this->totalNominalTarik <= 0">Lanjut Konfirmasi</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: Konfirmasi Penarikan --}}
    <flux:modal name="konfirmasi-penarikan" class="md:w-md">
        <div class="flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-full text-blue-500">
                    <flux:icon name="question-mark-circle" class="w-5 h-5" />
                </div>
                <flux:heading size="lg">Konfirmasi Penarikan</flux:heading>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 space-y-2 text-sm">
                <div class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-1">Rincian</div>
                @if($tarikSukarela && $nominalSukarela)
                    <div class="flex justify-between"><span class="text-zinc-500">Simpanan Sukarela</span><span class="font-medium">Rp {{ number_format((int)$nominalSukarela, 0, ',', '.') }}</span></div>
                @endif
                @if($tarikLain && $nominalLain)
                    <div class="flex justify-between"><span class="text-zinc-500">Simpanan Lain-lain</span><span class="font-medium">Rp {{ number_format((int)$nominalLain, 0, ',', '.') }}</span></div>
                @endif
                @if($tarikShu && $nominalShu)
                    <div class="flex justify-between"><span class="text-zinc-500">SHU</span><span class="font-medium">Rp {{ number_format((int)$nominalShu, 0, ',', '.') }}</span></div>
                @endif
                <flux:separator variant="subtle" />
                <div class="flex justify-between font-semibold">
                    <span>Total</span>
                    <span class="text-zinc-900 dark:text-zinc-100">Rp {{ number_format($this->totalNominalTarik, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs text-zinc-400">
                    <span>Rekening</span>
                    <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $namaBank ?: '-' }} – {{ $noRekening ?: '-' }}</span>
                </div>
                <div class="flex justify-between text-xs text-zinc-400">
                    <span>Atas Nama</span>
                    <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $namaPemilik ?: '-' }}</span>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button x-on:click="$flux.modal('ajukan-penarikan').show()" variant="ghost">Kembali</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" wire:click="submitPenarikan">Ya, Ajukan</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Modal: Sukses Penarikan --}}
    <flux:modal name="sukses-penarikan" class="md:w-md">
        <div class="flex flex-col items-center gap-4 text-center py-4">
            <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500">
                <flux:icon name="check-circle" class="w-9 h-9" />
            </div>
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">Permintaan penarikan Anda sedang menunggu proses oleh pengurus koperasi.</flux:text>
            <flux:modal.close><flux:button variant="primary" class="w-full mt-2">Tutup</flux:button></flux:modal.close>
        </div>
    </flux:modal>

</div>

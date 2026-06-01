<?php

use App\Models\KoperasiMember;
use App\Models\Pembiayaan;
use App\Models\Pinjaman;
use App\Models\MutasiSaldoMember;
use App\Models\TagihanPayrollEmployee;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

new #[Layout('layouts::anggota')] class extends Component
{
    public $employeeId;
    public $saldoPokok     = 0;
    public $saldoWajib     = 0;
    public $saldoSukarela  = 0;
    public $saldoLain      = 0;
    public $saldoShu       = 0;
    public $totalSimpanan  = 0;
    public $pinjamanAktifNominal = 0;
    public $sisaPinjamanNominal  = 0;

    public function mount()
    {
        Carbon::setLocale('id');
        $user = auth('web')->user();
        if (!$user || !$user->userable) return;
        $this->employeeId = $user->userable->id;
        $member = KoperasiMember::find($this->employeeId);

        $this->saldoPokok    = $member->saldo_simpanan_pokok;
        $this->saldoWajib    = $member->saldo_simpanan_wajib;
        $this->saldoSukarela = $member->saldo_simpanan_sukarela;
        $this->saldoLain     = $member->saldo_simpanan_lain_lain;
        $this->saldoShu      = $member->saldo_shu;
        $this->totalSimpanan = $this->saldoPokok + $this->saldoWajib + $this->saldoSukarela + $this->saldoLain + $this->saldoShu;

        $pembiayaanList = Pembiayaan::byEmployee()
            ->whereIn('status', ['dicairkan', 'berjalan', 'lunas'])
            ->with('tagihanPayrollEmployee')->get();
        $pinjamanList = Pinjaman::byEmployee()
            ->whereIn('status', ['dicairkan', 'berjalan', 'lunas'])
            ->with('tagihanPayrollEmployee')->get();

        $this->pinjamanAktifNominal =
            $pembiayaanList->sum(fn($i) => $i->nominal_disetujui ?: $i->nominal_pengajuan) +
            $pinjamanList->sum(fn($i) => $i->nominal_disetujui ?: $i->nominal_pengajuan);

        $sisa = 0;
        foreach ([...$pembiayaanList, ...$pinjamanList] as $p) {
            if ($p->status === 'lunas') continue;
            $lunasCount = $p->tagihanPayrollEmployee ? $p->tagihanPayrollEmployee->where('status','lunas')->count() : 0;
            $total = $p->total_pembiayaan ?: ($p->nominal_angsuran * $p->tenor_bulan);
            $sisa += max(0, $total - $lunasCount * $p->nominal_angsuran);
        }
        $this->sisaPinjamanNominal = $sisa;
    }

    #[Computed]
    public function pembiayaanPinjamanAktif()
    {
        if (!$this->employeeId) return collect();

        $pembiayaan = Pembiayaan::byEmployee()
            ->whereIn('status', ['dicairkan', 'berjalan'])
            ->with('tagihanPayrollEmployee')->get()
            ->map(function ($item) {
                $item->tipe = 'pembiayaan';
                $item->keterangan_label = $item->tujuan_pembiayaan;
                $item->nominal_label    = $item->total_pembiayaan ?: $item->nominal_pengajuan;
                return $item;
            });

        $pinjaman = Pinjaman::where('employee_id', $this->employeeId)
            ->whereIn('status', ['dicairkan', 'berjalan'])
            ->with('tagihanPayrollEmployee')->get()
            ->map(function ($item) {
                $item->tipe = 'pinjaman';
                $item->keterangan_label = $item->jenis_pinjaman;
                $item->nominal_label    = $item->nominal_disetujui ?: $item->nominal_pengajuan;
                return $item;
            });

        return $pembiayaan->concat($pinjaman)->sortByDesc('created_at');
    }

    #[Computed]
    public function tagihanBulanIni()
    {
        if (!$this->employeeId) return collect();
        return TagihanPayrollEmployee::where('employee_id', $this->employeeId)
            ->where('periode_payroll_bulan', Carbon::now()->month)
            ->where('periode_payroll_tahun', Carbon::now()->year)
            ->get();
    }

    #[Computed]
    public function totalTagihan()
    {
        return $this->tagihanBulanIni->sum('nominal');
    }

    #[Computed]
    public function transaksiTerbaru()
    {
        if (!$this->employeeId) return collect();
        return MutasiSaldoMember::where('employee_id', $this->employeeId)
            ->latest('id')->limit(5)->get();
    }
};
?>

<div class="space-y-6 pb-2">
<!-- Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-500 to-indigo-600 dark:from-blue-700 dark:to-indigo-700 p-5 sm:p-7 text-white shadow-lg">
        <div class="absolute -top-12 -right-12 w-52 h-52 rounded-full bg-white/10 pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-10 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>

        <div class="relative z-10">
            {{-- Greeting --}}
            <div class="text-sm font-medium opacity-75 mb-0.5">
                Selamat datang, <span class="font-semibold">{{ auth()->user()->userable->nama_lengkap ?? 'Anggota' }}</span>
            </div>
            <div class="text-xs opacity-60 mb-4">{{ Carbon::now()->translatedFormat('l, d F Y') }}</div>

            {{-- Total Simpanan --}}
            <div class="text-[11px] font-semibold uppercase tracking-widest opacity-75 mb-0.5">Total Simpanan</div>
            <div class="text-3xl sm:text-4xl font-bold tracking-tight">
                Rp {{ number_format($totalSimpanan, 0, ',', '.') }}
            </div>

            {{-- Simpanan breakdown chips --}}
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach([
                    ['Pokok',    $saldoPokok],
                    ['Wajib',    $saldoWajib],
                    ['Sukarela', $saldoSukarela],
                    ['Lain-lain',$saldoLain],
                    ['SHU',      $saldoShu],
                ] as [$label, $val])
                    <div class="bg-white/15 rounded-lg px-3 py-1.5 text-xs font-medium">
                        <span class="opacity-75">{{ $label }}</span>
                        <span class="ml-1 font-bold">Rp {{ number_format($val, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         PINJAMAN AKTIF + SISA — 2 mini stat cards
    ════════════════════════════════════════════════ --}}
    <div class="grid md:grid-cols-2 gap-3 sm:gap-4">
        <div class="flex items-center gap-3 p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center shrink-0">
                <flux:icon name="credit-card" class="w-5 h-5 text-rose-500 dark:text-rose-400" />
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-semibold text-zinc-400 uppercase tracking-wide truncate">Plafond Aktif</div>
                <div class="text-sm sm:text-lg font-bold text-zinc-800 dark:text-zinc-200 truncate">
                    Rp {{ number_format($pinjamanAktifNominal, 0, ',', '.') }}
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3 p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center shrink-0">
                <flux:icon name="clock" class="w-5 h-5 text-orange-500 dark:text-orange-400" />
            </div>
            <div class="min-w-0">
                <div class="text-[10px] sm:text-xs font-semibold text-zinc-400 uppercase tracking-wide truncate">Sisa Tagihan</div>
                <div class="text-sm sm:text-lg font-bold text-orange-600 dark:text-orange-400 truncate">
                    Rp {{ number_format($sisaPinjamanNominal, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         AKSI CEPAT
    ════════════════════════════════════════════════ --}}
    <div>
        <div class="text-xs font-semibold text-zinc-400 uppercase tracking-widest mb-3">Aksi Cepat</div>
        <div class="grid grid-cols-5 gap-2 sm:gap-3">
            @foreach([
                ['href' => '/anggota/dompet', 'icon' => 'arrow-down-tray',   'label' => 'Tarik',   'bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                ['href' => '/anggota/pembiayaan-pinjaman/pengajuan', 'icon' => 'plus-circle', 'label' => 'Ajukan', 'bg' => 'bg-blue-50 dark:bg-blue-950/40', 'text' => 'text-blue-600 dark:text-blue-400'],
                ['href' => '/anggota/ppob',  'icon' => 'bolt',              'label' => 'PPOB',    'bg' => 'bg-orange-50 dark:bg-orange-950/40', 'text' => 'text-orange-500 dark:text-orange-400'],
                ['href' => '/anggota/lazis', 'icon' => 'heart',             'label' => 'Lazis',   'bg' => 'bg-rose-50 dark:bg-rose-950/40', 'text' => 'text-rose-500 dark:text-rose-400'],
                ['href' => '/anggota/dompet','icon' => 'arrow-path',        'label' => 'Mutasi',  'bg' => 'bg-purple-50 dark:bg-purple-950/40', 'text' => 'text-purple-600 dark:text-purple-400'],
            ] as $action)
                <a href="{{ $action['href'] }}" wire:navigate
                   class="group flex flex-col items-center gap-2 p-2.5 sm:p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl hover:border-zinc-300 hover:shadow-sm transition-all text-center">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl {{ $action['bg'] }} {{ $action['text'] }} flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon name="{{ $action['icon'] }}" class="w-5 h-5" />
                    </div>
                    <span class="text-[10px] sm:text-xs font-semibold text-zinc-600 dark:text-zinc-400 leading-tight">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         SECTION 3 + 4: dua kolom di desktop, stack di mobile
    ════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 sm:gap-5">

        {{-- PINJAMAN AKTIF — 3 kolom (lebih lebar) --}}
        <div class="lg:col-span-3">
            <flux:card class="flex flex-col h-full">
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <flux:heading size="base" class="font-semibold text-zinc-800 dark:text-zinc-200">Pinjaman & Pembiayaan Aktif</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-0.5">Daftar pinjaman yang sedang berjalan</flux:text>
                    </div>
                    <flux:button size="sm" href="/anggota/pembiayaan-pinjaman" wire:navigate variant="ghost" icon="arrow-right">Semua</flux:button>
                </div>
                <flux:separator variant="subtle" class="mb-3" />

                @forelse($this->pembiayaanPinjamanAktif as $row)
                    @php
                        $lunasCount = $row->tagihanPayrollEmployee ? $row->tagihanPayrollEmployee->where('status','lunas')->count() : 0;
                        $sisaTenor  = max(0, (int)$row->tenor_bulan - $lunasCount);
                        $pct = $row->tenor_bulan > 0 ? round($lunasCount / $row->tenor_bulan * 100) : 0;
                    @endphp
                    <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800 mb-2">
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($row->tipe === 'pembiayaan')
                                        <flux:badge color="blue" size="sm">Pembiayaan</flux:badge>
                                    @else
                                        <flux:badge color="purple" size="sm">Pinjaman</flux:badge>
                                    @endif
                                    <span class="text-xs text-zinc-400 font-mono">{{ $row->nomor_pengajuan }}</span>
                                </div>
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-1 truncate">{{ $row->keterangan_label }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_label, 0, ',', '.') }}</div>
                                <div class="text-xs text-zinc-400 mt-0.5">Rp {{ number_format($row->nominal_angsuran, 0, ',', '.') }}/bln</div>
                            </div>
                        </div>
                        {{-- Progress bar --}}
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-[10px] font-semibold text-zinc-400 shrink-0">{{ $sisaTenor }} bln tersisa</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-8">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Tidak ada pinjaman aktif.</p>
                    </div>
                @endforelse
            </flux:card>
        </div>

        {{-- TAGIHAN BULAN INI — 2 kolom (lebih sempit) --}}
        <div class="lg:col-span-2">
            <flux:card class="flex flex-col h-full">
                <div class="mb-3">
                    <flux:heading size="base" class="font-semibold text-zinc-800 dark:text-zinc-200">Tagihan Bulan Ini</flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-0.5">{{ Carbon::now()->translatedFormat('F Y') }}</flux:text>
                </div>

                {{-- Total pill --}}
                <div class="flex items-center justify-between p-3 rounded-xl bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 mb-3">
                    <span class="text-xs font-semibold text-red-700 dark:text-red-400">Total Potongan</span>
                    <span class="text-base font-extrabold text-red-600 dark:text-red-400">
                        Rp {{ number_format($this->totalTagihan, 0, ',', '.') }}
                    </span>
                </div>

                <flux:separator variant="subtle" class="mb-3" />

                {{-- List tagihan --}}
                <div class="space-y-2">
                    @forelse($this->tagihanBulanIni as $t)
                        @php
                            $label = match($t->jenis_tagihan) {
                                'simpanan_pokok'   => 'Simpanan Pokok',
                                'simpanan_wajib'   => 'Simpanan Wajib',
                                'simpanan_sukarela'=> 'Simpanan Sukarela',
                                'lazis'            => 'Donasi LAZIS',
                                'ppob'             => 'Tagihan PPOB',
                                'pinjaman'         => 'Cicilan Pinjaman',
                                'pembiayaan'       => 'Cicilan Pembiayaan',
                                default            => ucfirst(str_replace('_',' ',$t->jenis_tagihan)),
                            };
                        @endphp
                        <div class="flex items-center justify-between gap-2 py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate">{{ $label }}</div>
                                @if($t->keterangan)
                                    <div class="text-[10px] text-zinc-400 truncate">{{ $t->keterangan }}</div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-sm font-bold text-red-600 dark:text-red-400">
                                    Rp {{ number_format($t->nominal, 0, ',', '.') }}
                                </span>
                                @if($t->status === 'lunas')
                                    <flux:badge color="green" size="sm">Lunas</flux:badge>
                                @elseif($t->status === 'masuk_payroll')
                                    <flux:badge color="blue" size="sm">Proses</flux:badge>
                                @else
                                    <flux:badge color="orange" size="sm">Pending</flux:badge>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-400 py-6">
                            <flux:icon name="check-circle" class="w-8 h-8 mx-auto mb-1.5 opacity-30" />
                            <p class="text-xs">Tidak ada tagihan bulan ini.</p>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         TRANSAKSI TERBARU
    ════════════════════════════════════════════════ --}}
    <flux:card class="flex flex-col">
        <div class="flex justify-between items-center mb-3">
            <div>
                <flux:heading size="base" class="font-semibold text-zinc-800 dark:text-zinc-200">Transaksi Terbaru</flux:heading>
                <flux:text class="text-xs text-zinc-400 mt-0.5">5 mutasi saldo terakhir</flux:text>
            </div>
            <flux:button size="sm" href="/anggota/dompet" wire:navigate variant="ghost" icon="arrow-right">Lihat Semua</flux:button>
        </div>
        <flux:separator variant="subtle" class="mb-3" />

        {{-- Mobile card list --}}
        <div class="sm:hidden space-y-2">
            @forelse($this->transaksiTerbaru as $trx)
                <div class="flex items-center gap-3 py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0
                        {{ $trx->jenis_mutasi === 'kredit' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400' }}">
                        <flux:icon name="{{ $trx->jenis_mutasi === 'kredit' ? 'arrow-down' : 'arrow-up' }}" class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate">
                                {{ $trx->keterangan ?: ucfirst(str_replace('_',' ',$trx->sumber_transaksi)) }}
                            </span>
                            <span class="text-sm font-bold shrink-0 {{ $trx->jenis_mutasi === 'kredit' ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                {{ $trx->jenis_mutasi === 'kredit' ? '+' : '-' }} Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-[10px] text-zinc-400">{{ $trx->created_at->format('d/m/Y') }}</span>
                            @php
                                $badgeColor = match($trx->jenis_saldo) {
                                    'simpanan_pokok'   => 'blue',
                                    'simpanan_wajib'   => 'purple',
                                    'simpanan_sukarela'=> 'green',
                                    'shu'              => 'teal',
                                    default            => 'zinc',
                                };
                                $badgeLabel = match($trx->jenis_saldo) {
                                    'simpanan_pokok'   => 'Pokok',
                                    'simpanan_wajib'   => 'Wajib',
                                    'simpanan_sukarela'=> 'Sukarela',
                                    'shu'              => 'SHU',
                                    default            => 'Lainnya',
                                };
                            @endphp
                            <flux:badge color="{{ $badgeColor }}" size="sm">{{ $badgeLabel }}</flux:badge>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-zinc-400 py-8">
                    <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                    <p class="text-sm">Belum ada transaksi.</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop table --}}
        <div class="hidden sm:block overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Keterangan</flux:table.column>
                    <flux:table.column>Jenis Saldo</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->transaksiTerbaru as $trx)
                        <flux:table.row :key="'trx-'.$trx->id">
                            <flux:table.cell class="text-zinc-400 text-sm">{{ $trx->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="block font-medium text-zinc-800 dark:text-zinc-200">{{ $trx->keterangan ?: '-' }}</span>
                                <span class="text-[10px] text-zinc-400">{{ ucfirst(str_replace('_',' ',$trx->sumber_transaksi)) }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $color = match($trx->jenis_saldo) { 'simpanan_pokok'=>'blue','simpanan_wajib'=>'purple','simpanan_sukarela'=>'green','shu'=>'teal',default=>'zinc' };
                                    $lbl   = match($trx->jenis_saldo) { 'simpanan_pokok'=>'Pokok','simpanan_wajib'=>'Wajib','simpanan_sukarela'=>'Sukarela','shu'=>'SHU',default=>'Lainnya' };
                                @endphp
                                <flux:badge color="{{ $color }}" size="sm" inset="top bottom">{{ $lbl }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-semibold {{ $trx->jenis_mutasi === 'kredit' ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                {{ $trx->jenis_mutasi === 'kredit' ? '+' : '-' }} Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-400 py-8">Belum ada transaksi.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

</div>
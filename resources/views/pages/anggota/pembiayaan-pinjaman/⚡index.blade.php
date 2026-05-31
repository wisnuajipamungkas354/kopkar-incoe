<?php

use App\Models\Pembiayaan;
use App\Models\Pinjaman;
use App\Models\TagihanPayrollEmployee;
use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('layouts::anggota', ['title' => 'Pembiayaan dan Pinjaman'])] class extends Component
{
    use WithPagination;

    public $activeTab = 'aktif';
    public $search = '';
    public $employeeId;

    // Summary stats
    public $totalPlafond = 0;
    public $sisaTagihan  = 0;
    public $jumlahAktif  = 0;
    public $jumlahPengajuan = 0;
    public $selectedCancelId = null;
    public $selectedCancelTipe = null; // pembiayaan | pinjaman

    public function mount()
    {
        $user = auth('web')->user();
        $this->employeeId = $user->userable->id;
        $this->calculateStats();
    }

    public function calculateStats()
    {
        $pembiayaanAktif = Pembiayaan::byEmployee()
            ->whereIn('status', ['dicairkan', 'berjalan'])
            ->with('tagihanPayrollEmployee')->get();
        $pinjamanAktif = Pinjaman::byEmployee()
            ->whereIn('status', ['dicairkan', 'berjalan'])
            ->with('tagihanPayrollEmployee')->get();

        $this->jumlahAktif = $pembiayaanAktif->count() + $pinjamanAktif->count();

        $this->totalPlafond =
            $pembiayaanAktif->sum(fn($i) => $i->nominal_disetujui ?: $i->nominal_pengajuan) +
            $pinjamanAktif->sum(fn($i) => $i->nominal_disetujui ?: $i->nominal_pengajuan);

        $sisa = 0;
        foreach ([...$pembiayaanAktif, ...$pinjamanAktif] as $p) {
            $lunasCount = $p->tagihanPayrollEmployee ? $p->tagihanPayrollEmployee->where('status', 'lunas')->count() : 0;
            $total = $p->total_pembiayaan ?: ($p->nominal_angsuran * $p->tenor_bulan);
            $sisa += max(0, $total - $lunasCount * ($p->nominal_angsuran ?? 0));
        }
        $this->sisaTagihan = $sisa;

        $this->jumlahPengajuan = Pembiayaan::where('employee_id', $this->employeeId)
            ->whereIn('status', ['diajukan', 'diproses'])->count()
            + Pinjaman::where('employee_id', $this->employeeId)
            ->whereIn('status', ['diajukan', 'diproses'])->count();
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

    #[Computed]
    public function daftarAktif()
    {
        $pembiayaan = Pembiayaan::byEmployee()
            ->whereIn('status', ['dicairkan', 'berjalan', 'lunas'])
            ->with('tagihanPayrollEmployee')->get()
            ->map(function ($item) {
                $item->tipe = 'pembiayaan';
                $item->jenis_label = 'Pembiayaan';
                $item->keterangan_label = $item->tujuan_pembiayaan;
                $item->nominal_label = $item->total_pembiayaan ?: $item->nominal_pengajuan;
                return $item;
            });

        $pinjaman = Pinjaman::byEmployee()
            ->whereIn('status', ['dicairkan', 'berjalan', 'lunas'])
            ->with('tagihanPayrollEmployee')->get()
            ->map(function ($item) {
                $item->tipe = 'pinjaman';
                $item->jenis_label = 'Pinjaman (' . $item->jenis_pinjaman . ')';
                $item->keterangan_label = 'Pinjaman Uang';
                $item->nominal_label = $item->nominal_disetujui ?: $item->nominal_pengajuan;
                return $item;
            });

        $merged = $pembiayaan->concat($pinjaman);

        if (!empty($this->search)) {
            $merged = $merged->filter(fn($item) =>
                stripos($item->nomor_pengajuan, $this->search) !== false ||
                stripos($item->keterangan_label, $this->search) !== false ||
                stripos($item->jenis_label, $this->search) !== false
            );
        }

        return $merged->sortByDesc('created_at');
    }

    #[Computed]
    public function daftarPengajuan()
    {
        $pembiayaan = Pembiayaan::where('employee_id', $this->employeeId)
            ->whereIn('status', ['diajukan', 'diproses', 'ditolak', 'dibatalkan'])
            ->get()
            ->map(function ($item) {
                $item->tipe = 'pembiayaan';
                $item->jenis_label = 'Pembiayaan';
                $item->keterangan_label = $item->tujuan_pembiayaan;
                $item->nominal_label = $item->nominal_pengajuan;
                return $item;
            });

        $pinjaman = Pinjaman::where('employee_id', $this->employeeId)
            ->whereIn('status', ['diajukan', 'diproses', 'ditolak', 'dibatalkan'])
            ->get()
            ->map(function ($item) {
                $item->tipe = 'pinjaman';
                $item->jenis_label = 'Pinjaman (' . $item->jenis_pinjaman . ')';
                $item->keterangan_label = 'Pinjaman Uang';
                $item->nominal_label = $item->nominal_pengajuan;
                return $item;
            });

        $merged = $pembiayaan->concat($pinjaman);

        if (!empty($this->search)) {
            $merged = $merged->filter(fn($item) =>
                stripos($item->nomor_pengajuan, $this->search) !== false ||
                stripos($item->keterangan_label, $this->search) !== false ||
                stripos($item->jenis_label, $this->search) !== false
            );
        }

        return $merged->sortByDesc('created_at');
    }

    public function confirmCancel($id, $tipe)
    {
        $this->selectedCancelId = $id;
        $this->selectedCancelTipe = $tipe;
        Flux::modal('konfirmasi-batal-pengajuan')->show();
    }

    public function cancelPengajuan()
    {
        if (!$this->selectedCancelId || !$this->selectedCancelTipe) return;

        if ($this->selectedCancelTipe === 'pembiayaan') {
            $pengajuan = Pembiayaan::where('id', $this->selectedCancelId)
                ->where('employee_id', $this->employeeId)
                ->where('status', 'diajukan') // hanya bisa batalkan jika masih diajukan
                ->first();
        } else {
            $pengajuan = Pinjaman::where('id', $this->selectedCancelId)
                ->where('employee_id', $this->employeeId)
                ->where('status', 'diajukan') // hanya bisa batalkan jika masih diajukan
                ->first();
        }

        if ($pengajuan) {
            DB::transaction(function () use ($pengajuan) {
                $pengajuan->update([
                    'status'           => 'dibatalkan',
                    'dibatalkan_oleh'  => auth('web')->user()->id,
                    'dibatalkan_pada'  => now(),
                ]);
            });
            Flux::toast(heading: 'Pengajuan Dibatalkan', text: 'Pengajuan ' . ($this->selectedCancelTipe === 'pembiayaan' ? 'pembiayaan' : 'pinjaman') . ' berhasil dibatalkan.', variant: 'success');
            $this->calculateStats();
        } else {
            Flux::toast(heading: 'Gagal', text: 'Pengajuan tidak ditemukan atau sudah diproses oleh staff dan tidak dapat dibatalkan.', variant: 'danger');
        }

        $this->selectedCancelId = null;
        $this->selectedCancelTipe = null;
        Flux::modal('konfirmasi-batal-pengajuan')->close();
    }
};
?>

<div class="space-y-5">

    {{-- ════════════════════════════════════════════════
         HERO BANNER
    ════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-blue-600 to-blue-700 dark:from-indigo-700 dark:to-blue-800 p-5 sm:p-7 text-white shadow-lg">
        <div class="absolute -top-12 -right-12 w-52 h-52 rounded-full bg-white/10 pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-10 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>

        <div class="relative z-10">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <flux:icon name="banknotes" class="w-5 h-5 opacity-75" />
                        <span class="text-sm font-medium opacity-75">Pembiayaan & Pinjaman</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-bold tracking-tight">
                        Rp {{ number_format($totalPlafond, 0, ',', '.') }}
                    </div>
                    <div class="text-xs opacity-60 mt-0.5">Sisa Plafond Aktif</div>
                </div>
                <a href="/anggota/pembiayaan-pinjaman/pengajuan" wire:navigate
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl text-sm font-semibold transition-all self-start">
                    <flux:icon name="plus" class="w-4 h-4" />
                    Ajukan Baru
                </a>
            </div>

            {{-- Stats row --}}
            <div class="flex flex-wrap gap-3 mt-5">
                <div class="bg-white/15 rounded-xl px-4 py-2.5 min-w-[120px]">
                    <div class="text-[10px] font-semibold opacity-70 uppercase tracking-wider">Aktif</div>
                    <div class="text-lg font-bold mt-0.5">{{ $jumlahAktif }} <span class="text-sm font-medium opacity-75">fasilitas</span></div>
                </div>
                <div class="bg-white/15 rounded-xl px-4 py-2.5 min-w-[120px]">
                    <div class="text-[10px] font-semibold opacity-70 uppercase tracking-wider">Sisa Tagihan</div>
                    <div class="text-lg font-bold mt-0.5">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</div>
                </div>
                <div class="bg-white/15 rounded-xl px-4 py-2.5 min-w-[120px]">
                    <div class="text-[10px] font-semibold opacity-70 uppercase tracking-wider">Dalam Proses</div>
                    <div class="text-lg font-bold mt-0.5">{{ $jumlahPengajuan }} <span class="text-sm font-medium opacity-75">pengajuan</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════
         TAB NAVIGATION
    ════════════════════════════════════════════════ --}}
    <div class="flex border-b border-zinc-200 dark:border-zinc-700 gap-1 overflow-x-auto">
        @php
            $tabs = [
                'aktif'     => 'Pinjaman Aktif',
                'pengajuan' => 'Riwayat Pengajuan',
            ];
        @endphp
        @foreach($tabs as $key => $label)
            <button wire:click="switchTab('{{ $key }}')"
                    class="shrink-0 pb-3 px-1 text-sm font-semibold border-b-2 cursor-pointer transition-all
                           {{ $activeTab === $key
                              ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                              : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                {{ $label }}
                @if($key === 'pengajuan' && $jumlahPengajuan > 0)
                    <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-400 text-white text-[10px] font-bold">{{ $jumlahPengajuan }}</span>
                @endif
            </button>
        @endforeach
    </div>
    {{-- ════════════════════════════════════════════════
         TAB CONTENT
    ════════════════════════════════════════════════ --}}
    <flux:card class="flex flex-col">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center mb-4">
            <flux:heading size="lg">{{ $tabs[$activeTab] }}</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-52" placeholder="Cari..." icon="magnifying-glass" />
        </div>
        <flux:separator variant="subtle" class="mb-3" />

        {{-- ── TAB: PINJAMAN AKTIF ─────────────────── --}}
        @if($activeTab === 'aktif')
            <div class="animate-fade-in-up">
                {{-- Mobile card list --}}
            <div class="sm:hidden space-y-2">
                @forelse($this->daftarAktif as $row)
                    @php
                        $lunasCount = $row->tagihanPayrollEmployee ? $row->tagihanPayrollEmployee->where('status','lunas')->count() : 0;
                        $sisaTenor  = max(0, (int)$row->tenor_bulan - $lunasCount);
                        $pct        = $row->tenor_bulan > 0 ? round($lunasCount / $row->tenor_bulan * 100) : 0;
                    @endphp
                    <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($row->tipe === 'pembiayaan')
                                        <flux:badge color="blue" size="sm">Pembiayaan</flux:badge>
                                    @else
                                        <flux:badge color="purple" size="sm">Pinjaman</flux:badge>
                                    @endif
                                    @if($row->status === 'lunas')
                                        <flux:badge color="green" size="sm">Lunas</flux:badge>
                                    @elseif($row->status === 'berjalan')
                                        <flux:badge color="emerald" size="sm">Berjalan</flux:badge>
                                    @else
                                        <flux:badge color="blue" size="sm">Dicairkan</flux:badge>
                                    @endif
                                </div>
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-1 truncate">{{ $row->keterangan_label }}</div>
                                <div class="text-[10px] text-zinc-400 font-mono">{{ $row->nomor_pengajuan }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_label, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-zinc-400">Rp {{ number_format($row->nominal_angsuran, 0, ',', '.') }}/bln</div>
                            </div>
                        </div>
                        {{-- Progress bar --}}
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-[10px] font-semibold text-zinc-400 shrink-0">{{ $sisaTenor }}/{{ $row->tenor_bulan }} bln</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-10">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Tidak ada pinjaman atau pembiayaan aktif.</p>
                    </div>
                @endforelse
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>No. Pengajuan</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Keterangan</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                        <flux:table.column>Cicilan/Bln</flux:table.column>
                        <flux:table.column>Progress</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->daftarAktif as $row)
                            @php
                                $lunasCount = $row->tagihanPayrollEmployee ? $row->tagihanPayrollEmployee->where('status','lunas')->count() : 0;
                                $sisaTenor  = max(0, (int)$row->tenor_bulan - $lunasCount);
                                $pct        = $row->tenor_bulan > 0 ? round($lunasCount / $row->tenor_bulan * 100) : 0;
                            @endphp
                            <flux:table.row :key="$row->tipe.'-'.$row->id">
                                <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->nomor_pengajuan }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->tipe === 'pembiayaan')
                                        <flux:badge color="blue" size="sm" inset="top bottom">Pembiayaan</flux:badge>
                                    @else
                                        <flux:badge color="purple" size="sm" inset="top bottom">Pinjaman</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-medium text-zinc-700 dark:text-zinc-300 max-w-xs truncate">{{ $row->keterangan_label }}</flux:table.cell>
                                <flux:table.cell class="font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_label, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500 text-sm">Rp {{ number_format($row->nominal_angsuran, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2 min-w-[120px]">
                                        <div class="flex-1 h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-[10px] text-zinc-400 font-semibold shrink-0">{{ $sisaTenor }}/{{ $row->tenor_bulan }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'dicairkan')
                                        <flux:badge color="blue" size="sm" inset="top bottom">Dicairkan</flux:badge>
                                    @elseif($row->status === 'berjalan')
                                        <flux:badge color="emerald" size="sm" inset="top bottom">Berjalan</flux:badge>
                                    @elseif($row->status === 'lunas')
                                        <flux:badge color="green" size="sm" inset="top bottom">Lunas</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ ucfirst($row->status) }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="text-center text-zinc-400 py-8">Tidak ada data pembiayaan atau pinjaman aktif.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            </div>

        {{-- ── TAB: RIWAYAT PENGAJUAN ──────────────── --}}
        @elseif($activeTab === 'pengajuan')
            <div class="animate-fade-in-up">
            {{-- Mobile card list --}}
            <div class="sm:hidden space-y-2">
                @forelse($this->daftarPengajuan as $row)
                    <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                        <div class="flex justify-between items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($row->tipe === 'pembiayaan')
                                        <flux:badge color="blue" size="sm">Pembiayaan</flux:badge>
                                    @else
                                        <flux:badge color="purple" size="sm">Pinjaman</flux:badge>
                                    @endif
                                    @if($row->status === 'diajukan')
                                        <flux:badge color="orange" size="sm">Menunggu</flux:badge>
                                    @elseif($row->status === 'diproses')
                                        <flux:badge color="sky" size="sm">Diproses</flux:badge>
                                    @elseif($row->status === 'ditolak')
                                        <flux:badge color="red" size="sm">Ditolak</flux:badge>
                                    @elseif($row->status === 'dibatalkan')
                                        <flux:badge color="zinc" size="sm">Dibatalkan</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ ucfirst($row->status) }}</flux:badge>
                                    @endif
                                </div>
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mt-1 truncate">{{ $row->keterangan_label }}</div>
                                <div class="text-[10px] text-zinc-400 font-mono">{{ $row->nomor_pengajuan }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_label, 0, ',', '.') }}</div>
                                <div class="text-[10px] text-zinc-400">{{ $row->tenor_bulan }} bulan</div>
                            </div>
                        </div>
                        @if($row->status === 'ditolak' && $row->alasan_penolakan)
                            <div class="mt-2 p-2 bg-red-50 dark:bg-red-950/20 rounded-lg border border-red-100 dark:border-red-900/30">
                                <div class="text-[10px] font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider">Alasan Penolakan</div>
                                <div class="text-xs text-red-500 mt-0.5">{{ $row->alasan_penolakan }}</div>
                            </div>
                        @endif
                        <div class="flex flex-row justify-between mt-1.5">
                            <flux:text size="xs">Diajukan: {{ $row->created_at ? $row->created_at->format('d/m/Y') : '-' }}</flux:text>
                            @if($row->status === 'diajukan')
                                <flux:button size="xs" variant="primary" color="red" wire:click="confirmCancel({{ $row->id }}, '{{ $row->tipe }}')" class="font-semibold cursor-pointer">Batalkan</flux:button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-10">
                        <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                        <p class="text-sm">Belum ada riwayat pengajuan.</p>
                    </div>
                @endforelse
            </div>

            {{-- Desktop table --}}
            <div class="hidden sm:block overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>No. Pengajuan</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Keterangan</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                        <flux:table.column>Tenor</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->daftarPengajuan as $row)
                            <flux:table.row :key="$row->tipe.'-'.$row->id">
                                <flux:table.cell class="text-zinc-400 text-sm">{{ $row->created_at ? $row->created_at->format('d/m/Y') : '-' }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->nomor_pengajuan }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->tipe === 'pembiayaan')
                                        <flux:badge color="blue" size="sm" inset="top bottom">Pembiayaan</flux:badge>
                                    @else
                                        <flux:badge color="purple" size="sm" inset="top bottom">Pinjaman</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-medium text-zinc-700 dark:text-zinc-300 max-w-xs truncate">{{ $row->keterangan_label }}</flux:table.cell>
                                <flux:table.cell class="font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_label, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500">{{ $row->tenor_bulan }} Bulan</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'diajukan')
                                        <flux:badge color="orange" size="sm" inset="top bottom">Menunggu Diproses</flux:badge>
                                    @elseif($row->status === 'diproses')
                                        <flux:badge color="sky" size="sm" inset="top bottom">Sedang Diproses</flux:badge>
                                    @elseif($row->status === 'ditolak')
                                        <div>
                                            <flux:badge color="red" size="sm" inset="top bottom">Ditolak</flux:badge>
                                            @if($row->alasan_penolakan)
                                                <div class="text-[10px] text-red-500 mt-0.5 max-w-[200px] truncate">{{ $row->alasan_penolakan }}</div>
                                            @endif
                                        </div>
                                    @elseif($row->status === 'dibatalkan')
                                        <flux:badge color="zinc" size="sm" inset="top bottom">Dibatalkan</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ ucfirst($row->status) }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'diajukan')
                                        <flux:button wire:click="confirmCancel({{ $row->id }}, '{{ $row->tipe }}')" size="xs" variant="danger">Batalkan</flux:button>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="7" class="text-center text-zinc-400 py-8">Belum ada riwayat pengajuan.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
            </div>
        @endif
    </flux:card>

    {{-- Modal: Konfirmasi Batal Pengajuan --}}
    <flux:modal name="konfirmasi-batal-pengajuan" class="md:w-md space-y-5">
        <div class="flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/40 rounded-full text-red-500">
                    <flux:icon name="exclamation-triangle" class="w-5 h-5" />
                </div>
                <flux:heading size="lg">Batalkan Pengajuan</flux:heading>
            </div>
            <flux:text size="sm" class="text-zinc-500">Apakah Anda yakin ingin membatalkan pengajuan {{ $selectedCancelTipe === 'pembiayaan' ? 'pembiayaan' : 'pinjaman' }} ini?</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Kembali</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" color="red" wire:click="cancelPengajuan">Ya, Batalkan</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
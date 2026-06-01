<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\PengaturanPpobEmployee;
use App\Models\TagihanPayrollEmployee;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Pembayaran PPOB'])] class extends Component
{
    // ── Filter ─────────────────────────────────────────────────
    public int $bulan;
    public int $tahun;
    public string $search = '';
    public string $filterStatus = 'semua'; // semua | belum | sudah

    // ── Form Bayar (per row) ────────────────────────────────────
    public ?int $payingId = null;        // pengaturan_ppob_employee.id
    public string $nominalBayar = '';
    public string $keteranganBayar = '';

    // ── Bulk ────────────────────────────────────────────────────
    public array $selectedIds  = [];
    public bool  $selectAll    = false;
    public string $bulkNominal = '';

    // ── Add PPOB Setting ────────────────────────────────────────
    public string $addEmployeeSearch = '';
    public ?int   $addEmployeeId     = null;
    public ?object $addSelectedEmployee = null;
    public string $addKategori        = 'listrik';
    public string $addNomorPelanggan  = '';
    public string $addCatatan         = '';

    public function mount(): void
    {
        $this->bulan = (int) now()->format('m');
        $this->tahun = (int) now()->format('Y');
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function labelKategori(string $k): string
    {
        return match ($k) {
            'listrik'  => 'Listrik (PLN)',
            'pdam'     => 'Air (PDAM)',
            'internet' => 'Internet / WiFi',
            'bpjs'     => 'BPJS Kesehatan',
            'telepon'  => 'Telepon / Pulsa',
            'tv'       => 'TV Kabel / Streaming',
            default    => 'Lain-lain',
        };
    }

    public function ikonKategori(string $k): string
    {
        return match ($k) {
            'listrik'  => 'bolt',
            'pdam'     => 'beaker',
            'internet' => 'wifi',
            'bpjs'     => 'heart',
            'telepon'  => 'phone',
            'tv'       => 'tv',
            default    => 'document-text',
        };
    }

    public function warnaBadge(string $k): string
    {
        return match ($k) {
            'listrik'  => 'yellow',
            'pdam'     => 'cyan',
            'internet' => 'indigo',
            'bpjs'     => 'green',
            'telepon'  => 'purple',
            'tv'       => 'orange',
            default    => 'zinc',
        };
    }

    // ── Computed ─────────────────────────────────────────────────

    #[Computed]
    public function daftarPpob()
    {
        $bulan = $this->bulan;
        $tahun = $this->tahun;

        $query = PengaturanPpobEmployee::with([
            'employee',
            'tagihanPayrollEmployee' => function ($q) use ($bulan, $tahun) {
                $q->where('jenis_tagihan', 'ppob')
                  ->where('periode_bulan', $bulan)
                  ->where('periode_tahun', $tahun);
            },
        ])->where('aktif', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_pelanggan', 'like', '%' . $this->search . '%')
                  ->orWhere('kategori_ppob', 'like', '%' . $this->search . '%')
                  ->orWhereHas('employee', function ($eq) {
                      $eq->where('nama_lengkap', 'like', '%' . $this->search . '%')
                         ->orWhere('npk', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $rows = $query->orderBy('kategori_ppob')->get();

        // Filter status
        if ($this->filterStatus === 'sudah') {
            $rows = $rows->filter(fn($r) => $r->tagihanPayrollEmployee !== null);
        } elseif ($this->filterStatus === 'belum') {
            $rows = $rows->filter(fn($r) => $r->tagihanPayrollEmployee === null);
        }

        return $rows;
    }

    #[Computed]
    public function statsBulanIni()
    {
        $all   = $this->daftarPpob;
        $sudah = $all->filter(fn($r) => $r->tagihanPayrollEmployee !== null);
        $total = $sudah->sum(fn($r) => $r->tagihanPayrollEmployee->nominal);

        return [
            'total_tagihan' => $all->count(),
            'sudah_bayar'   => $sudah->count(),
            'belum_bayar'   => $all->count() - $sudah->count(),
            'total_nominal' => $total,
        ];
    }

    #[Computed]
    public function availableEmployees()
    {
        if (!$this->addEmployeeSearch || str_contains($this->addEmployeeSearch, ' - ')) {
            return collect();
        }
        return Employee::where(fn($q) => $q
            ->where('npk', 'like', '%' . $this->addEmployeeSearch . '%')
            ->orWhere('nama_lengkap', 'like', '%' . $this->addEmployeeSearch . '%')
        )->orderBy('nama_lengkap')->take(20)->get();
    }

    // ── Actions ──────────────────────────────────────────────────

    public function openBayar(int $id): void
    {
        $this->payingId       = $id;
        $this->nominalBayar   = '';
        $this->keteranganBayar = '';
        Flux::modal('modal-bayar')->show();
    }

    public function simpanBayar(): void
    {
        $this->validate([
            'nominalBayar' => 'required|numeric|min:1',
        ], [
            'nominalBayar.required' => 'Nominal pembayaran wajib diisi.',
            'nominalBayar.min'      => 'Nominal minimal Rp 1.',
        ]);

        $ppob = PengaturanPpobEmployee::findOrFail($this->payingId);

        // Cek sudah ada tagihan bulan ini?
        $existing = TagihanPayrollEmployee::where('jenis_tagihan', 'ppob')
            ->where('tagihanable_type', PengaturanPpobEmployee::class)
            ->where('tagihanable_id', $ppob->id)
            ->where('periode_bulan', $this->bulan)
            ->where('periode_tahun', $this->tahun)
            ->first();

        if ($existing) {
            Flux::toast(heading: 'Sudah Ada', text: 'Tagihan PPOB ini sudah dibayar bulan ini.', variant: 'warning');
            Flux::modal('modal-bayar')->close();
            return;
        }

        TagihanPayrollEmployee::create([
            'employee_id'           => $ppob->employee_id,
            'jenis_tagihan'         => 'ppob',
            'tagihanable_type'      => PengaturanPpobEmployee::class,
            'tagihanable_id'        => $ppob->id,
            'periode_bulan'         => $this->bulan,
            'periode_tahun'         => $this->tahun,
            'periode_payroll_bulan' => $this->bulan,
            'periode_payroll_tahun' => $this->tahun,
            'nominal'               => (float) $this->nominalBayar,
            'status'                => 'pending',
            'keterangan'            => $this->keteranganBayar ?: ('PPOB ' . $this->labelKategori($ppob->kategori_ppob) . ' - ' . $ppob->nomor_pelanggan),
        ]);

        Flux::toast(heading: 'Berhasil', text: 'Pembayaran PPOB berhasil dicatat.', variant: 'success');
        Flux::modal('modal-bayar')->close();
        $this->payingId = null;
        unset($this->daftarPpob, $this->statsBulanIni);
    }

    public function batalkanBayar(int $tagihanId): void
    {
        $tagihan = TagihanPayrollEmployee::where('id', $tagihanId)
            ->where('jenis_tagihan', 'ppob')
            ->where('status', 'pending')
            ->first();

        if (!$tagihan) {
            Flux::toast(heading: 'Gagal', text: 'Tagihan tidak ditemukan atau sudah diproses payroll.', variant: 'danger');
            return;
        }

        $tagihan->delete();
        Flux::toast(heading: 'Dibatalkan', text: 'Pencatatan pembayaran PPOB berhasil dibatalkan.', variant: 'success');
        unset($this->daftarPpob, $this->statsBulanIni);
    }

    // ── Bulk Actions ─────────────────────────────────────────────

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            // Pilih semua yang belum dibayar
            $this->selectedIds = $this->daftarPpob
                ->filter(fn($r) => $r->tagihanPayrollEmployee === null)
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function openBulkBayar(): void
    {
        if (empty($this->selectedIds)) {
            Flux::toast(heading: 'Pilih Dulu', text: 'Pilih minimal satu tagihan PPOB terlebih dahulu.', variant: 'warning');
            return;
        }
        $this->bulkNominal = '';
        Flux::modal('modal-bulk-bayar')->show();
    }

    public function simpanBulkBayar(): void
    {
        $this->validate([
            'bulkNominal' => 'required|numeric|min:1',
        ], [
            'bulkNominal.required' => 'Nominal wajib diisi.',
        ]);

        $bulan = $this->bulan;
        $tahun = $this->tahun;
        $count = 0;

        DB::transaction(function () use ($bulan, $tahun, &$count) {
            foreach ($this->selectedIds as $id) {
                $ppob = PengaturanPpobEmployee::find((int) $id);
                if (!$ppob) continue;

                $exists = TagihanPayrollEmployee::where('jenis_tagihan', 'ppob')
                    ->where('tagihanable_type', PengaturanPpobEmployee::class)
                    ->where('tagihanable_id', $ppob->id)
                    ->where('periode_bulan', $bulan)
                    ->where('periode_tahun', $tahun)
                    ->exists();

                if ($exists) continue;

                TagihanPayrollEmployee::create([
                    'employee_id'           => $ppob->employee_id,
                    'jenis_tagihan'         => 'ppob',
                    'tagihanable_type'      => PengaturanPpobEmployee::class,
                    'tagihanable_id'        => $ppob->id,
                    'periode_bulan'         => $bulan,
                    'periode_tahun'         => $tahun,
                    'periode_payroll_bulan' => $bulan,
                    'periode_payroll_tahun' => $tahun,
                    'nominal'               => (float) $this->bulkNominal,
                    'status'                => 'pending',
                    'keterangan'            => 'PPOB ' . $this->labelKategori($ppob->kategori_ppob) . ' - ' . $ppob->nomor_pelanggan,
                ]);
                $count++;
            }
        });

        $this->selectedIds = [];
        $this->selectAll   = false;
        Flux::toast(heading: 'Berhasil', text: "$count tagihan PPOB berhasil dicatat.", variant: 'success');
        Flux::modal('modal-bulk-bayar')->close();
        unset($this->daftarPpob, $this->statsBulanIni);
    }

    // ── Add PPOB Setting ─────────────────────────────────────────

    public function selectAddEmployee(int $id, string $label): void
    {
        $this->addEmployeeId       = $id;
        $this->addEmployeeSearch   = $label;
        $this->addSelectedEmployee = Employee::find($id);
    }

    public function clearAddEmployee(): void
    {
        $this->addEmployeeId       = null;
        $this->addEmployeeSearch   = '';
        $this->addSelectedEmployee = null;
    }

    public function openTambahPpob(): void
    {
        $this->addEmployeeId       = null;
        $this->addEmployeeSearch   = '';
        $this->addSelectedEmployee = null;
        $this->addKategori         = 'listrik';
        $this->addNomorPelanggan   = '';
        $this->addCatatan          = '';
        Flux::modal('modal-tambah-ppob')->show();
    }

    public function simpanTambahPpob(): void
    {
        $this->validate([
            'addEmployeeId'      => 'required|integer',
            'addKategori'        => 'required|string',
            'addNomorPelanggan'  => 'required|string|max:100',
        ], [
            'addEmployeeId.required'     => 'Pilih karyawan terlebih dahulu.',
            'addKategori.required'       => 'Kategori PPOB wajib dipilih.',
            'addNomorPelanggan.required' => 'Nomor pelanggan wajib diisi.',
        ]);

        PengaturanPpobEmployee::create([
            'employee_id'    => $this->addEmployeeId,
            'kategori_ppob'  => $this->addKategori,
            'nomor_pelanggan'=> $this->addNomorPelanggan,
            'aktif'          => true,
            'catatan'        => $this->addCatatan ?: null,
        ]);

        Flux::toast(heading: 'Berhasil', text: 'Data PPOB anggota berhasil ditambahkan.', variant: 'success');
        Flux::modal('modal-tambah-ppob')->close();
        unset($this->daftarPpob, $this->statsBulanIni);
    }

    public function toggleAktif(int $id): void
    {
        $ppob = PengaturanPpobEmployee::find($id);
        if ($ppob) {
            $ppob->update(['aktif' => !$ppob->aktif]);
            unset($this->daftarPpob);
        }
    }
};
?>

<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════
         PAGE HEADER
    ═══════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Pembayaran PPOB</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">
                Kelola dan catat pembayaran tagihan utilitas anggota (Listrik, PDAM, Internet, BPJS, dll).
            </flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openTambahPpob">
            Tambah Data PPOB
        </flux:button>
    </div>

    <flux:separator variant="subtle" />

    {{-- ═══════════════════════════════════════════════
         FILTER BULAN & TAHUN
    ═══════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl px-4 py-2 shadow-sm">
            <flux:icon name="calendar" class="w-4 h-4 text-zinc-400" />
            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Periode:</span>
            <select wire:model.live="bulan" class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 bg-transparent border-none outline-none cursor-pointer">
                @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bl)
                    <option value="{{ $i + 1 }}">{{ $bl }}</option>
                @endforeach
            </select>
            <select wire:model.live="tahun" class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 bg-transparent border-none outline-none cursor-pointer">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         STATS CARDS
    ═══════════════════════════════════════════════ --}}
    @php $stats = $this->statsBulanIni; @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <flux:icon name="list-bullet" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total Tagihan</span>
            </div>
            <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $stats['total_tagihan'] }}</div>
            <div class="text-xs text-zinc-400 mt-1">anggota aktif bulan ini</div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-xl bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                    <flux:icon name="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Sudah Dibayar</span>
            </div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['sudah_bayar'] }}</div>
            <div class="text-xs text-zinc-400 mt-1">tagihan tercatat</div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-xl bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center">
                    <flux:icon name="clock" class="w-5 h-5 text-orange-500 dark:text-orange-400" />
                </div>
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Belum Dibayar</span>
            </div>
            <div class="text-3xl font-bold text-orange-500 dark:text-orange-400">{{ $stats['belum_bayar'] }}</div>
            <div class="text-xs text-zinc-400 mt-1">tagihan menunggu</div>
        </div>

        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-4 shadow-sm text-white">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <flux:icon name="banknotes" class="w-5 h-5 text-white" />
                </div>
                <span class="text-xs font-semibold text-white/80 uppercase tracking-wider">Total Terbayar</span>
            </div>
            <div class="text-xl font-bold">Rp {{ number_format($stats['total_nominal'], 0, ',', '.') }}</div>
            <div class="text-xs text-white/70 mt-1">sudah dicatat bulan ini</div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         TABEL UTAMA
    ═══════════════════════════════════════════════ --}}
    <flux:card class="flex flex-col">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between mb-4">
            <div class="flex items-center gap-3">
                <flux:heading size="lg" level="2">Daftar Tagihan PPOB</flux:heading>
                @if(count($selectedIds) > 0)
                    <span class="text-sm text-blue-600 dark:text-blue-400 font-semibold">
                        {{ count($selectedIds) }} dipilih
                    </span>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Filter Status --}}
                <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    @foreach(['semua' => 'Semua', 'belum' => 'Belum Bayar', 'sudah' => 'Sudah Bayar'] as $val => $lbl)
                        <button wire:click="$set('filterStatus', '{{ $val }}')"
                            class="px-3 py-1.5 text-xs font-semibold cursor-pointer transition-colors
                                   {{ $filterStatus === $val
                                       ? 'bg-blue-600 text-white'
                                       : 'bg-white dark:bg-zinc-900 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>

                <flux:input wire:model.live.debounce.300ms="search" size="sm"
                    class="max-w-52" placeholder="Cari nama / NPK / no..." icon="magnifying-glass" />

                @if(count($selectedIds) > 0)
                    <flux:button size="sm" variant="primary" icon="check-circle" wire:click="openBulkBayar">
                        Bayar {{ count($selectedIds) }} Tagihan
                    </flux:button>
                @endif
            </div>
        </div>

        <flux:separator variant="subtle" class="mb-2" />

        <div class="overflow-x-auto">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>
                        <flux:checkbox wire:model.live="selectAll" />
                    </flux:table.column>
                    <flux:table.column>Anggota</flux:table.column>
                    <flux:table.column>Kategori</flux:table.column>
                    <flux:table.column>No. Pelanggan</flux:table.column>
                    <flux:table.column>Status Bayar</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->daftarPpob as $row)
                        @php
                            $tagihan = $row->tagihanPayrollEmployee;
                            $sudahBayar = $tagihan !== null;
                        @endphp
                        <flux:table.row :key="$row->id"
                            class="{{ $sudahBayar ? 'opacity-70' : '' }}">

                            {{-- Checkbox --}}
                            <flux:table.cell>
                                @if(!$sudahBayar)
                                    <flux:checkbox wire:model.live="selectedIds" value="{{ $row->id }}" />
                                @else
                                    <flux:icon name="check-circle" class="w-5 h-5 text-green-500" />
                                @endif
                            </flux:table.cell>

                            {{-- Anggota --}}
                            <flux:table.cell>
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300 shrink-0">
                                        {{ substr($row->employee->nama_lengkap ?? 'A', 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-medium text-sm block text-zinc-900 dark:text-zinc-100">
                                            {{ $row->employee->nama_lengkap ?? 'Unknown' }}
                                        </span>
                                        <span class="text-xs text-zinc-400 block">NPK: {{ $row->employee->npk ?? '-' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>

                            {{-- Kategori --}}
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:badge color="{{ $this->warnaBadge($row->kategori_ppob) }}" size="sm" icon="{{ $this->ikonKategori($row->kategori_ppob) }}">
                                        {{ $this->labelKategori($row->kategori_ppob) }}
                                    </flux:badge>
                                </div>
                            </flux:table.cell>

                            {{-- No. Pelanggan --}}
                            <flux:table.cell class="font-mono text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $row->nomor_pelanggan }}
                            </flux:table.cell>

                            {{-- Status --}}
                            <flux:table.cell>
                                @if($sudahBayar)
                                    <flux:badge color="green" size="sm" icon="check-circle">Sudah Dibayar</flux:badge>
                                @else
                                    <flux:badge color="orange" size="sm" icon="clock">Belum Dibayar</flux:badge>
                                @endif
                            </flux:table.cell>

                            {{-- Nominal --}}
                            <flux:table.cell>
                                @if($sudahBayar)
                                    <span class="font-semibold text-green-600 dark:text-green-400">
                                        Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 text-sm italic">—</span>
                                @endif
                            </flux:table.cell>

                            {{-- Aksi --}}
                            <flux:table.cell>
                                @if(!$sudahBayar)
                                    <flux:button size="sm" variant="primary" icon="check"
                                        wire:click="openBayar({{ $row->id }})">
                                        Catat Bayar
                                    </flux:button>
                                @else
                                    @if($tagihan->status === 'pending')
                                        <flux:button size="sm" variant="danger" icon="x-mark"
                                            wire:click="batalkanBayar({{ $tagihan->id }})"
                                            wire:confirm="Batalkan pencatatan pembayaran PPOB ini?">
                                            Batalkan
                                        </flux:button>
                                    @else
                                        <flux:badge color="green" size="sm" icon="lock-closed">Payroll</flux:badge>
                                    @endif
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-12">
                                <div class="flex flex-col items-center gap-3 text-zinc-400">
                                    <flux:icon name="bolt" class="w-10 h-10 opacity-30" />
                                    <p class="text-sm">
                                        @if($search)
                                            Tidak ada hasil untuk "{{ $search }}".
                                        @else
                                            Tidak ada data PPOB aktif bulan ini.
                                            <br>
                                            <span class="text-xs">Klik <strong>Tambah Data PPOB</strong> untuk mendaftarkan tagihan anggota.</span>
                                        @endif
                                    </p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>


    {{-- ═══════════════════════════════════════════════
         MODAL: CATAT BAYAR (SINGLE)
    ═══════════════════════════════════════════════ --}}
    <flux:modal name="modal-bayar" class="md:w-[28rem] space-y-5">
        <div>
            <flux:heading size="lg">Catat Pembayaran PPOB</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Masukkan nominal tagihan yang sudah dibayarkan bulan ini.</flux:text>
        </div>
        <flux:separator variant="subtle" />

        <form wire:submit.prevent="simpanBayar" class="space-y-4">
            <flux:field>
                <flux:label>Nominal Tagihan (Rp)</flux:label>
                <flux:input wire:model="nominalBayar" type="number" min="1"
                    placeholder="Contoh: 500000" autofocus />
                <flux:error name="nominalBayar" />
            </flux:field>
            <flux:field>
                <flux:label>Keterangan (Opsional)</flux:label>
                <flux:input wire:model="keteranganBayar"
                    placeholder="Misal: PLN bulan Juni..." />
            </flux:field>
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- ═══════════════════════════════════════════════
         MODAL: BAYAR BULK
    ═══════════════════════════════════════════════ --}}
    <flux:modal name="modal-bulk-bayar" class="md:w-[28rem] space-y-5">
        <div>
            <flux:heading size="lg">Bayar Massal PPOB</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                Catat pembayaran untuk <strong>{{ count($selectedIds) }}</strong> tagihan sekaligus dengan nominal yang sama.
            </flux:text>
        </div>
        <flux:separator variant="subtle" />

        <form wire:submit.prevent="simpanBulkBayar" class="space-y-4">
            <flux:field>
                <flux:label>Nominal Per Tagihan (Rp)</flux:label>
                <flux:input wire:model="bulkNominal" type="number" min="1"
                    placeholder="Nominal sama untuk semua tagihan terpilih" autofocus />
                <flux:error name="bulkNominal" />
                <flux:description>Nominal ini akan diterapkan ke semua {{ count($selectedIds) }} tagihan yang dipilih.</flux:description>
            </flux:field>
            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check-circle">
                    Catat {{ count($selectedIds) }} Pembayaran
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- ═══════════════════════════════════════════════
         MODAL: TAMBAH DATA PPOB
    ═══════════════════════════════════════════════ --}}
    <flux:modal name="modal-tambah-ppob" class="md:w-[36rem] space-y-5">
        <div>
            <flux:heading size="lg">Tambah Data PPOB Anggota</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Daftarkan tagihan utilitas anggota yang akan dikelola koperasi.</flux:text>
        </div>
        <flux:separator variant="subtle" />

        <form wire:submit.prevent="simpanTambahPpob" class="space-y-4">

            {{-- Pilih Karyawan --}}
            <flux:field>
                <flux:label>Karyawan</flux:label>
                @if(!$addSelectedEmployee)
                    <div class="relative">
                        <flux:input wire:model.live.debounce.300ms="addEmployeeSearch"
                            icon="magnifying-glass" placeholder="Ketik NPK atau nama karyawan..." />
                        @if($addEmployeeSearch && count($this->availableEmployees) > 0)
                            <div class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                                @foreach($this->availableEmployees as $emp)
                                    <div wire:click="selectAddEmployee({{ $emp->id }}, '{{ $emp->npk }} - {{ $emp->nama_lengkap }}')"
                                         class="px-4 py-2.5 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer">
                                        <div class="font-medium text-sm text-zinc-800 dark:text-zinc-200">{{ $emp->nama_lengkap }}</div>
                                        <div class="text-xs text-zinc-500">NPK: {{ $emp->npk }} | {{ $emp->seksi ?? '-' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($addEmployeeSearch)
                            <div class="absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow p-3 text-center text-sm text-zinc-500">
                                Karyawan tidak ditemukan.
                            </div>
                        @endif
                    </div>
                    <flux:error name="addEmployeeId" />
                @else
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-sm">
                                {{ substr($addSelectedEmployee->nama_lengkap, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">{{ $addSelectedEmployee->nama_lengkap }}</div>
                                <div class="text-xs text-zinc-500">NPK: {{ $addSelectedEmployee->npk }}</div>
                            </div>
                        </div>
                        <flux:button size="sm" variant="subtle" wire:click="clearAddEmployee" icon="x-mark">Ganti</flux:button>
                    </div>
                @endif
            </flux:field>

            {{-- Kategori --}}
            <flux:field>
                <flux:label>Kategori PPOB</flux:label>
                <flux:select wire:model="addKategori">
                    <flux:select.option value="listrik">Listrik (PLN)</flux:select.option>
                    <flux:select.option value="pdam">Air (PDAM)</flux:select.option>
                    <flux:select.option value="internet">Internet / WiFi</flux:select.option>
                    <flux:select.option value="bpjs">BPJS Kesehatan</flux:select.option>
                    <flux:select.option value="telepon">Telepon / Pulsa</flux:select.option>
                    <flux:select.option value="tv">TV Kabel / Streaming</flux:select.option>
                    <flux:select.option value="lainnya">Lain-lain</flux:select.option>
                </flux:select>
                <flux:error name="addKategori" />
            </flux:field>

            {{-- Nomor Pelanggan --}}
            <flux:field>
                <flux:label>Nomor Pelanggan / ID Langganan</flux:label>
                <flux:input wire:model="addNomorPelanggan" placeholder="Contoh: 5217010012341" />
                <flux:error name="addNomorPelanggan" />
            </flux:field>

            {{-- Catatan --}}
            <flux:field>
                <flux:label>Catatan (Opsional)</flux:label>
                <flux:input wire:model="addCatatan" placeholder="Misal: Rumah, Kost, dll." />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" :disabled="!$addSelectedEmployee">Simpan Data PPOB</flux:button>
            </div>
        </form>
    </flux:modal>

</div>
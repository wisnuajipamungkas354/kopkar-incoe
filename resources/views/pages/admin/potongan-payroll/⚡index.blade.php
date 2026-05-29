<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\PotonganPayrollEmployee;
use App\Models\TagihanPayrollEmployee;
use Carbon\Carbon;

new #[Layout('layouts::admin', ['title' => 'Rekap Potongan Payroll'])] class extends Component
{
    use WithPagination;

    public $selectedMonth;
    public $selectedYear;
    public $search = '';
    public $perPage = 10;
    public $selectedEmployee = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedMonth' => ['except' => ''],
        'selectedYear' => ['except' => ''],
    ];

    public function mount()
    {
        $this->selectedMonth = $this->selectedMonth ?: (int) date('m');
        $this->selectedYear = $this->selectedYear ?: (int) date('Y');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedMonth()
    {
        $this->resetPage();
    }

    public function updatingSelectedYear()
    {
        $this->resetPage();
    }

    #[Computed]
    public function months()
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    #[Computed]
    public function years()
    {
        $currentYear = (int) date('Y');
        return range($currentYear - 2, $currentYear + 2);
    }

    #[Computed]
    public function payrollSummary()
    {
        $month = (int) $this->selectedMonth;
        $year = (int) $this->selectedYear;

        $firstDay = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $lastDay = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // Totals for recurring deductions (from active PotonganPayrollEmployee)
        $totalWajib = PotonganPayrollEmployee::where('jenis_potongan', 'simpanan_wajib')
            ->where('tanggal_mulai_berlaku', '<=', $lastDay)
            ->where(function ($q) use ($firstDay) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', $firstDay);
            })
            ->whereHas('employee.koperasiMember')
            ->sum('nominal');

        $totalSukarela = PotonganPayrollEmployee::where('jenis_potongan', 'simpanan_sukarela')
            ->where('tanggal_mulai_berlaku', '<=', $lastDay)
            ->where(function ($q) use ($firstDay) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', $firstDay);
            })
            ->whereHas('employee.koperasiMember')
            ->sum('nominal');

        $totalLazisRutin = PotonganPayrollEmployee::where('jenis_potongan', 'lazis')
            ->where('tanggal_mulai_berlaku', '<=', $lastDay)
            ->where(function ($q) use ($firstDay) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', $firstDay);
            })
            ->whereHas('employee.koperasiMember')
            ->sum('nominal');

        // Totals for transactional tagihans
        $tagihanSums = TagihanPayrollEmployee::where('periode_payroll_bulan', $month)
            ->where('periode_payroll_tahun', $year)
            ->whereHas('employee.koperasiMember')
            ->selectRaw("jenis_tagihan, SUM(nominal) as total")
            ->groupBy('jenis_tagihan')
            ->pluck('total', 'jenis_tagihan')
            ->toArray();

        $totalPokok = $tagihanSums['simpanan_pokok'] ?? 0;
        $totalPinjaman = $tagihanSums['pinjaman'] ?? 0;
        $totalPembiayaan = $tagihanSums['pembiayaan'] ?? 0;
        $totalPpob = $tagihanSums['ppob'] ?? 0;
        $totalLazisTambahan = $tagihanSums['lazis'] ?? 0;
        $totalToko = $tagihanSums['toko'] ?? 0;
        $totalLainnya = ($tagihanSums['operasional'] ?? 0);

        $totalSimpanan = $totalWajib + $totalSukarela + $totalPokok;
        $totalPinjamanPembiayaan = $totalPinjaman + $totalPembiayaan;
        $totalLazis = $totalLazisRutin + $totalLazisTambahan;
        $totalLain = $totalToko + $totalLainnya;

        $grandTotal = $totalSimpanan + $totalPinjamanPembiayaan + $totalPpob + $totalLazis + $totalLain;

        return [
            'simpanan' => $totalSimpanan,
            'pinjaman_pembiayaan' => $totalPinjamanPembiayaan,
            'ppob' => $totalPpob,
            'lazis' => $totalLazis,
            'lainnya' => $totalLain,
            'grand_total' => $grandTotal,
        ];
    }

    #[Computed]
    public function employeesDeductions()
    {
        $month = (int) $this->selectedMonth;
        $year = (int) $this->selectedYear;

        $firstDay = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $lastDay = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $query = Employee::whereHas('koperasiMember', function($q) {
                $q->where('status', 'active');
            })
            ->with([
                'potonganPayrollEmployee' => function ($query) use ($firstDay, $lastDay) {
                    $query->where('tanggal_mulai_berlaku', '<=', $lastDay)
                          ->where(function ($q) use ($firstDay) {
                              $q->whereNull('tanggal_selesai')
                                ->orWhere('tanggal_selesai', '>=', $firstDay);
                          });
                },
                'tagihanPayrollEmployee' => function ($query) use ($month, $year) {
                    $query->where('periode_payroll_bulan', $month)
                          ->where('periode_payroll_tahun', $year);
                }
            ])
            ->when($this->search, function ($q) {
                $q->where(function($sub) {
                    $sub->where('nama_lengkap', 'like', '%' . $this->search . '%')
                       ->orWhere('npk', 'like', '%' . $this->search . '%')
                       ->orWhere('seksi', 'like', '%' . $this->search . '%');
                });
            });

        $paginated = $query->paginate($this->perPage);

        // Map computed totals for each employee row
        $paginated->getCollection()->transform(function ($employee) {
            $sw = $employee->potonganPayrollEmployee->where('jenis_potongan', 'simpanan_wajib')->sum('nominal');
            $ss = $employee->potonganPayrollEmployee->where('jenis_potongan', 'simpanan_sukarela')->sum('nominal');
            $lzRutin = $employee->potonganPayrollEmployee->where('jenis_potongan', 'lazis')->sum('nominal');

            $sp = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'simpanan_pokok')->sum('nominal');
            $pinjaman = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'pinjaman')->sum('nominal');
            $pembiayaan = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'pembiayaan')->sum('nominal');
            $ppob = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'ppob')->sum('nominal');
            
            // Other tagihans (like toko, operasional, or 1x lazis)
            $lain = $employee->tagihanPayrollEmployee->whereNotIn('jenis_tagihan', ['pinjaman', 'pembiayaan', 'ppob', 'simpanan_pokok'])->sum('nominal');

            $employee->simpanan_wajib = $sw;
            $employee->simpanan_sukarela = $ss;
            $employee->simpanan_pokok = $sp;
            $employee->lazis_rutin = $lzRutin;
            $employee->pinjaman = $pinjaman;
            $employee->pembiayaan = $pembiayaan;
            $employee->ppob = $ppob;
            $employee->tagihan_lain = $lain;
            $employee->total_potongan = $sw + $ss + $sp + $lzRutin + $pinjaman + $pembiayaan + $ppob + $lain;

            return $employee;
        });

        return $paginated;
    }

    public function showDetail($employeeId)
    {
        $month = (int) $this->selectedMonth;
        $year = (int) $this->selectedYear;

        $firstDay = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $lastDay = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $employee = Employee::with([
            'potonganPayrollEmployee' => function ($query) use ($firstDay, $lastDay) {
                $query->where('tanggal_mulai_berlaku', '<=', $lastDay)
                      ->where(function ($q) use ($firstDay) {
                          $q->whereNull('tanggal_selesai')
                            ->orWhere('tanggal_selesai', '>=', $firstDay);
                      });
            },
            'tagihanPayrollEmployee' => function ($query) use ($month, $year) {
                $query->where('periode_payroll_bulan', $month)
                      ->where('periode_payroll_tahun', $year)
                      ->with('tagihanable');
            }
        ])->find($employeeId);

        if ($employee) {
            $sw = $employee->potonganPayrollEmployee->where('jenis_potongan', 'simpanan_wajib')->sum('nominal');
            $ss = $employee->potonganPayrollEmployee->where('jenis_potongan', 'simpanan_sukarela')->sum('nominal');
            $lzRutin = $employee->potonganPayrollEmployee->where('jenis_potongan', 'lazis')->sum('nominal');

            $sp = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'simpanan_pokok')->sum('nominal');
            $pinjaman = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'pinjaman')->sum('nominal');
            $pembiayaan = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'pembiayaan')->sum('nominal');
            $ppob = $employee->tagihanPayrollEmployee->where('jenis_tagihan', 'ppob')->sum('nominal');
            $lain = $employee->tagihanPayrollEmployee->whereNotIn('jenis_tagihan', ['pinjaman', 'pembiayaan', 'ppob', 'simpanan_pokok'])->sum('nominal');

            $employee->simpanan_wajib = $sw;
            $employee->simpanan_sukarela = $ss;
            $employee->simpanan_pokok = $sp;
            $employee->lazis_rutin = $lzRutin;
            $employee->pinjaman = $pinjaman;
            $employee->pembiayaan = $pembiayaan;
            $employee->ppob = $ppob;
            $employee->tagihan_lain = $lain;
            $employee->total_potongan = $sw + $ss + $sp + $lzRutin + $pinjaman + $pembiayaan + $ppob + $lain;

            $this->selectedEmployee = $employee;
            $this->js("Flux.modal('detail-modal').show()");
        }
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Rekap Potongan Payroll</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Laporan rekapitulasi pemotongan payroll gaji karyawan untuk iuran dan kewajiban Koperasi.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Filters Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
        <!-- Month Filter -->
        <flux:field>
            <flux:label>Periode Bulan</flux:label>
            <flux:select wire:model.live="selectedMonth" placeholder="Pilih Bulan...">
                @foreach($this->months as $val => $name)
                    <flux:select.option value="{{ $val }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <!-- Year Filter -->
        <flux:field>
            <flux:label>Periode Tahun</flux:label>
            <flux:select wire:model.live="selectedYear" placeholder="Pilih Tahun...">
                @foreach($this->years as $yr)
                    <flux:select.option value="{{ $yr }}">{{ $yr }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <!-- Search Input -->
        <flux:field class="md:col-span-2">
            <flux:label>Cari Anggota</flux:label>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Masukkan nama, NPK atau seksi..." icon="magnifying-glass" />
        </flux:field>
    </div>

    <!-- Summary Cards Dashboard -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Card 1: Simpanan -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden">
            <div class="absolute right-3 top-3 bg-blue-50 dark:bg-blue-950/40 p-2 rounded-lg text-blue-600 dark:text-blue-400">
                <flux:icon name="wallet" variant="outline" class="w-5 h-5" />
            </div>
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Simpanan Bulanan</div>
            <div class="text-xl font-extrabold text-zinc-800 dark:text-zinc-200 mt-2">
                Rp {{ number_format($this->payrollSummary['simpanan'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-zinc-400 mt-1">Pokok, Wajib & Sukarela Rutin</div>
        </div>

        <!-- Card 2: Pinjaman & Pembiayaan -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden">
            <div class="absolute right-3 top-3 bg-amber-50 dark:bg-amber-950/40 p-2 rounded-lg text-amber-600 dark:text-amber-400">
                <flux:icon name="banknotes" variant="outline" class="w-5 h-5" />
            </div>
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Pinjaman & Pembiayaan</div>
            <div class="text-xl font-extrabold text-zinc-800 dark:text-zinc-200 mt-2">
                Rp {{ number_format($this->payrollSummary['pinjaman_pembiayaan'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-zinc-400 mt-1">Angsuran Pinjaman & Pembiayaan</div>
        </div>

        <!-- Card 3: PPOB -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden">
            <div class="absolute right-3 top-3 bg-emerald-50 dark:bg-emerald-950/40 p-2 rounded-lg text-emerald-600 dark:text-emerald-400">
                <flux:icon name="qr-code" variant="outline" class="w-5 h-5" />
            </div>
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Tagihan PPOB</div>
            <div class="text-xl font-extrabold text-zinc-800 dark:text-zinc-200 mt-2">
                Rp {{ number_format($this->payrollSummary['ppob'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-zinc-400 mt-1">Listrik, Wifi, BPJS via Payroll</div>
        </div>

        <!-- Card 4: Lazis & Lainnya -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden">
            <div class="absolute right-3 top-3 bg-purple-50 dark:bg-purple-950/40 p-2 rounded-lg text-purple-600 dark:text-purple-400">
                <flux:icon name="heart" variant="outline" class="w-5 h-5" />
            </div>
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Lazis & Lainnya</div>
            <div class="text-xl font-extrabold text-zinc-800 dark:text-zinc-200 mt-2">
                Rp {{ number_format($this->payrollSummary['lazis'] + $this->payrollSummary['lainnya'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-zinc-400 mt-1">Zakat, Infaq, Toko & Operasional</div>
        </div>

        <!-- Card 5: Grand Total -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-5 rounded-2xl shadow-md relative overflow-hidden text-white sm:col-span-2 lg:col-span-1">
            <div class="absolute right-3 top-3 bg-white/20 p-2 rounded-lg">
                <flux:icon name="calculator" class="w-5 h-5 text-white" />
            </div>
            <div class="text-xs font-bold uppercase tracking-wider opacity-80">Total Payroll</div>
            <div class="text-2xl font-black mt-2">
                Rp {{ number_format($this->payrollSummary['grand_total'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] opacity-80 mt-1">Estimasi Potongan Bulan Ini</div>
        </div>
    </div>

    <!-- Table Section -->
    <flux:card class="flex flex-col">
        <div class="flex justify-between items-center mb-4">
            <flux:heading size="lg" level="2">Daftar Potongan Anggota</flux:heading>
            <flux:text class="text-xs text-zinc-400">Periode: {{ $this->months[$this->selectedMonth] }} {{ $this->selectedYear }}</flux:text>
        </div>

        <div class="overflow-x-auto">
            <flux:table class="mt-2" :paginate="$this->employeesDeductions">
                <flux:table.columns>
                    <flux:table.column>Anggota</flux:table.column>
                    <flux:table.column class="text-right">S. Wajib</flux:table.column>
                    <flux:table.column class="text-right">S. Sukarela</flux:table.column>
                    <flux:table.column class="text-right">S. Pokok</flux:table.column>
                    <flux:table.column class="text-right">Lazis</flux:table.column>
                    <flux:table.column class="text-right">Pinjaman</flux:table.column>
                    <flux:table.column class="text-right">Pembiayaan</flux:table.column>
                    <flux:table.column class="text-right">PPOB</flux:table.column>
                    <flux:table.column class="text-right">Lainnya</flux:table.column>
                    <flux:table.column class="text-right font-bold">Total</flux:table.column>
                    <flux:table.column class="text-center">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->employeesDeductions as $row)
                        <flux:table.row :key="$row->id">
                            <!-- Column 1: Anggota -->
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->nama_lengkap ?? 'A', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->nama_lengkap ?? 'Unknown' }}</span>
                                        <span class="text-xs text-zinc-500">NPK: {{ $row->npk ?? '-' }} • {{ $row->seksi ?? '-' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <!-- Column 2: Simpanan Wajib -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->simpanan_wajib > 0 ? 'Rp ' . number_format($row->simpanan_wajib, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 3: Simpanan Sukarela -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->simpanan_sukarela > 0 ? 'Rp ' . number_format($row->simpanan_sukarela, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 4: Simpanan Pokok -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->simpanan_pokok > 0 ? 'Rp ' . number_format($row->simpanan_pokok, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 5: Lazis -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->lazis_rutin > 0 ? 'Rp ' . number_format($row->lazis_rutin, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 6: Pinjaman -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->pinjaman > 0 ? 'Rp ' . number_format($row->pinjaman, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 7: Pembiayaan -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->pembiayaan > 0 ? 'Rp ' . number_format($row->pembiayaan, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 8: PPOB -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->ppob > 0 ? 'Rp ' . number_format($row->ppob, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 9: Lainnya -->
                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-350">
                                {{ $row->tagihan_lain > 0 ? 'Rp ' . number_format($row->tagihan_lain, 0, ',', '.') : '-' }}
                            </flux:table.cell>

                            <!-- Column 10: Total -->
                            <flux:table.cell class="text-right font-extrabold text-blue-600 dark:text-blue-400">
                                Rp {{ number_format($row->total_potongan, 0, ',', '.') }}
                            </flux:table.cell>

                            <!-- Column 11: Action -->
                            <flux:table.cell class="text-center">
                                <flux:button size="xs" variant="subtle" icon="eye" wire:click="showDetail({{ $row->id }})">Breakdown</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="11" class="text-center text-zinc-500 py-8">
                                Tidak ada data potongan payroll ditemukan untuk periode ini.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Detail Breakdown Modal -->
    <flux:modal name="detail-modal" class="md:w-2xl max-h-[90vh] overflow-y-auto">
        @if($selectedEmployee)
            <div>
                <flux:heading size="lg">Detail Breakdown Potongan Payroll</flux:heading>
                <flux:text size="sm" class="mt-1">Rincian seluruh pos potongan anggota untuk periode {{ $this->months[$this->selectedMonth] }} {{ $this->selectedYear }}.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Info Anggota Header -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedEmployee->nama_lengkap, 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedEmployee->nama_lengkap }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">NPK: {{ $selectedEmployee->npk }} • Seksi: {{ $selectedEmployee->seksi }}</flux:text>
                    </div>
                </div>

                <!-- Recurring Deductions Section -->
                <div>
                    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">1. Potongan Rutin Bulanan (Recurring)</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-zinc-100 dark:bg-zinc-800 text-xs font-bold text-zinc-500 uppercase tracking-wider">
                                    <th class="p-3">Pos Potongan</th>
                                    <th class="p-3">Mulai Berlaku</th>
                                    <th class="p-3 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                @php $totalRecurring = 0; @endphp
                                @forelse($selectedEmployee->potonganPayrollEmployee as $pot)
                                    @php $totalRecurring += $pot->nominal; @endphp
                                    <tr class="hover:bg-zinc-100/50 dark:hover:bg-zinc-800/30">
                                        <td class="p-3 font-medium text-zinc-800 dark:text-zinc-400">
                                            @if($pot->jenis_potongan === 'simpanan_wajib')
                                                Simpanan Wajib
                                            @elseif($pot->jenis_potongan === 'simpanan_sukarela')
                                                Simpanan Sukarela
                                            @elseif($pot->jenis_potongan === 'lazis')
                                                Lazis Bulanan ({{ ucfirst($pot->sub_jenis_potongan ?? 'zakat') }})
                                            @else
                                                {{ ucfirst($pot->jenis_potongan) }}
                                            @endif
                                        </td>
                                        <td class="p-3 text-zinc-500 text-xs">
                                            {{ \Carbon\Carbon::parse($pot->tanggal_mulai_berlaku)->format('d F Y') }}
                                        </td>
                                        <td class="p-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                                            Rp {{ number_format($pot->nominal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="p-4 text-center text-zinc-400 text-xs">Tidak ada potongan rutin aktif bulan ini.</td>
                                    </tr>
                                @endforelse
                                @if($totalRecurring > 0)
                                    <tr class="bg-zinc-100/30 dark:bg-zinc-800/10 font-bold border-t border-zinc-200 dark:border-zinc-800">
                                        <td colspan="2" class="p-3 text-zinc-600 dark:text-zinc-400 text-xs uppercase tracking-wider">Subtotal Rutin</td>
                                        <td class="p-3 text-right text-zinc-800 dark:text-zinc-200">Rp {{ number_format($totalRecurring, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Transactional Deductions Section -->
                <div>
                    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">2. Tagihan Bulanan (Transactional & Non-Recurring)</flux:heading>
                    <div class="bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-zinc-100 dark:bg-zinc-800 text-xs font-bold text-zinc-500 uppercase tracking-wider">
                                    <th class="p-3">Jenis Tagihan / Item</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-right">Nominal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                @php $totalTransactional = 0; @endphp
                                @forelse($selectedEmployee->tagihanPayrollEmployee as $tag)
                                    @php $totalTransactional += $tag->nominal; @endphp
                                    <tr class="hover:bg-zinc-100/50 dark:hover:bg-zinc-800/30">
                                        <td class="p-3">
                                            <div class="font-medium text-zinc-800 dark:text-zinc-400">
                                                @if($tag->jenis_tagihan === 'pinjaman')
                                                    Pinjaman ({{ $tag->tagihanable?->jenis_pinjaman === 'qard' ? 'Qard Hasan' : 'Bon Sementara' }})
                                                @elseif($tag->jenis_tagihan === 'pembiayaan')
                                                    Pembiayaan ({{ ucfirst($tag->tagihanable?->kategori_pembiayaan ?? 'Barang') }})
                                                @elseif($tag->jenis_tagihan === 'ppob')
                                                    PPOB ({{ $tag->tagihanable?->kategori_ppob ?? '-' }})
                                                @elseif($tag->jenis_tagihan === 'simpanan_pokok')
                                                    Simpanan Pokok
                                                @else
                                                    Tagihan {{ ucfirst($tag->jenis_tagihan) }}
                                                @endif
                                            </div>
                                            @if($tag->keterangan || $tag->tagihanable?->nomor_pelanggan)
                                                <div class="text-[11px] text-zinc-400 mt-0.5">
                                                    {{ $tag->keterangan }} 
                                                    @if($tag->tagihanable?->nomor_pelanggan)
                                                         • ID: {{ $tag->tagihanable->nomor_pelanggan }}
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-3 text-xs">
                                            @if($tag->status === 'lunas')
                                                <flux:badge color="green" size="sm">Lunas</flux:badge>
                                            @elseif($tag->status === 'masuk_payroll')
                                                <flux:badge color="blue" size="sm">Masuk Payroll</flux:badge>
                                            @else
                                                <flux:badge color="yellow" size="sm">Pending</flux:badge>
                                            @endif
                                        </td>
                                        <td class="p-3 text-right font-semibold text-zinc-800 dark:text-zinc-200">
                                            Rp {{ number_format($tag->nominal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="p-4 text-center text-zinc-400 text-xs">Tidak ada tagihan transaksional bulan ini.</td>
                                    </tr>
                                @endforelse
                                @if($totalTransactional > 0)
                                    <tr class="bg-zinc-100/30 dark:bg-zinc-800/10 font-bold border-t border-zinc-200 dark:border-zinc-800">
                                        <td colspan="2" class="p-3 text-zinc-600 dark:text-zinc-400 text-xs uppercase tracking-wider">Subtotal Tagihan</td>
                                        <td class="p-3 text-right text-zinc-800 dark:text-zinc-200">Rp {{ number_format($totalTransactional, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <!-- Grand Total Summary Box -->
                <div class="p-4 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/40 rounded-2xl flex justify-between items-center">
                    <div>
                        <div class="text-xs font-bold text-blue-800 dark:text-blue-400 uppercase tracking-wider">Total Potongan Payroll</div>
                        <div class="text-[10px] text-zinc-400 mt-0.5">Gabungan Rutin & Tagihan bulan ini</div>
                    </div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400">
                        Rp {{ number_format($selectedEmployee->total_potongan, 0, ',', '.') }}
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end pt-2">
                    <flux:button variant="subtle" x-on:click="$flux.modal('detail-modal').close()">Tutup</flux:button>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat rincian potongan...
            </div>
        @endif
    </flux:modal>
</div>

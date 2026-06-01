<?php

use App\Models\KoperasiMember;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\PenarikanSaldo;
use App\Models\MutasiSaldoMember;
use App\Models\Employee;
use App\Models\NamaBank;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Penarikan Saldo'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPengajuan = null;
    public $alasanPenolakan = '';

    // ── Form Tambah ──────────────────────────────
    public bool $showTambahForm = false;
    
    // Pilih Karyawan
    public $employee_id      = '';
    public $employeeSearch   = '';
    public $selectedEmployee = null;

    // Balances
    public $saldoSukarela = 0;
    public $saldoLain     = 0;
    public $saldoShu      = 0;

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

    #[Computed]
    public function pengajuan()
    {
        $data = PenarikanSaldo::with(['employee', 'employee.user', 'detailPenarikanSaldo'])
                ->whereIn('status', ['diajukan', 'diproses'])
                ->orderBy('created_at', 'DESC')
                ->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap ?? '', $this->search) !== false ||
                       stripos($item->employee->npk ?? '', $this->search) !== false ||
                       stripos($item->nomor_pengajuan ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    #[Computed]
    public function availableEmployees()
    {
        $query = Employee::query();
        if ($this->employeeSearch && !str_contains($this->employeeSearch, ' - ')) {
            $query->where(fn($q) => $q
                ->where('npk', 'like', '%'.$this->employeeSearch.'%')
                ->orWhere('nama_lengkap', 'like', '%'.$this->employeeSearch.'%')
            );
        }
        return $query->orderBy('nama_lengkap')->take(50)->get();
    }

    #[Computed]
    public function daftarBank()
    {
        return NamaBank::orderBy('nama_bank')->get();
    }

    #[Computed]
    public function totalNominalTarik()
    {
        return ($this->tarikSukarela ? (int)$this->nominalSukarela : 0)
             + ($this->tarikLain     ? (int)$this->nominalLain     : 0)
             + ($this->tarikShu      ? (int)$this->nominalShu      : 0);
    }

    public function detailPengajuan($id)
    {
        $this->selectedPengajuan = PenarikanSaldo::with(['employee', 'employee.user', 'detailPenarikanSaldo'])->find($id);
        $this->alasanPenolakan = '';
        Flux::modal('detail-pengajuan')->show();
    }

    // ══════════════════════════════════════════════════════════
    // FORM TAMBAH ACTIONS
    // ══════════════════════════════════════════════════════════

    public function openTambahForm()
    {
        $this->showTambahForm  = true;
        $this->employee_id     = '';
        $this->employeeSearch  = '';
        $this->selectedEmployee = null;
        
        $this->tarikSukarela = false;
        $this->tarikLain = false;
        $this->tarikShu = false;
        $this->nominalSukarela = '';
        $this->nominalLain = '';
        $this->nominalShu = '';
        $this->namaBank = '';
        $this->noRekening = '';
        $this->namaPemilik = '';
        $this->keteranganTarik = '';
        $this->saldoSukarela = 0;
        $this->saldoLain = 0;
        $this->saldoShu = 0;
        
        Flux::modal('form-tambah')->show();
    }

    public function selectEmployee($id, $label)
    {
        $this->employee_id      = $id;
        $this->employeeSearch   = $label;
        $this->selectedEmployee = Employee::with('koperasiMember')->find($id);

        if ($this->selectedEmployee && $this->selectedEmployee->koperasiMember) {
            $member = $this->selectedEmployee->koperasiMember;
            $this->saldoSukarela = $member->saldo_simpanan_sukarela;
            $this->saldoLain     = $member->saldo_simpanan_lain_lain;
            $this->saldoShu      = $member->saldo_shu;
        } else {
            $this->saldoSukarela = 0;
            $this->saldoLain = 0;
            $this->saldoShu = 0;
        }

        if ($this->selectedEmployee && $this->selectedEmployee->nama_bank) {
            $this->namaBank    = $this->selectedEmployee->nama_bank;
            $this->noRekening  = $this->selectedEmployee->no_rekening;
            $this->namaPemilik = $this->selectedEmployee->nama_pemilik_rekening;
            Flux::toast(text: 'Data rekening otomatis terisi dari profil anggota.', variant: 'success');
        } else {
            $this->namaPemilik  = $this->selectedEmployee?->nama_lengkap ?? '';
            $this->namaBank     = '';
            $this->noRekening   = '';
        }
    }

    public function clearEmployee()
    {
        $this->employee_id      = '';
        $this->employeeSearch   = '';
        $this->selectedEmployee = null;
        $this->saldoSukarela = 0;
        $this->saldoLain = 0;
        $this->saldoShu = 0;
        
        $this->tarikSukarela = false;
        $this->tarikLain = false;
        $this->tarikShu = false;
    }

    public function getValidationRules()
    {
        $rules = [
            'employee_id' => 'required',
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
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'nominalSukarela.max' => 'Nominal melebihi saldo sukarela anggota.',
            'nominalLain.max' => 'Nominal melebihi saldo lain-lain anggota.',
            'nominalShu.max' => 'Nominal melebihi saldo SHU anggota.',
            'nominalSukarela.min' => 'Nominal minimal Rp 1.',
            'nominalLain.min' => 'Nominal minimal Rp 1.',
            'nominalShu.min' => 'Nominal minimal Rp 1.',
            'nominalSukarela.required' => 'Nominal wajib diisi.',
            'nominalLain.required' => 'Nominal wajib diisi.',
            'nominalShu.required' => 'Nominal wajib diisi.',
            'namaBank.required' => 'Nama bank wajib diisi.',
            'noRekening.required' => 'Nomor rekening wajib diisi.',
            'namaPemilik.required' => 'Nama pemilik rekening wajib diisi.',
        ];
    }

    public function submitPengajuanPenarikan()
    {
        $this->validate($this->getValidationRules(), $this->getValidationMessages());

        if ($this->totalNominalTarik <= 0) {
            Flux::toast(heading: 'Peringatan', text: 'Pilih minimal satu sumber saldo dengan nominal valid.', variant: 'warning');
            return;
        }

        DB::transaction(function () {
            $penarikan = PenarikanSaldo::create([
                'nomor_pengajuan'       => 'TARIK-' . strtoupper(uniqid()),
                'employee_id'           => $this->employee_id,
                'total_penarikan'       => $this->totalNominalTarik,
                'no_rekening'           => $this->noRekening,
                'nama_bank'             => $this->namaBank,
                'nama_pemilik_rekening' => $this->namaPemilik,
                'status'                => 'diajukan',
                'diajukan_oleh'         => auth('web')->user()->userable->npk,
                'diajukan_pada'         => now(),
                'catatan'               => $this->keteranganTarik,
            ]);

            if ($this->tarikSukarela && (int)$this->nominalSukarela > 0) {
                $penarikan->detailPenarikanSaldo()->create(['sumber_saldo' => 'simpanan_sukarela', 'nominal' => (int)$this->nominalSukarela]);
            }
            if ($this->tarikLain && (int)$this->nominalLain > 0) {
                $penarikan->detailPenarikanSaldo()->create(['sumber_saldo' => 'simpanan_lain_lain', 'nominal' => (int)$this->nominalLain]);
            }
            if ($this->tarikShu && (int)$this->nominalShu > 0) {
                $penarikan->detailPenarikanSaldo()->create(['sumber_saldo' => 'shu', 'nominal' => (int)$this->nominalShu]);
            }
        });

        Flux::toast(heading: 'Berhasil', text: 'Pengajuan penarikan saldo berhasil ditambahkan.', variant: 'success');
        Flux::modal('form-tambah')->close();
        unset($this->pengajuan);
    }

    /**
     * Proses penarikan (diajukan → diproses)
     */
    public function prosesPenarikan($id)
    {
        $penarikan = PenarikanSaldo::find($id);

        if (!$penarikan || $penarikan->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($penarikan) {
            $penarikan->update([
                'status'        => 'diproses',
                'diproses_oleh' => auth('web')->user()->userable->npk,
                'diproses_pada' => now(),
            ]);
        });

        Flux::toast(
            heading: 'Sedang Diproses',
            text: 'Penarikan sedang diproses. Lakukan transfer dana ke rekening anggota, lalu selesaikan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
        unset($this->pengajuan);
    }

    /**
     * Selesaikan penarikan (diproses → selesai)
     * Kurangi saldo anggota via MutasiSaldoMember
     */
    public function selesaikanPenarikan($id)
    {
        $penarikan = PenarikanSaldo::with(['detailPenarikanSaldo'])->find($id);
        $member = KoperasiMember::where('employee_id', $penarikan->employee_id)->first();

        if (!$penarikan || $penarikan->status !== 'diproses') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($penarikan, $member) {
            foreach ($penarikan->detailPenarikanSaldo as $detail) {
                $jenisSaldo = $detail->sumber_saldo;
                $namaJenisSaldo = 'saldo_' . $jenisSaldo;

                $saldoTerakhir = MutasiSaldoMember::where('employee_id', $penarikan->employee_id)
                    ->where('jenis_saldo', $jenisSaldo)
                    ->latest('id')
                    ->value('saldo_sesudah') ?? $member[$namaJenisSaldo];

                MutasiSaldoMember::create([
                    'employee_id'      => $penarikan->employee_id,
                    'jenis_saldo'      => $jenisSaldo,
                    'jenis_mutasi'     => 'debit',
                    'nominal'          => $detail->nominal,
                    'saldo_sebelum'    => $saldoTerakhir,
                    'saldo_sesudah'    => max(0, $saldoTerakhir - $detail->nominal),
                    'sumber_transaksi' => 'penarikan_saldo',
                    'referensi_id'     => $penarikan->id,
                    'keterangan'       => 'Penarikan Saldo — ' . $penarikan->nomor_pengajuan,
                    'diproses_oleh'    => auth('web')->user()->id,
                ]);

                $member->update([
                    $namaJenisSaldo => $saldoTerakhir - $detail->nominal,
                ]);
            }

            $penarikan->update([
                'status'            => 'selesai',
                'tanggal_pencairan' => now()->toDateString(),
            ]);
        });

        Flux::toast(
            heading: 'Penarikan Selesai',
            text: 'Dana berhasil ditransfer dan saldo anggota telah disesuaikan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
        unset($this->pengajuan);
    }

    public function tolak($id)
    {
        $penarikan = PenarikanSaldo::find($id);

        if (!$penarikan) {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->validate([
            'alasanPenolakan' => 'required|string|max:500'
        ], [
            'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.'
        ]);

        DB::transaction(function () use ($penarikan) {
            $penarikan->update([
                'status'           => 'ditolak',
                'alasan_penolakan' => $this->alasanPenolakan,
                'ditolak_oleh'     => auth('web')->user()->userable->npk,
                'ditolak_pada'     => now(),
            ]);
        });

        Flux::toast(
            heading: 'Pengajuan Ditolak',
            text: 'Pengajuan penarikan saldo berhasil ditolak.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
        $this->alasanPenolakan = '';
        unset($this->pengajuan);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Penarikan Saldo</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan penarikan saldo simpanan anggota.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openTambahForm">
            Tambah Pengajuan
        </flux:button>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan Penarikan</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK / nomor..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>No. Pengajuan</flux:table.column>
                    <flux:table.column>NPK & Nama Anggota</flux:table.column>
                    <flux:table.column>Total Nominal</flux:table.column>
                    <flux:table.column>Rekening Tujuan</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ $row->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->nomor_pengajuan }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->employee->nama_lengkap ?? 'A', 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-medium block">{{ $row->employee->nama_lengkap ?? 'Unknown' }}</span>
                                        <span class="text-xs text-zinc-400 block">NPK: {{ $row->employee->npk ?? '-' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($row->total_penarikan, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="font-medium block text-sm">{{ $row->nama_bank }}</span>
                                <span class="text-xs text-zinc-400 font-mono">{{ $row->no_rekening }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'diajukan')
                                    <flux:badge color="orange" size="sm" icon="clock">Menunggu</flux:badge>
                                @elseif($row->status === 'diproses')
                                    <flux:badge color="sky" size="sm" icon="clock">Diproses</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPengajuan({{ $row->id }})">Detail</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center text-gray-500 py-6">Tidak ada pengajuan penarikan saldo yang tertunda.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail -->
    <flux:modal name="detail-pengajuan" class="md:w-xl max-h-[90vh] overflow-y-auto">
        @if($selectedPengajuan)
            <div>
                <flux:heading size="lg">Detail Penarikan Saldo</flux:heading>
                <flux:text size="sm" class="mt-1">Periksa rincian penarikan sebelum melakukan transfer.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Info Anggota -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedPengajuan->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPengajuan->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">
                            NPK: {{ $selectedPengajuan->employee->npk ?? '-' }} • {{ $selectedPengajuan->employee->seksi ?? '-' }}
                        </flux:text>
                        <div class="mt-1">
                            @if($selectedPengajuan->status === 'diajukan')
                                <flux:badge color="orange" size="sm">Menunggu Proses</flux:badge>
                            @elseif($selectedPengajuan->status === 'diproses')
                                <flux:badge color="sky" size="sm">Sedang Diproses</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Rincian Saldo -->
                <div>
                    <flux:text class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">Rincian Saldo Ditarik</flux:text>
                    <div class="space-y-2">
                        @foreach($selectedPengajuan->detailPenarikanSaldo as $detail)
                            <div class="flex justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ ucwords(str_replace('_', ' ', $detail->sumber_saldo)) }}
                                </span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($detail->nominal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-between items-center mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40 rounded-lg">
                        <span class="font-bold text-blue-800 dark:text-blue-200">Total Penarikan</span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($selectedPengajuan->total_penarikan, 0, ',', '.') }}</span>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <!-- Rekening -->
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Bank Tujuan</flux:text>
                        <flux:text class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->nama_bank }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Nomor Rekening</flux:text>
                        <flux:text class="font-semibold font-mono text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->no_rekening }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Atas Nama</flux:text>
                        <flux:text class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->nama_pemilik_rekening }}</flux:text>
                    </div>
                </div>

                @if($selectedPengajuan->catatan)
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Keterangan</flux:text>
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                            <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">{{ $selectedPengajuan->catatan }}</flux:text>
                        </div>
                    </div>
                @endif

                <!-- Alasan Penolakan -->
                @if(in_array($selectedPengajuan->status, ['diajukan', 'diproses']))
                    <flux:field>
                        <flux:label>Alasan Penolakan <span class="text-zinc-400 text-xs">(wajib diisi jika menolak)</span></flux:label>
                        <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan..." rows="2" />
                        <flux:error name="alasanPenolakan" />
                    </flux:field>
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    @if(in_array($selectedPengajuan->status, ['diajukan', 'diproses']))
                        <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedPengajuan->id }})">Tolak</flux:button>
                    @endif

                    @if($selectedPengajuan->status === 'diajukan')
                        <flux:button variant="primary" color="sky" icon="check" wire:click="prosesPenarikan({{ $selectedPengajuan->id }})">Proses Penarikan</flux:button>
                    @elseif($selectedPengajuan->status === 'diproses')
                        <flux:button variant="primary" color="emerald" icon="banknotes" wire:click="selesaikanPenarikan({{ $selectedPengajuan->id }})">Transfer & Selesaikan</flux:button>
                    @endif
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">Memuat data pengajuan...</div>
        @endif
    </flux:modal>

    <!-- Modal Form Tambah -->
    <flux:modal name="form-tambah" class="md:w-[40rem] max-h-[90vh] overflow-y-auto">
        <div class="mb-5">
            <flux:heading size="lg">Tambah Pengajuan Penarikan Saldo</flux:heading>
            <flux:text size="sm" class="text-zinc-500">Ajukan penarikan saldo atas nama anggota. Pastikan identitas dan nominal sesuai.</flux:text>
        </div>

        <form wire:submit.prevent="submitPengajuanPenarikan" class="space-y-6">
            {{-- Bagian Pencarian & Pemilihan Karyawan --}}
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl space-y-4">
                <flux:heading size="sm">1. Pilih Anggota</flux:heading>
                
                @if(!$selectedEmployee)
                    <div class="relative">
                        <flux:input wire:model.live.debounce.300ms="employeeSearch" 
                                    icon="magnifying-glass" 
                                    placeholder="Ketik NPK atau Nama Karyawan..." />
                        
                        @if($employeeSearch && count($this->availableEmployees) > 0)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @foreach($this->availableEmployees as $emp)
                                    <div wire:click="selectEmployee({{ $emp->id }}, '{{ $emp->npk }} - {{ $emp->nama_lengkap }}')" 
                                         class="px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer">
                                        <div class="font-medium text-sm text-zinc-800 dark:text-zinc-200">{{ $emp->nama_lengkap }}</div>
                                        <div class="text-xs text-zinc-500">NPK: {{ $emp->npk }} | Seksi: {{ $emp->seksi ?? '-' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($employeeSearch)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg p-3 text-center text-sm text-zinc-500">
                                Karyawan tidak ditemukan.
                            </div>
                        @endif
                    </div>
                    @error('employee_id') <flux:error>{{ $message }}</flux:error> @enderror
                @else
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">
                                {{ substr($selectedEmployee->nama_lengkap, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-bold text-sm text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->nama_lengkap }}</div>
                                <div class="text-xs text-zinc-500">NPK: {{ $selectedEmployee->npk }}</div>
                            </div>
                        </div>
                        <flux:button size="sm" variant="subtle" wire:click="clearEmployee" icon="x-mark">Ganti</flux:button>
                    </div>

                    {{-- Info Saldo --}}
                    <div class="grid grid-cols-3 gap-3 mt-3">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg border border-emerald-100 dark:border-emerald-800/50">
                            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">Saldo Sukarela</div>
                            <div class="font-bold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($saldoSukarela, 0, ',', '.') }}</div>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800/50">
                            <div class="text-[10px] text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">Saldo Lain-lain</div>
                            <div class="font-bold text-blue-700 dark:text-blue-300">Rp {{ number_format($saldoLain, 0, ',', '.') }}</div>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800/50">
                            <div class="text-[10px] text-purple-600 dark:text-purple-400 uppercase tracking-wider mb-1">Saldo SHU</div>
                            <div class="font-bold text-purple-700 dark:text-purple-300">Rp {{ number_format($saldoShu, 0, ',', '.') }}</div>
                        </div>
                    </div>
                @endif
            </div>

            @if($selectedEmployee)
                {{-- Bagian Detail Penarikan --}}
                <div class="space-y-4">
                    <flux:heading size="sm">2. Sumber & Nominal Penarikan</flux:heading>
                    
                    <div class="space-y-3">
                        {{-- Sukarela --}}
                        <div class="p-3 rounded-xl border transition-colors {{ $tarikSukarela ? 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800' }}">
                            <flux:checkbox wire:model.live="tarikSukarela" label="Simpanan Sukarela (Maks: Rp {{ number_format($saldoSukarela, 0, ',', '.') }})" />
                            @if($tarikSukarela)
                                <div class="pl-7 mt-2">
                                    <flux:field>
                                        <flux:input wire:model.live.debounce.400ms="nominalSukarela" type="number" size="sm" placeholder="Nominal (Rp)" :max="$saldoSukarela" />
                                        <flux:error name="nominalSukarela" />
                                    </flux:field>
                                </div>
                            @endif
                        </div>

                        {{-- Lain-lain --}}
                        <div class="p-3 rounded-xl border transition-colors {{ $tarikLain ? 'bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-900' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800' }}">
                            <flux:checkbox wire:model.live="tarikLain" label="Simpanan Lain-lain (Maks: Rp {{ number_format($saldoLain, 0, ',', '.') }})" />
                            @if($tarikLain)
                                <div class="pl-7 mt-2">
                                    <flux:field>
                                        <flux:input wire:model.live.debounce.400ms="nominalLain" type="number" size="sm" placeholder="Nominal (Rp)" :max="$saldoLain" />
                                        <flux:error name="nominalLain" />
                                    </flux:field>
                                </div>
                            @endif
                        </div>

                        {{-- SHU --}}
                        <div class="p-3 rounded-xl border transition-colors {{ $tarikShu ? 'bg-purple-50 dark:bg-purple-950/20 border-purple-200 dark:border-purple-900' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800' }}">
                            <flux:checkbox wire:model.live="tarikShu" label="SHU (Maks: Rp {{ number_format($saldoShu, 0, ',', '.') }})" />
                            @if($tarikShu)
                                <div class="pl-7 mt-2">
                                    <flux:field>
                                        <flux:input wire:model.live.debounce.400ms="nominalShu" type="number" size="sm" placeholder="Nominal (Rp)" :max="$saldoShu" />
                                        <flux:error name="nominalShu" />
                                    </flux:field>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-between items-center p-4 bg-zinc-100 dark:bg-zinc-800/50 rounded-lg">
                        <span class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Total Nominal Penarikan</span>
                        <span class="text-xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($this->totalNominalTarik, 0, ',', '.') }}</span>
                    </div>

                    <flux:heading size="sm" class="pt-2">3. Tujuan Pencairan</flux:heading>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Nama Bank</flux:label>
                            <flux:select wire:model="namaBank" placeholder="Pilih Bank">
                                @foreach($this->daftarBank as $bank)
                                    <flux:select.option value="{{ $bank->nama_bank }}">{{ $bank->nama_bank }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="namaBank" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Nomor Rekening</flux:label>
                            <flux:input wire:model="noRekening" placeholder="Misal: 1234567890" />
                            <flux:error name="noRekening" />
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Nama Pemilik Rekening</flux:label>
                        <flux:input wire:model="namaPemilik" placeholder="Atas nama di buku tabungan" />
                        <flux:error name="namaPemilik" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Catatan / Keterangan (Opsional)</flux:label>
                        <flux:textarea wire:model="keteranganTarik" rows="2" placeholder="Tujuan penarikan..." />
                    </flux:field>
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" :disabled="!$selectedEmployee">Simpan Pengajuan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
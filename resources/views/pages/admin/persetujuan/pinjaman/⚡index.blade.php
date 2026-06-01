<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pinjaman;
use App\Models\Employee;
use App\Models\NamaBank;
use App\Models\TagihanPayrollEmployee;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Pinjaman'])] class extends Component
{
    use WithPagination;

    // ── Search ──────────────────────────────────────────────────
    public string $search = '';
    public int $perPage = 10;

    // ── Detail modal state ──────────────────────────────────────
    public $selectedPinjaman   = null;
    public string $alasanPenolakan = '';

    // ── Form Tambah ──────────────────────────────
    public bool $showTambahForm = false;
    
    // Pilih Karyawan
    public $employee_id      = '';
    public $employeeSearch   = '';
    public $selectedEmployee = null;

    // ── PINJAMAN FORM ──────────────────────────────────────────
    public $jenis_pinjaman          = 'qard';
    public $nominal_pengajuan_p     = '';
    public $nominal_disetujui_p     = '';
    public $tenor_bulan_p           = 12;
    public $nama_bank_p             = '';
    public $no_rekening_p           = '';
    public $nama_pemilik_rekening_p = '';
    public $catatan_p               = '';

    // ══════════════════════════════════════════════════════════
    // LIFECYCLE
    // ══════════════════════════════════════════════════════════

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedJenisPinjaman($value)
    {
        $this->tenor_bulan_p = $value === 'bon' ? 1 : 12;
    }

    // ══════════════════════════════════════════════════════════
    // COMPUTED
    // ══════════════════════════════════════════════════════════

    #[Computed]
    public function pengajuanPinjaman()
    {
        $query = Pinjaman::with(['employee'])
                ->whereIn('status', ['diajukan', 'diproses'])
                ->orderBy('updated_at', 'DESC');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('nomor_pengajuan', 'like', '%'.$this->search.'%')
                  ->orWhere('jenis_pinjaman', 'like', '%'.$this->search.'%')
                  ->orWhereHas('employee', function($eq) {
                      $eq->where('nama_lengkap', 'like', '%'.$this->search.'%')
                         ->orWhere('npk', 'like', '%'.$this->search.'%');
                  });
            });
        }

        return $query->paginate($this->perPage);
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
    public function bankList()
    {
        $banks = NamaBank::orderBy('nama_bank')->pluck('nama_bank')->toArray();
        return empty($banks)
            ? ['BCA','BRI','BNI','BSI','BJB','BTN','Mandiri','Bank DKI','Bank Muamalat','Seabank','Permata']
            : $banks;
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
        
        $this->jenis_pinjaman = 'qard'; 
        $this->nominal_pengajuan_p = '';
        $this->nominal_disetujui_p = ''; 
        $this->tenor_bulan_p = 12;
        $this->nama_bank_p = ''; 
        $this->no_rekening_p = '';
        $this->nama_pemilik_rekening_p = ''; 
        $this->catatan_p = '';
        
        Flux::modal('form-tambah')->show();
    }

    public function selectEmployee($id, $label)
    {
        $this->employee_id      = $id;
        $this->employeeSearch   = $label;
        $this->selectedEmployee = Employee::with('koperasiMember')->find($id);

        if ($this->selectedEmployee && $this->selectedEmployee->nama_bank) {
            $this->nama_bank_p             = $this->selectedEmployee->nama_bank;
            $this->no_rekening_p           = $this->selectedEmployee->no_rekening;
            $this->nama_pemilik_rekening_p = $this->selectedEmployee->nama_pemilik_rekening;
            Flux::toast(text: 'Data rekening otomatis terisi dari profil anggota.', variant: 'success');
        } else {
            $this->nama_pemilik_rekening_p  = $this->selectedEmployee?->nama_lengkap ?? '';
        }
    }

    public function simpanPinjaman()
    {
        $this->validate([
            'employee_id'             => 'required|exists:employees,id',
            'jenis_pinjaman'          => 'required|in:qard,bon',
            'nominal_pengajuan_p'     => 'required|numeric|min:1',
            'tenor_bulan_p'           => 'required|integer|min:1',
            'nama_bank_p'             => 'required|string',
            'no_rekening_p'           => 'required|string',
            'nama_pemilik_rekening_p' => 'required|string',
        ], [
            'employee_id.required' => 'Pilih karyawan terlebih dahulu.',
        ]);

        if ($this->jenis_pinjaman === 'bon' && $this->tenor_bulan_p != 1) {
            $this->addError('tenor_bulan_p', 'Tenor Bon Sementara harus 1 bulan.'); return;
        }

        $nomDisetujui = $this->nominal_disetujui_p !== '' ? (float)$this->nominal_disetujui_p : (float)$this->nominal_pengajuan_p;
        $nomAngsuran  = $nomDisetujui / (int)$this->tenor_bulan_p;
        $userId = auth('web')->user()->id;

        Pinjaman::create([
            'nomor_pengajuan'       => 'PJ-'.date('YmdHis').'-'.rand(1000,9999),
            'employee_id'           => $this->employee_id,
            'jenis_pinjaman'        => $this->jenis_pinjaman,
            'nominal_pengajuan'     => $this->nominal_pengajuan_p,
            'nominal_disetujui'     => $nomDisetujui,
            'tenor_bulan'           => $this->tenor_bulan_p,
            'nominal_angsuran'      => $nomAngsuran,
            'no_rekening'           => $this->no_rekening_p,
            'nama_bank'             => $this->nama_bank_p,
            'nama_pemilik_rekening' => $this->nama_pemilik_rekening_p,
            'status'                => 'diajukan',
            'catatan'               => $this->catatan_p ?: null,
            'diajukan_oleh'         => $userId,
            'diajukan_pada'         => now(),
        ]);

        Flux::toast(heading: 'Berhasil', text: 'Pengajuan pinjaman berhasil ditambahkan.', variant: 'success');
        Flux::modal('form-tambah')->close();
        unset($this->pengajuanPinjaman);
    }

    // ══════════════════════════════════════════════════════════
    // DETAIL & APPROVAL: PINJAMAN
    // ══════════════════════════════════════════════════════════

    public function detailPinjaman($id)
    {
        $this->selectedPinjaman   = Pinjaman::with('employee')->find($id);
        $this->alasanPenolakan    = '';
        Flux::modal('detail-pengajuan')->show();
    }

    public function prosesPinjaman($id)
    {
        $pinjaman = Pinjaman::find($id);
        if (!$pinjaman || $pinjaman->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Status tidak valid.', variant: 'danger'); return;
        }
        DB::transaction(fn() => $pinjaman->update([
            'status'        => 'diproses',
            'diproses_oleh' => auth('web')->user()->id,
            'diproses_pada' => now(),
        ]));
        Flux::toast(heading: 'Sedang Diproses', text: 'Pengajuan sedang diproses.', variant: 'success');
        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
        unset($this->pengajuanPinjaman);
    }

    public function selesaikanPengajuan($id)
    {
        $pinjaman = Pinjaman::find($id);
        if (!$pinjaman || $pinjaman->status !== 'diproses') {
            Flux::toast(heading: 'Error', text: 'Status tidak valid.', variant: 'danger'); return;
        }
        DB::transaction(function () use ($pinjaman) {
            $nominal     = (float)$pinjaman->nominal_pengajuan;
            $tenor       = (int)$pinjaman->tenor_bulan;
            $nomAngsuran = $nominal / $tenor;

            $pinjaman->update([
                'status'            => 'berjalan',
                'nominal_disetujui' => $nominal,
                'nominal_angsuran'  => $nomAngsuran,
                'tanggal_pencairan' => now()->toDateString(),
            ]);

            TagihanPayrollEmployee::create([
                'employee_id'           => $pinjaman->employee_id,
                'jenis_tagihan'         => 'pinjaman',
                'tagihanable_type'      => Pinjaman::class,
                'tagihanable_id'        => $pinjaman->id,
                'periode_bulan'         => now()->format('m'),
                'periode_tahun'         => now()->format('Y'),
                'periode_payroll_bulan' => now()->addMonth()->format('m'),
                'periode_payroll_tahun' => now()->addMonth()->format('Y'),
                'nominal'               => $nomAngsuran,
                'status'                => 'pending',
                'keterangan'            => 'Angsuran Pinjaman '.$pinjaman->nomor_pengajuan,
            ]);
        });
        
        Flux::toast(heading: 'Cicilan Aktif', text: 'Pinjaman aktif dan cicilan pertama dibuat.', variant: 'success');
        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
        unset($this->pengajuanPinjaman);
    }

    public function tolakPinjaman($id)
    {
        $pinjaman = Pinjaman::find($id);
        if (!$pinjaman) { Flux::toast(heading: 'Error', text: 'Data tidak ditemukan.', variant: 'danger'); return; }
        $this->validate(['alasanPenolakan' => 'required|string|max:500'], ['alasanPenolakan.required' => 'Alasan penolakan wajib diisi.']);
        DB::transaction(fn() => $pinjaman->update([
            'status'           => 'ditolak',
            'alasan_penolakan' => $this->alasanPenolakan,
            'ditolak_oleh'     => auth('web')->user()->id,
            'ditolak_pada'     => now(),
        ]));
        Flux::toast(heading: 'Ditolak', text: 'Pengajuan pinjaman ditolak.', variant: 'success');
        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null; $this->alasanPenolakan = '';
        unset($this->pengajuanPinjaman);
    }
};
?>

<div>
    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Pinjaman</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan pinjaman anggota.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openTambahForm">
            Tambah Pengajuan
        </flux:button>
    </div>

    <flux:separator variant="subtle" />

    <div class="mt-5">
        <flux:card class="mt-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                <flux:heading size="lg" level="2">
                    Daftar Pengajuan Pinjaman
                </flux:heading>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    size="sm"
                    class="max-w-64 w-full"
                    placeholder="Cari nama / NPK / nomor..."
                    icon="magnifying-glass"
                />
            </div>

            <flux:separator variant="subtle" class="mb-2" />

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->pengajuanPinjaman">
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>No. Pengajuan</flux:table.column>
                        <flux:table.column>NPK & Nama Anggota</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                        <flux:table.column>Tenor</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->pengajuanPinjaman as $row)
                            <flux:table.row :key="$row->id">
                                <flux:table.cell class="text-xs text-zinc-500 whitespace-nowrap">
                                    {{ $row->diajukan_pada ? \Carbon\Carbon::parse($row->diajukan_pada)->format('d/m/Y') : $row->created_at?->format('d/m/Y') }}
                                </flux:table.cell>
                                <flux:table.cell class="font-semibold text-xs font-mono text-zinc-800 dark:text-white">
                                    {{ $row->nomor_pengajuan }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300 shrink-0">
                                            {{ substr($row->employee->nama_lengkap ?? 'A', 0, 1) }}
                                        </div>
                                        <div>
                                            <span class="font-medium block text-sm">{{ $row->employee->nama_lengkap ?? '-' }}</span>
                                            <span class="text-xs text-zinc-400">NPK: {{ $row->employee->npk ?? '-' }}</span>
                                        </div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($row->jenis_pinjaman === 'qard')
                                        <flux:badge color="emerald" size="sm">Qard Hasan</flux:badge>
                                    @else
                                        <flux:badge color="amber" size="sm">Bon Sementara</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-bold text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                    Rp {{ number_format($row->nominal_pengajuan, 0, ',', '.') }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $row->tenor_bulan }} Bulan</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'diajukan')
                                        <flux:badge color="orange" size="sm" icon="clock">Menunggu</flux:badge>
                                    @elseif($row->status === 'diproses')
                                        <flux:badge color="sky" size="sm" icon="arrow-path">Diproses</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPinjaman({{ $row->id }})">Detail</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center py-10 text-zinc-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                                        <span>Tidak ada pengajuan pinjaman yang perlu disetujui.</span>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    </div>

    {{-- MODAL: DETAIL PENGAJUAN --}}
    <flux:modal name="detail-pengajuan" class="md:w-2xl max-h-[90vh] overflow-y-auto">
        @if($selectedPinjaman)
            <div>
                <flux:heading size="lg">Detail Pengajuan Pinjaman</flux:heading>
                <flux:text size="sm" class="mt-1">Tinjau informasi pengajuan pinjaman anggota.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-5">
                {{-- Info Anggota --}}
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ substr($selectedPinjaman->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPinjaman->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">NPK: {{ $selectedPinjaman->employee->npk ?? '-' }} • Seksi: {{ $selectedPinjaman->employee->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                {{-- Status Stepper --}}
                @php
                    $steps = ['diajukan'=>'Diajukan','diproses'=>'Diproses','berjalan'=>'Berjalan','lunas'=>'Lunas'];
                    $colors = ['diajukan'=>'bg-orange-500','diproses'=>'bg-sky-500','berjalan'=>'bg-emerald-500','lunas'=>'bg-green-600'];
                    $statusOrder = array_keys($steps);
                    $currentIdx = array_search($selectedPinjaman->status, $statusOrder);
                @endphp
                <div class="flex items-center text-xs font-semibold">
                    @foreach($steps as $key => $label)
                        @php $idx = array_search($key, $statusOrder); @endphp
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold {{ $currentIdx !== false && $idx <= $currentIdx ? ($colors[$key] ?? 'bg-zinc-400') : 'bg-zinc-300 dark:bg-zinc-700' }}">{{ $idx + 1 }}</div>
                            <span class="text-[10px] text-center {{ $currentIdx !== false && $idx <= $currentIdx ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400' }}">{{ $label }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 {{ $currentIdx !== false && $idx < $currentIdx ? 'bg-emerald-400' : 'bg-zinc-200 dark:bg-zinc-700' }} mb-3"></div>
                        @endif
                    @endforeach
                </div>

                {{-- Detail --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Nomor Pengajuan</flux:text>
                        <flux:text class="font-semibold text-zinc-900 dark:text-white text-sm">{{ $selectedPinjaman->nomor_pengajuan }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Jenis Pinjaman</flux:text>
                        @if($selectedPinjaman->jenis_pinjaman === 'qard')
                            <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Qard Hasan</span>
                        @else
                            <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Bon Sementara</span>
                        @endif
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Nominal Diajukan</flux:text>
                        <flux:text class="text-lg font-bold text-zinc-900 dark:text-white">Rp {{ number_format($selectedPinjaman->nominal_pengajuan, 0, ',', '.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Tenor</flux:text>
                        <flux:text class="text-lg font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedPinjaman->tenor_bulan }} Bulan</flux:text>
                    </div>
                    <div class="col-span-2 p-3 rounded-lg border text-xs {{ $selectedPinjaman->jenis_pinjaman === 'bon' ? 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/20 dark:border-amber-900/40 dark:text-amber-300' : 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-900/40 dark:text-emerald-300' }}">
                        @if($selectedPinjaman->jenis_pinjaman === 'bon')
                            <strong>Penting:</strong> Bon Sementara dipotong penuh pada siklus penggajian berikutnya.
                        @else
                            <strong>Estimasi Angsuran:</strong> Rp {{ number_format($selectedPinjaman->nominal_pengajuan / max(1,$selectedPinjaman->tenor_bulan), 0, ',', '.') }} / bulan
                        @endif
                    </div>
                </div>

                {{-- Rekening --}}
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rekening Pencairan</flux:heading>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div><span class="text-xs text-zinc-400 block">Bank</span><span class="font-semibold">{{ $selectedPinjaman->nama_bank }}</span></div>
                        <div><span class="text-xs text-zinc-400 block">No. Rekening</span><span class="font-semibold font-mono">{{ $selectedPinjaman->no_rekening }}</span></div>
                        <div><span class="text-xs text-zinc-400 block">Atas Nama</span><span class="font-semibold">{{ $selectedPinjaman->nama_pemilik_rekening }}</span></div>
                    </div>
                </div>

                @if($selectedPinjaman->catatan)
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-100 text-sm text-blue-700 dark:text-blue-300">
                        <strong>Catatan:</strong> {{ $selectedPinjaman->catatan }}
                    </div>
                @endif

                <flux:separator variant="subtle" />

                {{-- Actions --}}
                <div class="space-y-4">
                    @if(in_array($selectedPinjaman->status, ['diajukan', 'diproses']))
                        <flux:field>
                            <flux:label>Alasan Penolakan <span class="text-zinc-400 text-xs">(wajib diisi jika menolak)</span></flux:label>
                            <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan di sini..." rows="2" />
                            <flux:error name="alasanPenolakan" />
                        </flux:field>
                    @endif
                    <div class="flex justify-end gap-3 pt-2 flex-wrap">
                        @if(in_array($selectedPinjaman->status, ['diajukan', 'diproses']))
                            <flux:button variant="danger" icon="x-mark" wire:click="tolakPinjaman({{ $selectedPinjaman->id }})">Tolak</flux:button>
                        @endif
                        @if($selectedPinjaman->status === 'diajukan')
                            <flux:button variant="primary" color="sky" icon="check" wire:click="prosesPinjaman({{ $selectedPinjaman->id }})">Proses Pengajuan</flux:button>
                        @elseif($selectedPinjaman->status === 'diproses')
                            <flux:button variant="primary" color="emerald" icon="banknotes" wire:click="selesaikanPengajuan({{ $selectedPinjaman->id }})">Dana Cair → Selesaikan</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- MODAL: FORM TAMBAH PENGAJUAN --}}
    <flux:modal name="form-tambah" class="md:w-2xl max-h-[90vh] overflow-y-auto">
        <div class="mb-5">
            <flux:heading size="lg">Tambah Pengajuan Pinjaman</flux:heading>
            <flux:text size="sm" class="mt-1">Isi data pengajuan pinjaman untuk anggota.</flux:text>
        </div>

        {{-- Pilih Karyawan --}}
        <div class="border-b border-zinc-200 dark:border-zinc-700 pb-5 mb-5">
            <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Karyawan</flux:heading>
            <div x-data="{ open: false }" class="relative">
                <flux:field>
                    <flux:label>Cari Karyawan (NPK atau Nama)</flux:label>
                    <flux:input
                        type="text"
                        placeholder="Ketik NPK atau Nama..."
                        wire:model.live="employeeSearch"
                        x-on:focus="open = true" x-on:click="open = true" x-on:keydown.enter.prevent=""
                        icon="magnifying-glass"
                        autofocus="true"
                    />
                    <div x-show="open" x-on:click.outside="open = false"
                        class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-48 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700"
                        style="display:none;" x-transition>
                        @forelse($this->availableEmployees as $emp)
                            <div x-on:click="open = false; $wire.selectEmployee({{ $emp->id }}, '{{ $emp->npk }} - {{ $emp->nama_lengkap }}')"
                                class="px-4 py-2.5 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer text-sm flex justify-between">
                                <span class="font-medium">{{ $emp->nama_lengkap }}</span>
                                <span class="font-mono text-zinc-400 text-xs">{{ $emp->npk }}</span>
                            </div>
                        @empty
                            <div class="px-4 py-2.5 text-sm text-zinc-500">Karyawan tidak ditemukan.</div>
                        @endforelse
                    </div>
                    <flux:error name="employee_id" />
                </flux:field>
            </div>
            @if($selectedEmployee)
                <div class="mt-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-sm font-bold text-emerald-600 dark:text-emerald-400">
                            {{ substr($selectedEmployee->nama_lengkap, 0, 1) }}
                        </div>
                        <div class="text-sm">
                            <span class="font-semibold block text-zinc-900 dark:text-white">{{ $selectedEmployee->nama_lengkap }}</span>
                            <span class="text-zinc-400 text-xs">NPK: {{ $selectedEmployee->npk }} • {{ $selectedEmployee->seksi ?? '-' }}</span>
                        </div>
                        @if($selectedEmployee->koperasiMember)
                            <span class="ml-auto text-xs font-semibold text-green-600 dark:text-green-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Anggota</span>
                        @endif
                    </div>
                    
                    <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-between gap-4">
                        <div class="flex-1">
                            <span class="text-[10px] uppercase font-bold text-zinc-500 tracking-wider">Sisa Tagihan</span>
                            <div class="text-sm font-bold text-red-600 dark:text-red-400 mt-0.5">Rp {{ number_format($selectedEmployee->sisa_tagihan, 0, ',', '.') }}</div>
                        </div>
                        <div class="flex-1 text-right">
                            <span class="text-[10px] uppercase font-bold text-zinc-500 tracking-wider">Sisa Plafon</span>
                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">Rp {{ number_format($selectedEmployee->sisa_plafon, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <form wire:submit="simpanPinjaman" class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Jenis Pinjaman</flux:label>
                    <flux:select wire:model.live="jenis_pinjaman">
                        <flux:select.option value="qard">Qard Hasan</flux:select.option>
                        <flux:select.option value="bon">Bon Sementara</flux:select.option>
                    </flux:select>
                    <flux:error name="jenis_pinjaman" />
                </flux:field>
                <flux:field>
                    <flux:label>Tenor (Bulan)</flux:label>
                    <flux:input type="number" wire:model="tenor_bulan_p" placeholder="12" :disabled="$jenis_pinjaman === 'bon'" />
                    <flux:error name="tenor_bulan_p" />
                </flux:field>
                <flux:field class="col-span-full">
                    <flux:label>Nominal Pengajuan (Rp)</flux:label>
                    <flux:input type="number" wire:model="nominal_pengajuan_p" placeholder="5000000" />
                    <flux:error name="nominal_pengajuan_p" />
                </flux:field>
            </div>
            <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Rekening Pencairan</flux:heading>
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Bank</flux:label>
                    <flux:select wire:model="nama_bank_p" placeholder="Pilih...">
                        @foreach($this->bankList as $bank)<flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="nama_bank_p" />
                </flux:field>
                <flux:field>
                    <flux:label>No. Rekening</flux:label>
                    <flux:input type="text" wire:model="no_rekening_p" placeholder="7012398412" />
                    <flux:error name="no_rekening_p" />
                </flux:field>
                <flux:field class="col-span-full">
                    <flux:label>Atas Nama</flux:label>
                    <flux:input type="text" wire:model="nama_pemilik_rekening_p" />
                    <flux:error name="nama_pemilik_rekening_p" />
                </flux:field>
            </div>
            <flux:field>
                <flux:label>Catatan Internal</flux:label>
                <flux:textarea wire:model="catatan_p" rows="2" placeholder="Catatan tambahan..." />
            </flux:field>
            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" variant="subtle" x-on:click="$flux.modal('form-tambah').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Simpan Pinjaman</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
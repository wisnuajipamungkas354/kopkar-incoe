<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pembiayaan;
use App\Models\Employee;
use App\Models\NamaBank;
use App\Models\TagihanPayrollEmployee;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Pembiayaan Syariah'])] class extends Component
{
    use WithPagination;

    // ── Search ──────────────────────────────────────────────────
    public string $search = '';
    public int $perPage = 10;

    // ── Detail modal state ──────────────────────────────────────
    public $selectedPembiayaan = null;
    public string $alasanPenolakan = '';

    // ── Form Tambah ──────────────────────────────
    public bool $showTambahForm = false;
    
    // Pilih Karyawan
    public $employee_id      = '';
    public $employeeSearch   = '';
    public $selectedEmployee = null;

    // ── PEMBIAYAAN FORM ────────────────────────────────────────
    public $kategori_pembiayaan      = 'barang';
    public $tujuan_pembiayaan        = '';
    public $items_barang             = [['rincian' => '', 'harga' => '']];
    public $nominal_pembiayaan       = '';
    public $tenor_bulan_pb           = '';
    public $margin_persen            = 8.5;
    public $pencairan_dana_ke        = 'pihak_ketiga';
    public $nama_pihak_ketiga        = '';
    public $no_telp_pihak_ketiga     = '';
    public $alamat_pihak_ketiga      = '';
    public $nama_bank_pb             = '';
    public $no_rekening_pb           = '';
    public $nama_pemilik_rekening_pb = '';
    public $catatan_pb               = '';

    // ══════════════════════════════════════════════════════════
    // LIFECYCLE
    // ══════════════════════════════════════════════════════════

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ══════════════════════════════════════════════════════════
    // COMPUTED
    // ══════════════════════════════════════════════════════════

    #[Computed]
    public function pengajuanPembiayaan()
    {
        $query = Pembiayaan::with(['employee'])
                ->whereIn('status', ['diajukan', 'diproses'])
                ->orderBy('updated_at', 'DESC');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('nomor_pengajuan', 'like', '%'.$this->search.'%')
                  ->orWhere('kategori_pembiayaan', 'like', '%'.$this->search.'%')
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

    public function getSimulasiPembiayaan(): array
    {
        $nominal = $this->kategori_pembiayaan === 'barang'
            ? $this->getTotalBarang()
            : (float) $this->nominal_pembiayaan;
        $tenor  = (int) $this->tenor_bulan_pb;
        $margin = (float) $this->margin_persen;
        if ($nominal <= 0 || $tenor <= 0) return [];
        $totalMargin     = $nominal * ($margin / 100) * ($tenor / 12);
        $totalPembiayaan = $nominal + $totalMargin;
        return [
            'nominal'          => $nominal,
            'total_margin'     => $totalMargin,
            'total_pembiayaan' => $totalPembiayaan,
            'angsuran'         => $totalPembiayaan / $tenor,
        ];
    }

    public function getTotalBarang(): float
    {
        return collect($this->items_barang)->sum(fn($i) => (float)($i['harga'] ?? 0));
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
        
        $this->kategori_pembiayaan = 'barang'; 
        $this->tujuan_pembiayaan = '';
        $this->items_barang = [['rincian' => '', 'harga' => '']];
        $this->nominal_pembiayaan = ''; 
        $this->tenor_bulan_pb = '';
        $this->margin_persen = 8.5; 
        $this->pencairan_dana_ke = 'pihak_ketiga';
        $this->nama_pihak_ketiga = ''; 
        $this->no_telp_pihak_ketiga = '';
        $this->alamat_pihak_ketiga = ''; 
        $this->nama_bank_pb = '';
        $this->no_rekening_pb = ''; 
        $this->nama_pemilik_rekening_pb = '';
        $this->catatan_pb = '';
        
        Flux::modal('form-tambah')->show();
    }

    public function selectEmployee($id, $label)
    {
        $this->employee_id      = $id;
        $this->employeeSearch   = $label;
        $this->selectedEmployee = Employee::with('koperasiMember')->find($id);

        if ($this->selectedEmployee && $this->selectedEmployee->nama_bank) {
            $this->nama_bank_pb             = $this->selectedEmployee->nama_bank;
            $this->no_rekening_pb           = $this->selectedEmployee->no_rekening;
            $this->nama_pemilik_rekening_pb = $this->selectedEmployee->nama_pemilik_rekening;
            Flux::toast(text: 'Data rekening otomatis terisi dari profil anggota.', variant: 'success');
        } else {
            $this->nama_pemilik_rekening_pb = $this->selectedEmployee?->nama_lengkap ?? '';
        }
    }

    public function addItemBarang()
    {
        $this->items_barang[] = ['rincian' => '', 'harga' => ''];
    }

    public function removeItemBarang($index)
    {
        unset($this->items_barang[$index]);
        $this->items_barang = array_values($this->items_barang);
    }

    public function simpanPembiayaan()
    {
        $isBarang = $this->kategori_pembiayaan === 'barang';

        $this->validate([
            'employee_id'             => 'required|exists:employees,id',
            'kategori_pembiayaan'     => 'required|string',
            'tujuan_pembiayaan'       => 'required|string',
            'nominal_pembiayaan'      => $isBarang ? 'nullable' : 'required|numeric|min:1',
            'items_barang'            => $isBarang ? 'required|array|min:1' : 'nullable',
            'items_barang.*.rincian'  => $isBarang ? 'required|string|max:255' : 'nullable',
            'items_barang.*.harga'    => $isBarang ? 'required|numeric|min:1' : 'nullable',
            'tenor_bulan_pb'          => 'required|integer|min:1',
            'margin_persen'           => 'required|numeric|min:0',
            'pencairan_dana_ke'       => 'required|in:pihak_ketiga,anggota',
            'nama_pihak_ketiga'       => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:255',
            'no_telp_pihak_ketiga'    => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:20',
            'alamat_pihak_ketiga'     => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:500',
            'nama_bank_pb'            => 'required|string',
            'no_rekening_pb'          => 'required|string',
            'nama_pemilik_rekening_pb'=> 'required|string',
        ], ['employee_id.required' => 'Pilih karyawan terlebih dahulu.']);

        $nominal = $isBarang ? $this->getTotalBarang() : (float)$this->nominal_pembiayaan;
        $tenor   = (int)$this->tenor_bulan_pb;
        $margin  = (float)$this->margin_persen;

        if ($nominal <= 0) {
            if ($isBarang) {
                $this->addError('items_barang', 'Total harga rincian barang tidak boleh 0.');
            } else {
                $this->addError('nominal_pembiayaan', 'Nominal tidak boleh kosong.');
            }
            return;
        }

        $totalMargin     = $nominal * ($margin / 100) * ($tenor / 12);
        $totalPembiayaan = $nominal + $totalMargin;
        $nomAngsuran     = $totalPembiayaan / $tenor;
        $userId = auth('web')->user()->id;

        Pembiayaan::create([
            'nomor_pengajuan'        => 'PB-'.date('YmdHis').'-'.rand(1000,9999),
            'employee_id'            => $this->employee_id,
            'kategori_pembiayaan'    => $this->kategori_pembiayaan,
            'tujuan_pembiayaan'      => $this->tujuan_pembiayaan,
            'rincian_barang'         => $isBarang ? $this->items_barang : null,
            'nominal_pengajuan'      => $nominal,
            'nominal_disetujui'      => $nominal,
            'tenor_bulan'            => $tenor,
            'margin_persen'          => $margin,
            'total_margin'           => $totalMargin,
            'total_pembiayaan'       => $totalPembiayaan,
            'nominal_angsuran'       => $nomAngsuran,
            'pencairan_dana_ke'      => $this->pencairan_dana_ke,
            'nama_pihak_ketiga'      => $this->pencairan_dana_ke === 'pihak_ketiga' ? $this->nama_pihak_ketiga : null,
            'no_telp_pihak_ketiga'   => $this->pencairan_dana_ke === 'pihak_ketiga' ? $this->no_telp_pihak_ketiga : null,
            'alamat_pihak_ketiga'    => $this->pencairan_dana_ke === 'pihak_ketiga' ? $this->alamat_pihak_ketiga : null,
            'no_rekening'            => $this->no_rekening_pb,
            'nama_bank'              => $this->nama_bank_pb,
            'nama_pemilik_rekening'  => $this->nama_pemilik_rekening_pb,
            'status'                 => 'diajukan',
            'catatan'                => $this->catatan_pb ?: null,
            'diajukan_oleh'          => $userId,
            'diajukan_pada'          => now(),
        ]);

        Flux::toast(heading: 'Berhasil', text: 'Pengajuan pembiayaan berhasil ditambahkan.', variant: 'success');
        Flux::modal('form-tambah')->close();
        unset($this->pengajuanPembiayaan);
    }

    // ══════════════════════════════════════════════════════════
    // DETAIL & APPROVAL: PEMBIAYAAN
    // ══════════════════════════════════════════════════════════

    public function detailPembiayaan($id)
    {
        $this->selectedPembiayaan = Pembiayaan::with('employee')->find($id);
        $this->alasanPenolakan    = '';
        Flux::modal('detail-pengajuan')->show();
    }

    public function prosesPembiayaan($id)
    {
        $pembiayaan = Pembiayaan::find($id);
        if (!$pembiayaan || $pembiayaan->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Status tidak valid.', variant: 'danger'); return;
        }
        DB::transaction(fn() => $pembiayaan->update([
            'status'        => 'diproses',
            'diproses_oleh' => auth('web')->user()->id,
            'diproses_pada' => now(),
        ]));
        Flux::toast(heading: 'Sedang Diproses', text: 'Pengajuan pembiayaan sedang diproses.', variant: 'success');
        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
        unset($this->pengajuanPembiayaan);
    }

    public function selesaikanPengajuan($id)
    {
        $pembiayaan = Pembiayaan::find($id);
        if (!$pembiayaan || $pembiayaan->status !== 'diproses') {
            Flux::toast(heading: 'Error', text: 'Status tidak valid.', variant: 'danger'); return;
        }
        DB::transaction(function () use ($pembiayaan) {
            $nominal         = (float)$pembiayaan->nominal_pengajuan;
            $tenor           = (int)$pembiayaan->tenor_bulan;
            $marginPersen    = (float)($pembiayaan->margin_persen ?? 8.5);
            $totalMargin     = $nominal * ($marginPersen / 100) * ($tenor / 12);
            $totalPembiayaan = $nominal + $totalMargin;
            $nomAngsuran     = $totalPembiayaan / $tenor;
            $pembiayaan->update([
                'status'            => 'berjalan',
                'nominal_disetujui' => $nominal,
                'total_margin'      => $totalMargin,
                'total_pembiayaan'  => $totalPembiayaan,
                'nominal_angsuran'  => $nomAngsuran,
                'tanggal_pencairan' => now()->toDateString(),
            ]);
            TagihanPayrollEmployee::create([
                'employee_id'           => $pembiayaan->employee_id,
                'jenis_tagihan'         => 'pembiayaan',
                'tagihanable_type'      => Pembiayaan::class,
                'tagihanable_id'        => $pembiayaan->id,
                'periode_bulan'         => now()->format('m'),
                'periode_tahun'         => now()->format('Y'),
                'periode_payroll_bulan' => now()->addMonth()->format('m'),
                'periode_payroll_tahun' => now()->addMonth()->format('Y'),
                'nominal'               => $nomAngsuran,
                'status'                => 'pending',
                'keterangan'            => 'Angsuran Pembiayaan '.$pembiayaan->nomor_pengajuan,
            ]);
        });
        Flux::toast(heading: 'Angsuran Aktif', text: 'Angsuran pembiayaan pertama dibuat.', variant: 'success');
        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
        unset($this->pengajuanPembiayaan);
    }

    public function tolakPembiayaan($id)
    {
        $pembiayaan = Pembiayaan::find($id);
        if (!$pembiayaan) { Flux::toast(heading: 'Error', text: 'Data tidak ditemukan.', variant: 'danger'); return; }
        $this->validate(['alasanPenolakan' => 'required|string|max:500'], ['alasanPenolakan.required' => 'Alasan penolakan wajib diisi.']);
        DB::transaction(fn() => $pembiayaan->update([
            'status'           => 'ditolak',
            'alasan_penolakan' => $this->alasanPenolakan,
            'ditolak_oleh'     => auth('web')->user()->id,
            'ditolak_pada'     => now(),
        ]));
        Flux::toast(heading: 'Ditolak', text: 'Pengajuan pembiayaan ditolak.', variant: 'success');
        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null; $this->alasanPenolakan = '';
        unset($this->pengajuanPembiayaan);
    }
};
?>

<div>
    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Pembiayaan Syariah</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan pembiayaan syariah anggota.</flux:text>
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
                    Daftar Pengajuan Pembiayaan
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
                <flux:table :paginate="$this->pengajuanPembiayaan">
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>No. Pengajuan</flux:table.column>
                        <flux:table.column>NPK & Nama Anggota</flux:table.column>
                        <flux:table.column>Kategori</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                        <flux:table.column>Tenor</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->pengajuanPembiayaan as $row)
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
                                    @php
                                        $km = ['barang'=>['Pembelian Barang','blue'],'kendaraan'=>['Kendaraan','indigo'],'renovasi'=>['Renovasi','amber'],'pendidikan'=>['Pendidikan','purple'],'kesehatan'=>['Kesehatan','teal'],'lainnya'=>['Lainnya','zinc']];
                                        [$klabel, $kcolor] = $km[$row->kategori_pembiayaan] ?? [ucfirst($row->kategori_pembiayaan), 'zinc'];
                                    @endphp
                                    <flux:badge color="{{ $kcolor }}" size="sm">{{ $klabel }}</flux:badge>
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
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPembiayaan({{ $row->id }})">Detail</flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center py-10 text-zinc-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-8 h-8 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>
                                        <span>Tidak ada pengajuan pembiayaan yang perlu disetujui.</span>
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
        @if($selectedPembiayaan)
            <div>
                <flux:heading size="lg">Detail Pengajuan Pembiayaan</flux:heading>
                <flux:text size="sm" class="mt-1">Tinjau informasi pengajuan pembiayaan syariah anggota.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-5">
                {{-- Info Anggota --}}
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ substr($selectedPembiayaan->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPembiayaan->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">NPK: {{ $selectedPembiayaan->employee->npk ?? '-' }} • Seksi: {{ $selectedPembiayaan->employee->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                {{-- Status Stepper --}}
                @php
                    $steps2 = ['diajukan'=>'Diajukan','diproses'=>'Diproses','berjalan'=>'Berjalan','lunas'=>'Lunas'];
                    $colors2 = ['diajukan'=>'bg-orange-500','diproses'=>'bg-sky-500','berjalan'=>'bg-emerald-500','lunas'=>'bg-green-600'];
                    $statusOrder2 = array_keys($steps2);
                    $currentIdx2 = array_search($selectedPembiayaan->status, $statusOrder2);
                @endphp
                <div class="flex items-center text-xs font-semibold">
                    @foreach($steps2 as $key2 => $label2)
                        @php $idx2 = array_search($key2, $statusOrder2); @endphp
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold {{ $currentIdx2 !== false && $idx2 <= $currentIdx2 ? ($colors2[$key2] ?? 'bg-zinc-400') : 'bg-zinc-300 dark:bg-zinc-700' }}">{{ $idx2 + 1 }}</div>
                            <span class="text-[10px] text-center {{ $currentIdx2 !== false && $idx2 <= $currentIdx2 ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400' }}">{{ $label2 }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 {{ $currentIdx2 !== false && $idx2 < $currentIdx2 ? 'bg-emerald-400' : 'bg-zinc-200 dark:bg-zinc-700' }} mb-3"></div>
                        @endif
                    @endforeach
                </div>

                {{-- Detail --}}
                <div class="grid grid-cols-2 gap-4">
                    <div><flux:text class="text-xs font-medium text-zinc-400 mb-1">Nomor Pengajuan</flux:text><flux:text class="font-semibold text-zinc-900 dark:text-white">{{ $selectedPembiayaan->nomor_pengajuan }}</flux:text></div>
                    <div><flux:text class="text-xs font-medium text-zinc-400 mb-1">Kategori</flux:text><flux:text class="font-semibold text-zinc-900 dark:text-white">{{ ucfirst($selectedPembiayaan->kategori_pembiayaan) }}</flux:text></div>
                    <div class="col-span-2"><flux:text class="text-xs font-medium text-zinc-400 mb-1">Tujuan Pembiayaan</flux:text><flux:text class="font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedPembiayaan->tujuan_pembiayaan }}</flux:text></div>
                </div>

                {{-- Rincian Barang --}}
                @if($selectedPembiayaan->kategori_pembiayaan === 'barang' && !empty($selectedPembiayaan->rincian_barang))
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rincian Barang</flux:heading>
                        <div class="space-y-2">
                            @foreach($selectedPembiayaan->rincian_barang as $item)
                                <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $item['rincian'] ?? '-' }}</span>
                                    <span class="text-sm font-bold">Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Simulasi --}}
                <div class="p-4 bg-gradient-to-tr from-emerald-500/5 to-teal-500/5 dark:from-emerald-950/10 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                    <flux:heading size="sm" class="mb-3 text-emerald-800 dark:text-emerald-300">Simulasi Perhitungan</flux:heading>
                    <flux:separator />
                    @php
                        $simNominal      = (float) $selectedPembiayaan->nominal_pengajuan;
                        $simTenor        = (int) $selectedPembiayaan->tenor_bulan;
                        $simMargin       = (float) ($selectedPembiayaan->margin_persen ?? 8.5);
                        $simAngsuranPkok = $simNominal / max(1, $simTenor);
                        $simMarginBulan  = ($simNominal * ($simMargin / 100)) / 12;
                        $simAngsuranTot  = $simAngsuranPkok + $simMarginBulan;
                        $simTotalPemb    = $simAngsuranTot * $simTenor;
                    @endphp
                    <div class="space-y-2 text-sm mt-3">
                        <div class="flex justify-between"><span class="text-zinc-500">Nominal Pokok</span><span class="font-semibold">Rp {{ number_format($simNominal, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-500">Tenor</span><span class="font-semibold">{{ $simTenor }} Bulan</span></div>
                        <div class="flex justify-between border-t border-dashed border-zinc-200 dark:border-zinc-700 pt-2"><span class="text-zinc-500">Margin ({{ $simMargin }}%)</span><span class="font-semibold">Rp {{ number_format($simMarginBulan, 0, ',', '.') }}/bln</span></div>
                        <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-2 text-emerald-700 dark:text-emerald-400 font-bold"><span>Total Angsuran / Bulan</span><span class="text-base">Rp {{ number_format($simAngsuranTot, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between border-t-2 border-double border-zinc-300 dark:border-zinc-700 pt-2 font-bold"><span>Total Pembiayaan</span><span class="text-emerald-700 dark:text-emerald-300 text-lg">Rp {{ number_format($simTotalPemb, 0, ',', '.') }}</span></div>
                    </div>
                </div>

                {{-- Rekening --}}
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rekening Pencairan</flux:heading>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div><span class="text-xs text-zinc-400 block">Bank</span><span class="font-semibold">{{ $selectedPembiayaan->nama_bank }}</span></div>
                        <div><span class="text-xs text-zinc-400 block">No. Rekening</span><span class="font-semibold font-mono">{{ $selectedPembiayaan->no_rekening }}</span></div>
                        <div><span class="text-xs text-zinc-400 block">Atas Nama</span><span class="font-semibold">{{ $selectedPembiayaan->nama_pemilik_rekening }}</span></div>
                    </div>
                </div>

                @if(!empty($selectedPembiayaan->nama_pihak_ketiga))
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Pihak Ketiga</flux:heading>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div><span class="text-xs text-zinc-400 block">Nama</span><span class="font-semibold">{{ $selectedPembiayaan->nama_pihak_ketiga }}</span></div>
                            <div><span class="text-xs text-zinc-400 block">No. Telp</span><span class="font-semibold">{{ $selectedPembiayaan->no_telp_pihak_ketiga }}</span></div>
                            <div><span class="text-xs text-zinc-400 block">Alamat</span><span class="font-semibold">{{ $selectedPembiayaan->alamat_pihak_ketiga ?? '-' }}</span></div>
                        </div>
                    </div>
                @endif

                <flux:separator variant="subtle" />

                {{-- Actions --}}
                <div class="space-y-4">
                    @if(in_array($selectedPembiayaan->status, ['diajukan', 'diproses']))
                        <flux:field>
                            <flux:label>Alasan Penolakan <span class="text-zinc-400 text-xs">(wajib diisi jika menolak)</span></flux:label>
                            <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan di sini..." rows="2" />
                            <flux:error name="alasanPenolakan" />
                        </flux:field>
                    @endif
                    <div class="flex justify-end gap-3 pt-2 flex-wrap">
                        @if(in_array($selectedPembiayaan->status, ['diajukan', 'diproses']))
                            <flux:button variant="danger" icon="x-mark" wire:click="tolakPembiayaan({{ $selectedPembiayaan->id }})">Tolak</flux:button>
                        @endif
                        @if($selectedPembiayaan->status === 'diajukan')
                            <flux:button variant="primary" color="sky" icon="check" wire:click="prosesPembiayaan({{ $selectedPembiayaan->id }})">Proses Pengajuan</flux:button>
                        @elseif($selectedPembiayaan->status === 'diproses')
                            <flux:button variant="primary" color="emerald" icon="banknotes" wire:click="selesaikanPengajuan({{ $selectedPembiayaan->id }})">Dana Cair → Selesaikan</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- MODAL: FORM TAMBAH PENGAJUAN --}}
    <flux:modal name="form-tambah" class="md:w-3xl max-h-[90vh] overflow-y-auto">
        <div class="mb-5">
            <flux:heading size="lg">Tambah Pengajuan Pembiayaan</flux:heading>
            <flux:text size="sm" class="mt-1">Isi data pengajuan pembiayaan syariah untuk anggota.</flux:text>
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

        <form wire:submit="simpanPembiayaan" class="space-y-5">
            <div class="flex flex-col gap-3">
                <flux:field>
                    <flux:label>Kategori</flux:label>
                    <flux:select wire:model.live="kategori_pembiayaan">
                        <flux:select.option value="barang">Pembelian Barang</flux:select.option>
                        <flux:select.option value="kendaraan">Kendaraan</flux:select.option>
                        <flux:select.option value="renovasi">Renovasi Rumah</flux:select.option>
                        <flux:select.option value="pendidikan">Pendidikan</flux:select.option>
                        <flux:select.option value="kesehatan">Kesehatan</flux:select.option>
                        <flux:select.option value="lainnya">Lainnya</flux:select.option>
                    </flux:select>
                    <flux:error name="kategori_pembiayaan" />
                </flux:field>
                <flux:field>
                    <flux:label>Tujuan Pembiayaan</flux:label>
                    <flux:textarea wire:model="tujuan_pembiayaan" rows="2" placeholder="Jelaskan tujuan..." />
                    <flux:error name="tujuan_pembiayaan" />
                </flux:field>
            </div>

            @if($kategori_pembiayaan === 'barang')
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <flux:label>Rincian Barang</flux:label>
                        <flux:button type="button" wire:click="addItemBarang" size="sm" variant="outline" icon="plus">Tambah Item</flux:button>
                    </div>
                    @foreach($items_barang as $index => $item)
                        <div class="mb-2">
                            <div class="flex flex-row items-center gap-1">
                                <div class="grow flex flex-col gap-2 items-start">
                                    <flux:input type="text" wire:model.live.debounce.750ms="items_barang.{{ $index }}.rincian" placeholder="Nama barang" class="flex-1" />
                                    <flux:input type="number" wire:model.live.debounce.750ms="items_barang.{{ $index }}.harga" placeholder="Harga (Rp)" class="w-36" />
                                </div>
                                @if(count($items_barang) > 1)
                                    <flux:button type="button" wire:click="removeItemBarang({{ $index }})" variant="primary" size="sm" icon="trash" color="red" />
                                @endif
                            </div>
                            <div class="flex gap-2 mt-1">
                                <div class="flex-1"><flux:error name="items_barang.{{ $index }}.rincian" /></div>
                                <div class="w-36"><flux:error name="items_barang.{{ $index }}.harga" /></div>
                                @if(count($items_barang) > 1)<div class="w-8"></div>@endif
                            </div>
                        </div>
                    @endforeach
                    <div class="text-right text-sm mt-1">
                        Total: <strong>Rp {{ number_format($this->getTotalBarang(), 0, ',', '.') }}</strong>
                    </div>
                    <flux:error name="items_barang" />
                </div>
            @else
                <flux:field>
                    <flux:label>Nominal Pembiayaan (Rp)</flux:label>
                    <flux:input type="number" wire:model.live="nominal_pembiayaan" placeholder="15000000" />
                    <flux:error name="nominal_pembiayaan" />
                </flux:field>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Tenor (Bulan)</flux:label>
                    <flux:input type="number" wire:model.live.debounce.750ms="tenor_bulan_pb" placeholder="24" />
                    <flux:error name="tenor_bulan_pb" />
                </flux:field>
                <flux:field>
                    <flux:label>Margin (% / Tahun)</flux:label>
                    <flux:input type="number" wire:model.live.debounce.750ms="margin_persen" step="0.1" placeholder="8.5" />
                    <flux:error name="margin_persen" />
                </flux:field>
            </div>

            @php $sim = $this->getSimulasiPembiayaan(); @endphp
            @if(!empty($sim))
                <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-zinc-500 block text-xs">Total Pembiayaan</span><span class="font-bold">Rp {{ number_format($sim['total_pembiayaan'], 0, ',', '.') }}</span></div>
                    <div><span class="text-zinc-500 block text-xs">Angsuran / Bulan</span><span class="font-bold text-emerald-700 dark:text-emerald-400">Rp {{ number_format($sim['angsuran'], 0, ',', '.') }}</span></div>
                </div>
            @endif

            <flux:separator variant="subtle" />
            <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Pencairan Dana ke Pihak Ketiga</flux:heading>
            
            <div class="p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-700 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Nama Pihak Ketiga</flux:label>
                        <flux:input type="text" wire:model="nama_pihak_ketiga" placeholder="Toko/Vendor/Dealer..." />
                        <flux:error name="nama_pihak_ketiga" />
                    </flux:field>
                    <flux:field>
                        <flux:label>No. Telepon Pihak Ketiga</flux:label>
                        <flux:input type="text" wire:model="no_telp_pihak_ketiga" placeholder="08..." />
                        <flux:error name="no_telp_pihak_ketiga" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Alamat Pihak Ketiga</flux:label>
                    <flux:input type="text" wire:model="alamat_pihak_ketiga" placeholder="Alamat lengkap..." />
                    <flux:error name="alamat_pihak_ketiga" />
                </flux:field>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Bank</flux:label>
                    <flux:select wire:model="nama_bank_pb" placeholder="Pilih...">
                        @foreach($this->bankList as $bank)<flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:error name="nama_bank_pb" />
                </flux:field>
                <flux:field>
                    <flux:label>No. Rekening</flux:label>
                    <flux:input type="text" wire:model="no_rekening_pb" placeholder="7012398412" />
                    <flux:error name="no_rekening_pb" />
                </flux:field>
                <flux:field>
                    <flux:label>Atas Nama</flux:label>
                    <flux:input type="text" wire:model="nama_pemilik_rekening_pb" />
                    <flux:error name="nama_pemilik_rekening_pb" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Catatan Internal</flux:label>
                <flux:textarea wire:model="catatan_pb" rows="2" placeholder="Catatan tambahan..." />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" variant="subtle" x-on:click="$flux.modal('form-tambah').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Simpan Pembiayaan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
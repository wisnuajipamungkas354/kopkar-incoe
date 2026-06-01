<?php

use App\Models\PengajuanPerubahanPotonganPayroll;
use App\Models\PotonganPayrollEmployee;
use App\Models\DetailPayrollEmployee;
use App\Models\PengaturanPpobEmployee;
use App\Models\KategoriPpob;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Carbon\Carbon;
use Flux\Flux;

new #[Layout('layouts::anggota', ['title' => 'Pembayaran'])] class extends Component
{
    use WithPagination;

    public $mainTab    = 'ppob';    // ppob | lazis
    public $ppobTab    = 'rutin';   // rutin | tambahan
    public $lazisTab   = 'setoran'; // setoran | pengajuan | riwayat
    public $search     = '';

    public $userId;
    public $employeeId;

    // LAZIS stats
    public $totalLazis           = 0;
    public $nominalSaatIniZakat  = 0;
    public $nominalSaatIniInfaq  = 0;
    public $pengajuanPendingZakat = 0;
    public $pengajuanPendingInfaq = 0;

    // Form ubah setoran lazis (rutin)
    public $nominalBaru       = '';
    public $jenisLazisPilihan = 'zakat';

    // Form setoran tambahan lazis
    public $nominalTambahan       = '';
    public $jenisLazisTambahan    = 'zakat';
    public $metodeTambahan        = 'payroll'; // payroll | qris

    // Form PPOB
    public $kategoriPpob    = '';
    public $searchKategoriPpob = '';
    public $selectedKategoriPpob = null;
    public $idPelangganPpob = '';
    public $tipePpob        = 'rutin'; // rutin | tambahan

    // Konfirmasi hapus PPOB
    public $ppobHapusId   = null;
    public $ppobHapusNama = '';

    public function mount()
    {
        $user            = auth('web')->user();
        $this->userId    = $user->id;
        $this->employeeId = $user->userable->id;
        $this->refreshStats();
    }

    public function switchMain($tab)
    {
        $this->mainTab = $tab;
        $this->search  = '';
        $this->resetPage();
    }

    public function switchPpob($tab)
    {
        $this->ppobTab = $tab;
        $this->search  = '';
        $this->resetPage();
    }

    public function switchLazis($tab)
    {
        $this->lazisTab = $tab;
        $this->search   = '';
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function refreshStats()
    {
        $this->totalLazis = DetailPayrollEmployee::join('payroll_employee', 'detail_payroll_employee.payroll_employee_id', '=', 'payroll_employee.id')
            ->where('payroll_employee.employee_id', $this->employeeId)
            ->where('detail_payroll_employee.jenis_potongan', 'lazis')
            ->sum('detail_payroll_employee.nominal');

        $now = Carbon::now()->format('Y-m-d');

        $zakatPotongan = PotonganPayrollEmployee::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'zakat')
            ->where('employee_id', $this->employeeId)
            ->where('tanggal_mulai_berlaku', '<=', $now)
            ->latest()->first();
        $this->nominalSaatIniZakat = $zakatPotongan?->nominal ?? 0;

        $this->pengajuanPendingZakat = PengajuanPerubahanPotonganPayroll::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'zakat')
            ->where('employee_id', $this->employeeId)
            ->where('status', 'pending')->count();

        $infaqPotongan = PotonganPayrollEmployee::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'infaq_shodaqoh')
            ->where('employee_id', $this->employeeId)
            ->where('tanggal_mulai_berlaku', '<=', $now)
            ->latest()->first();
        $this->nominalSaatIniInfaq = $infaqPotongan?->nominal ?? 0;

        $this->pengajuanPendingInfaq = PengajuanPerubahanPotonganPayroll::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'infaq_shodaqoh')
            ->where('employee_id', $this->employeeId)
            ->where('status', 'pending')->count();
    }

    // ─── PPOB Actions ─────────────────────────────────────────────
    public function openTambahPpob($tipe = 'rutin')
    {
        $this->tipePpob             = $tipe;
        $this->kategoriPpob         = '';
        $this->searchKategoriPpob   = '';
        $this->selectedKategoriPpob = null;
        $this->idPelangganPpob      = '';
        Flux::modal('tambah-ppob')->show();
    }

    public function submitTambahPpob()
    {
        $this->validate([
            'kategoriPpob'    => 'required|string',
            'idPelangganPpob' => 'required|string|max:100',
        ], [
            'kategoriPpob.required'    => 'Kategori PPOB wajib dipilih.',
            'idPelangganPpob.required' => 'ID Pelanggan wajib diisi.',
        ]);

        // Cek duplikat: 1 karyawan tidak boleh punya 2 PPOB aktif dengan kategori + nomor pelanggan sama
        $exists = PengaturanPpobEmployee::where('employee_id', $this->employeeId)
            ->where('kategori_ppob', $this->kategoriPpob)
            ->where('nomor_pelanggan', $this->idPelangganPpob)
            ->where('aktif', true)
            ->exists();

        if ($exists) {
            $this->addError('idPelangganPpob', 'PPOB dengan kategori dan nomor pelanggan ini sudah terdaftar.');
            return;
        }

        PengaturanPpobEmployee::create([
            'employee_id'     => $this->employeeId,
            'kategori_ppob'   => $this->kategoriPpob,
            'nomor_pelanggan' => $this->idPelangganPpob,
            'aktif'           => true,
        ]);

        $this->reset(['kategoriPpob', 'idPelangganPpob', 'searchKategoriPpob', 'selectedKategoriPpob']);
        unset($this->daftarPpobRutin, $this->daftarPpobTambahan, $this->totalTagihanPpobRutin);
        Flux::modal('tambah-ppob')->close();
        Flux::modal('sukses-ppob')->show();
    }

    public function konfirmasiHapusPpob($id, $nama)
    {
        $this->ppobHapusId   = $id;
        $this->ppobHapusNama = $nama;
        Flux::modal('hapus-ppob')->show();
    }

    public function hapusPpob()
    {
        if ($this->ppobHapusId) {
            $ppob = PengaturanPpobEmployee::where('id', $this->ppobHapusId)
                ->where('employee_id', $this->employeeId)
                ->first();

            if ($ppob) {
                $ppob->update(['aktif' => false]);
                unset($this->daftarPpobRutin, $this->daftarPpobTambahan, $this->totalTagihanPpobRutin);
            }
        }

        $this->ppobHapusId   = null;
        $this->ppobHapusNama = '';
        Flux::modal('hapus-ppob')->close();
        Flux::modal('sukses-hapus-ppob')->show();
    }

    // ─── LAZIS Actions ────────────────────────────────────────────
    public function showSetoranTambahanLazisModal($kategori)
    {
        $this->jenisLazisTambahan = $kategori;
        Flux::modal('tambah-setoran-lazis')->show();
    }


    public function submitUbahSetoran()
    {
        $this->validate([
            'nominalBaru'       => 'required|numeric|min:0',
            'jenisLazisPilihan' => 'required|in:zakat,infaq_shodaqoh',
        ], [
            'nominalBaru.required' => 'Nominal baru wajib diisi.',
            'nominalBaru.numeric'  => 'Nominal baru harus berupa angka.',
            'nominalBaru.min'      => 'Nominal baru tidak boleh kurang dari Rp 0.',
        ]);

        $nominalLama = $this->jenisLazisPilihan === 'zakat'
            ? $this->nominalSaatIniZakat
            : $this->nominalSaatIniInfaq;

        PengajuanPerubahanPotonganPayroll::create([
            'employee_id'        => $this->employeeId,
            'jenis_potongan'     => 'lazis',
            'sub_jenis_potongan' => $this->jenisLazisPilihan,
            'nominal_lama'       => $nominalLama,
            'nominal_baru'       => (int) $this->nominalBaru,
            'status'             => 'pending',
            'tanggal_berlaku'    => Carbon::now()->addMonths(1)->firstOfMonth()->format('Y-m-d'),
            'diajukan_oleh'      => $this->userId,
            'tanggal_pengajuan'  => Carbon::now()->format('Y-m-d'),
        ]);

        $this->reset('nominalBaru');
        $this->refreshStats();
        Flux::modal('konfirmasi-ubah-setoran')->close();
        Flux::modal('sukses-ubah-setoran')->show();
    }

    public function submitSetoranTambahan()
    {
        $this->validate([
            'nominalTambahan'    => 'required|numeric|min:1000',
            'jenisLazisTambahan' => 'required|in:zakat,infaq_shodaqoh',
            'metodeTambahan'     => 'required|in:payroll,qris',
        ], [
            'nominalTambahan.required' => 'Nominal wajib diisi.',
            'nominalTambahan.min'      => 'Nominal minimal Rp 1.000.',
        ]);

        // TODO: Proses setoran tambahan (langsung, tanpa approval)
        $this->reset(['nominalTambahan', 'jenisLazisTambahan', 'metodeTambahan']);
        Flux::modal('tambah-setoran-lazis')->close();
        Flux::modal('sukses-setoran-tambahan')->show();
    }

    // ── Computed: PPOB ──────────────────────────────────────────
    #[Computed]
    public function daftarKategoriPpob()
    {
        return KategoriPpob::where('aktif', true)->orderBy('nama')->get();
    }

    #[Computed]
    public function availableKategoriPpob()
    {
        if (empty($this->searchKategoriPpob)) {
            return $this->daftarKategoriPpob;
        }

        $search = strtolower($this->searchKategoriPpob);
        return $this->daftarKategoriPpob->filter(function($k) use ($search) {
            return str_contains(strtolower($k->nama), $search) || str_contains(strtolower($k->kode), $search);
        });
    }

    public function selectKategoriPpob($kode, $nama)
    {
        $this->kategoriPpob = $kode;
        $this->selectedKategoriPpob = ['kode' => $kode, 'nama' => $nama];
        $this->searchKategoriPpob = '';
        $this->resetValidation('kategoriPpob');
    }

    public function removeSelectedKategoriPpob()
    {
        $this->kategoriPpob = '';
        $this->selectedKategoriPpob = null;
    }

    #[Computed]
    public function opsiKategoriPpob()
    {
        return $this->daftarKategoriPpob->map(function ($k) {
            return ['value' => $k->kode, 'label' => $k->nama];
        })->toArray();
    }

    #[Computed]
    public function daftarPpobRutin()
    {
        return PengaturanPpobEmployee::where('employee_id', $this->employeeId)
            ->when($this->search, fn($q) => $q->where(fn($x) => $x
                ->where('kategori_ppob', 'like', "%{$this->search}%")
                ->orWhere('nomor_pelanggan', 'like', "%{$this->search}%")
            ))
            ->orderBy('kategori_ppob')
            ->get()
            ->map(fn($r) => (object)[
                'id'            => $r->id,
                'kategori'      => $this->labelKategoriPpob($r->kategori_ppob),
                'kategori_raw'  => $r->kategori_ppob,
                'id_pelanggan'  => $r->nomor_pelanggan,
                'aktif'         => (bool) $r->aktif,
                'tanggal_daftar'=> $r->created_at->format('Y-m-d'),
                'catatan'       => $r->catatan,
            ]);
    }

    #[Computed]
    public function daftarPpobTambahan()
    {
        // Sementara kosong — fitur PPOB Tambahan (non-rutin) menggunakan alur yang sama
        // tapi tandai dengan catatan khusus. Untuk saat ini menampilkan yang nonaktif.
        return PengaturanPpobEmployee::where('employee_id', $this->employeeId)
            ->where('aktif', false)
            ->when($this->search, fn($q) => $q->where(fn($x) => $x
                ->where('kategori_ppob', 'like', "%{$this->search}%")
                ->orWhere('nomor_pelanggan', 'like', "%{$this->search}%")
            ))
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($r) => (object)[
                'id'            => $r->id,
                'kategori'      => $this->labelKategoriPpob($r->kategori_ppob),
                'kategori_raw'  => $r->kategori_ppob,
                'id_pelanggan'  => $r->nomor_pelanggan,
                'aktif'         => false,
                'tanggal_daftar'=> $r->created_at->format('Y-m-d'),
                'catatan'       => $r->catatan,
            ]);
    }

    #[Computed]
    public function totalTagihanPpobRutin()
    {
        return PengaturanPpobEmployee::where('employee_id', $this->employeeId)
            ->where('aktif', true)
            ->count();
    }

    // Helper label kategori PPOB
    private function labelKategoriPpob(string $k): string
    {
        $kategori = $this->daftarKategoriPpob->firstWhere('kode', $k);
        return $kategori ? $kategori->nama : 'Lain-lain';
    }

    // ── Computed: LAZIS ─────────────────────────────────────────
    #[Computed]
    public function setoranAktif()
    {
        return PotonganPayrollEmployee::where('employee_id', $this->employeeId)
            ->where('jenis_potongan', 'lazis')
            ->when($this->search, fn($q) => $q->where('sub_jenis_potongan', 'like', "%{$this->search}%"))
            ->latest()->get();
    }

    #[Computed]
    public function daftarPengajuan()
    {
        return PengajuanPerubahanPotonganPayroll::where('employee_id', $this->employeeId)
            ->where('jenis_potongan', 'lazis')
            ->when($this->search, fn($q) => $q->where(fn($x) => $x
                ->where('sub_jenis_potongan', 'like', "%{$this->search}%")
                ->orWhere('status', 'like', "%{$this->search}%")
            ))
            ->latest()->paginate(10);
    }

    #[Computed]
    public function riwayatLazis()
    {
        return DetailPayrollEmployee::select('detail_payroll_employee.*')
            ->join('payroll_employee', 'detail_payroll_employee.payroll_employee_id', '=', 'payroll_employee.id')
            ->where('payroll_employee.employee_id', $this->employeeId)
            ->where('detail_payroll_employee.jenis_potongan', 'lazis')
            ->when($this->search, fn($q) => $q->where(fn($x) => $x
                ->where('detail_payroll_employee.sub_jenis_potongan', 'like', "%{$this->search}%")
                ->orWhere('detail_payroll_employee.keterangan', 'like', "%{$this->search}%")
            ))
            ->latest('detail_payroll_employee.id')->paginate(10);
    }
};
?>

<div class="space-y-6">

    {{-- ── Page Header ─────────────────────────────────────────── --}}
    <div>
        <flux:heading size="xl" level="1">Pembayaran</flux:heading>
        <flux:subheading class="mt-1">Kelola pembayaran PPOB dan donasi LAZIS Anda.</flux:subheading>
    </div>

    <flux:separator variant="subtle" />

    {{-- ── Tab Utama ────────────────────────────────────────────── --}}
    <div class="flex border-b border-zinc-200 dark:border-zinc-700">
        <button wire:click="switchMain('ppob')"
            class="pb-3 px-1 mr-6 text-sm font-semibold border-b-2 transition-all flex items-center gap-2
                   {{ $mainTab === 'ppob'
                      ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                      : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
            <flux:icon name="bolt" variant="outline" class="w-4 h-4" />
            PPOB
        </button>
        <button wire:click="switchMain('lazis')"
            class="pb-3 px-1 mr-6 text-sm font-semibold border-b-2 transition-all flex items-center gap-2
                   {{ $mainTab === 'lazis'
                      ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                      : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
            <flux:icon name="heart" variant="outline" class="w-4 h-4" />
            LAZIS
        </button>
    </div>


    {{-- ══════════════════════════════════════════════════════════════
         TAB: PPOB
    ══════════════════════════════════════════════════════════════ --}}
    @if($mainTab === 'ppob')
        <div class="animate-fade-in-up space-y-6">

        {{-- Kartu Ringkasan PPOB --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Total Tagihan Rutin --}}
            <flux:card class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/40 rounded-xl shrink-0">
                    <flux:icon name="bolt" variant="solid" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:text class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Item PPOB Rutin Aktif</flux:text>
                    <div class="text-xl font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">
                        {{ $this->daftarPpobRutin->where('aktif', true)->count() }} Item
                    </div>
                </div>
            </flux:card>

            {{-- Item PPOB Tambahan --}}
            <flux:card class="flex items-center gap-4">
                <div class="p-3 bg-violet-100 dark:bg-violet-900/40 rounded-xl shrink-0">
                    <flux:icon name="plus-circle" variant="solid" class="w-6 h-6 text-violet-600 dark:text-violet-400" />
                </div>
                <div>
                    <flux:text class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">PPOB Tambahan (Pending Payroll)</flux:text>
                    <div class="text-xl font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">
                        {{ $this->daftarPpobTambahan->count() }} Item
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Sub-tab PPOB --}}
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            @foreach(['rutin' => 'PPOB Rutin', 'tambahan' => 'PPOB Tambahan'] as $key => $label)
                <button wire:click="switchPpob('{{ $key }}')"
                    class="pb-3 px-1 mr-6 text-sm font-medium border-b-2 transition-all
                           {{ $ppobTab === $key
                              ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                              : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Konten PPOB --}}
        <flux:card class="flex flex-col">
            {{-- Header tabel --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg" level="2">
                        {{ $ppobTab === 'rutin' ? 'Daftar PPOB Rutin' : 'Daftar PPOB Tambahan' }}
                    </flux:heading>
                    <flux:text class="text-xs text-zinc-400 mt-0.5">
                        @if($ppobTab === 'rutin')
                            Pembayaran rutin yang dipotong otomatis setiap bulan via payroll.
                        @else
                            Pembayaran by request, dipotong satu kali di payroll bulan berikutnya.
                        @endif
                    </flux:text>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <flux:input wire:model.live="search" size="sm" class="max-w-44" placeholder="Cari..." icon="magnifying-glass" />
                    <flux:button size="sm" variant="primary" icon="plus"
                        wire:click="openTambahPpob('{{ $ppobTab }}')">
                        Tambah
                    </flux:button>
                </div>
            </div>

            <flux:separator variant="subtle" class="mt-4 mb-3" />

            {{-- Daftar PPOB (mobile: card, desktop: tabel) --}}
            @php
                $daftarPpob = $ppobTab === 'rutin' ? $this->daftarPpobRutin : $this->daftarPpobTambahan;
            @endphp

            {{-- Mobile Card View --}}
            <div class="sm:hidden space-y-2">
                @forelse($daftarPpob as $row)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                    {{ $row->aktif ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                            @if(str_contains(strtolower($row->kategori), 'listrik') || str_contains(strtolower($row->kategori), 'token'))
                                <flux:icon name="bolt" variant="solid" class="w-5 h-5" />
                            @elseif(str_contains(strtolower($row->kategori), 'bpjs'))
                                <flux:icon name="shield-check" variant="solid" class="w-5 h-5" />
                            @elseif(str_contains(strtolower($row->kategori), 'wifi') || str_contains(strtolower($row->kategori), 'internet'))
                                <flux:icon name="wifi" variant="solid" class="w-5 h-5" />
                            @else
                                <flux:icon name="credit-card" variant="solid" class="w-5 h-5" />
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $row->kategori }}</span>
                                    <span class="block text-xs text-zinc-400 font-mono mt-0.5">{{ $row->id_pelanggan }}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if($row->aktif)
                                        <flux:badge color="green" size="sm">Aktif</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Non-Aktif</flux:badge>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-zinc-400">Daftar: {{ Carbon::parse($row->tanggal_daftar)->format('d/m/Y') }}</span>
                                @if($row->aktif)
                                    <button wire:click="konfirmasiHapusPpob({{ $row->id }}, '{{ $row->kategori }}')"
                                        class="text-xs text-red-500 hover:text-red-600 font-medium flex items-center gap-1">
                                        <flux:icon name="trash" class="w-3.5 h-3.5" />
                                        Nonaktifkan
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-zinc-400 py-12">
                        <flux:icon name="bolt" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                        <p class="text-sm font-medium">Belum ada PPOB {{ $ppobTab === 'rutin' ? 'Rutin' : 'Tambahan' }}.</p>
                        <p class="text-xs mt-1">Klik tombol <strong>Tambah</strong> untuk menambahkan.</p>
                    </div>
                @endforelse
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden sm:block overflow-x-auto">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Kategori</flux:table.column>
                        <flux:table.column>ID Pelanggan</flux:table.column>
                        <flux:table.column>Tgl. Daftar</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($daftarPpob as $row)
                            <flux:table.row :key="$row->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0
                                                    {{ $row->aktif ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                                            @if(str_contains(strtolower($row->kategori), 'listrik') || str_contains(strtolower($row->kategori), 'token'))
                                                <flux:icon name="bolt" variant="solid" class="w-4 h-4" />
                                            @elseif(str_contains(strtolower($row->kategori), 'bpjs'))
                                                <flux:icon name="shield-check" variant="solid" class="w-4 h-4" />
                                            @elseif(str_contains(strtolower($row->kategori), 'wifi') || str_contains(strtolower($row->kategori), 'internet'))
                                                <flux:icon name="wifi" variant="solid" class="w-4 h-4" />
                                            @else
                                                <flux:icon name="credit-card" variant="solid" class="w-4 h-4" />
                                            @endif
                                        </div>
                                        <span class="font-medium text-sm">{{ $row->kategori }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-sm text-zinc-500">{{ $row->id_pelanggan }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-400 text-sm">{{ Carbon::parse($row->tanggal_daftar)->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->aktif)
                                        <flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm" inset="top bottom">Non-Aktif</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($row->aktif)
                                        <flux:button size="xs" variant="ghost" icon="trash"
                                            wire:click="konfirmasiHapusPpob({{ $row->id }}, '{{ $row->kategori }}')"
                                            class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30">
                                            Nonaktifkan
                                        </flux:button>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="5" class="text-center text-zinc-400 py-10">
                                    <flux:icon name="bolt" class="w-8 h-8 mx-auto mb-2 opacity-20" />
                                    <p class="text-sm">Belum ada PPOB {{ $ppobTab === 'rutin' ? 'Rutin' : 'Tambahan' }}.</p>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>

        {{-- Info Box PPOB --}}
        @if($ppobTab === 'rutin')
            <div class="rounded-xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/60 dark:bg-blue-950/20 p-4 flex gap-3">
                <flux:icon name="information-circle" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" />
                <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <p class="font-semibold">Ketentuan PPOB Rutin</p>
                    <ul class="list-disc list-inside text-blue-600/80 dark:text-blue-400/80 space-y-0.5 text-xs">
                        <li>PPOB Rutin dipotong otomatis setiap bulan melalui payroll.</li>
                        <li>Perubahan / penambahan akan berlaku mulai payroll bulan berikutnya.</li>
                        <li>Anda dapat menonaktifkan PPOB Rutin kapan saja.</li>
                    </ul>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-violet-100 dark:border-violet-900/40 bg-violet-50/60 dark:bg-violet-950/20 p-4 flex gap-3">
                <flux:icon name="information-circle" class="w-5 h-5 text-violet-500 shrink-0 mt-0.5" />
                <div class="text-sm text-violet-700 dark:text-violet-300 space-y-1">
                    <p class="font-semibold">Ketentuan PPOB Tambahan</p>
                    <ul class="list-disc list-inside text-violet-600/80 dark:text-violet-400/80 space-y-0.5 text-xs">
                        <li>PPOB Tambahan bersifat tidak rutin, hanya by request.</li>
                        <li>Dipotong satu kali di payroll bulan berikutnya.</li>
                        <li>Dapat dinonaktifkan sebelum payroll diproses.</li>
                    </ul>
                </div>
            </div>
        @endif
        </div>

    @endif


    {{-- ══════════════════════════════════════════════════════════════
         TAB: LAZIS
    ══════════════════════════════════════════════════════════════ --}}
    @if($mainTab === 'lazis')
        <div class="animate-fade-in-up space-y-6">

        {{-- Info Kemitraan YAA --}}
        <div class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-xl border border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/60 dark:bg-emerald-950/20">
            <div class="w-32 h-12 shrink-0 bg-white dark:bg-zinc-800 p-2 rounded-lg shadow-sm flex items-center justify-center border border-zinc-200 dark:border-zinc-700">
                <img src="{{ asset('img/logo-yayasan-lazis-light.png') }}" x-show="$flux.appearance === 'light'" class="object-contain max-h-8" alt="Logo Yayasan">
                <img src="{{ asset('img/logo-yayasan-lazis-dark.png') }}"  x-show="$flux.appearance === 'dark'"  class="object-contain max-h-8" alt="Logo Yayasan">
            </div>
            <div class="text-center sm:text-left flex-1">
                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Kemitraan Lazis Yayasan Amaliah Astra (YAA)</p>
                <p class="text-xs text-emerald-700/80 dark:text-emerald-300/70 mt-0.5">Penyaluran Zakat, Infaq, dan Sedekah diproses secara transparan melalui kerjasama resmi dengan <strong>Yayasan Amaliah Astra</strong>.</p>
            </div>
        </div>

        {{-- Kartu Setoran Aktif --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Total Donasi --}}
            <flux:card class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl shrink-0">
                    <flux:icon name="heart" variant="solid" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:text class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Total Donasi</flux:text>
                    <div class="text-xl font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">Rp {{ number_format($totalLazis, 0, ',', '.') }}</div>
                </div>
            </flux:card>

            {{-- Zakat --}}
            <flux:card class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-teal-50 dark:bg-teal-950/40 rounded-lg text-teal-600 dark:text-teal-400">
                            <flux:icon name="hand-raised" variant="solid" class="w-4 h-4" />
                        </div>
                        <span class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">Zakat</span>
                    </div>
                    @if($pengajuanPendingZakat > 0)
                        <flux:badge color="orange" size="sm">{{ $pengajuanPendingZakat }} Pending</flux:badge>
                    @endif
                </div>
                <div>
                    <span class="text-xs text-zinc-400">Setoran bulanan</span>
                    <div class="text-lg font-bold text-zinc-800 dark:text-zinc-200">
                        Rp {{ number_format($nominalSaatIniZakat, 0, ',', '.') }}<span class="text-xs font-normal text-zinc-400">/bln</span>
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <flux:button size="xs" variant="ghost" icon="pencil-square"
                        x-on:click="$wire.set('jenisLazisPilihan', 'zakat'); $flux.modal('ubah-setoran').show()">
                        Ubah
                    </flux:button>
                    <flux:button size="xs" variant="ghost" icon="plus"
                        wire:click="showSetoranTambahanLazisModal('zakat')">
                        Tambahan
                    </flux:button>
                </div>
            </flux:card>

            {{-- Infaq & Shodaqoh --}}
            <flux:card class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-indigo-50 dark:bg-indigo-950/40 rounded-lg text-indigo-600 dark:text-indigo-400">
                            <flux:icon name="sparkles" variant="solid" class="w-4 h-4" />
                        </div>
                        <span class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">Infaq & Shodaqoh</span>
                    </div>
                    @if($pengajuanPendingInfaq > 0)
                        <flux:badge color="orange" size="sm">{{ $pengajuanPendingInfaq }} Pending</flux:badge>
                    @endif
                </div>
                <div>
                    <span class="text-xs text-zinc-400">Setoran bulanan</span>
                    <div class="text-lg font-bold text-zinc-800 dark:text-zinc-200">
                        Rp {{ number_format($nominalSaatIniInfaq, 0, ',', '.') }}<span class="text-xs font-normal text-zinc-400">/bln</span>
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <flux:button size="xs" variant="ghost" icon="pencil-square"
                        x-on:click="$wire.set('jenisLazisPilihan', 'infaq_shodaqoh'); $flux.modal('ubah-setoran').show()">
                        Ubah
                    </flux:button>
                    <flux:button size="xs" variant="ghost" icon="plus"
                        wire:click="showSetoranTambahanLazisModal('infaq_shodaqoh')">
                        Tambahan
                    </flux:button>
                </div>
            </flux:card>
        </div>

        {{-- Sub-tab LAZIS --}}
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            @php
                $lazisTabs = [
                    'setoran'   => 'Setoran Aktif',
                    'pengajuan' => 'Dalam Pengajuan',
                    'riwayat'   => 'Riwayat Transaksi',
                ];
            @endphp
            @foreach($lazisTabs as $key => $label)
                <button wire:click="switchLazis('{{ $key }}')"
                    class="pb-3 px-1 mr-6 text-sm font-medium border-b-2 transition-all
                           {{ $lazisTab === $key
                              ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
                              : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                    {{ $label }}
                    @if($key === 'pengajuan' && ($pengajuanPendingZakat + $pengajuanPendingInfaq) > 0)
                        <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-600 text-[10px] font-bold">
                            {{ $pengajuanPendingZakat + $pengajuanPendingInfaq }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Konten LAZIS --}}
        <flux:card class="flex flex-col">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg" level="2">{{ $lazisTabs[$lazisTab] }}</flux:heading>
                    @if($lazisTab === 'setoran')
                        <flux:text class="text-xs text-zinc-400 mt-0.5">Riwayat potongan payroll bulanan yang telah terdaftar.</flux:text>
                    @elseif($lazisTab === 'pengajuan')
                        <flux:text class="text-xs text-zinc-400 mt-0.5">Pengajuan perubahan setoran rutin yang menunggu persetujuan.</flux:text>
                    @else
                        <flux:text class="text-xs text-zinc-400 mt-0.5">Riwayat transaksi LAZIS yang telah diproses.</flux:text>
                    @endif
                </div>
                <flux:input wire:model.live="search" size="sm" class="max-w-44 shrink-0" placeholder="Cari..." icon="magnifying-glass" />
            </div>

            <flux:separator variant="subtle" class="mt-4 mb-3" />

            {{-- Tab: Setoran Aktif --}}
            @if($lazisTab === 'setoran')
                <div class="animate-fade-in-up">
                {{-- Mobile --}}
                <div class="sm:hidden space-y-2">
                    @forelse($this->setoranAktif as $row)
                        @php
                            $today     = Carbon::today()->format('Y-m-d');
                            $isExpired = $row->tanggal_selesai && $row->tanggal_selesai < $today;
                            $isPending = $row->tanggal_mulai_berlaku > $today;
                            $isZakat   = $row->sub_jenis_potongan === 'zakat';
                        @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                        {{ $isZakat ? 'bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400' }}">
                                <flux:icon name="{{ $isZakat ? 'hand-raised' : 'sparkles' }}" variant="solid" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                            {{ $isZakat ? 'Zakat' : 'Infaq & Shodaqoh' }}
                                        </span>
                                        <span class="block text-xs text-zinc-400 mt-0.5">Mulai {{ Carbon::parse($row->tanggal_mulai_berlaku)->translatedFormat('d F Y') }}</span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal, 0, ',', '.') }}<span class="text-xs font-normal text-zinc-400">/bln</span></div>
                                        @if($isExpired) <flux:badge color="zinc" size="sm">Non-Aktif</flux:badge>
                                        @elseif($isPending) <flux:badge color="orange" size="sm">Menunggu</flux:badge>
                                        @else <flux:badge color="green" size="sm">Aktif</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-400 py-12">
                            <flux:icon name="heart" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                            <p class="text-sm">Tidak ada setoran LAZIS aktif.</p>
                        </div>
                    @endforelse
                </div>
                {{-- Desktop --}}
                <flux:table class="hidden sm:table">
                    <flux:table.columns>
                        <flux:table.column>Program LAZIS</flux:table.column>
                        <flux:table.column>Nominal Bulanan</flux:table.column>
                        <flux:table.column>Mulai Berlaku</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->setoranAktif as $row)
                            @php
                                $today     = Carbon::today()->format('Y-m-d');
                                $isExpired = $row->tanggal_selesai && $row->tanggal_selesai < $today;
                                $isPending = $row->tanggal_mulai_berlaku > $today;
                                $isZakat   = $row->sub_jenis_potongan === 'zakat';
                            @endphp
                            <flux:table.row :key="'a-'.$row->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                                    {{ $isZakat ? 'bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400' }}">
                                            <flux:icon name="{{ $isZakat ? 'hand-raised' : 'sparkles' }}" variant="solid" class="w-3.5 h-3.5" />
                                        </div>
                                        <span class="font-medium text-sm">{{ $isZakat ? 'Zakat' : 'Infaq & Shodaqoh' }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="font-semibold">Rp {{ number_format($row->nominal, 0, ',', '.') }}<span class="text-xs font-normal text-zinc-400">/bln</span></flux:table.cell>
                                <flux:table.cell class="text-zinc-500 text-sm">{{ Carbon::parse($row->tanggal_mulai_berlaku)->translatedFormat('d F Y') }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($isExpired) <flux:badge color="zinc" size="sm" inset="top bottom">Non-Aktif</flux:badge>
                                    @elseif($isPending) <flux:badge color="orange" size="sm" inset="top bottom">Menunggu Berlaku</flux:badge>
                                    @else <flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="text-center text-zinc-400 py-10">
                                    Tidak ada setoran LAZIS aktif saat ini.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                </div>

            {{-- Tab: Dalam Pengajuan --}}
            @elseif($lazisTab === 'pengajuan')
                <div class="animate-fade-in-up">
                {{-- Mobile --}}
                <div class="sm:hidden space-y-2">
                    @forelse($this->daftarPengajuan as $row)
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                        {{ $row->sub_jenis_potongan === 'zakat' ? 'Zakat' : 'Infaq & Shodaqoh' }}
                                    </span>
                                    <span class="text-xs text-zinc-400">{{ Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</span>
                                </div>
                                @if($row->status === 'pending') <flux:badge color="orange" size="sm">Pending</flux:badge>
                                @elseif($row->status === 'disetujui') <flux:badge color="green" size="sm">Disetujui</flux:badge>
                                @else <flux:badge color="red" size="sm">Ditolak</flux:badge>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <div>
                                    <div class="text-[10px] text-zinc-400">Nominal Lama</div>
                                    <div class="font-medium text-zinc-400 line-through">Rp {{ number_format($row->nominal_lama, 0, ',', '.') }}</div>
                                </div>
                                <flux:icon name="arrow-right" class="w-4 h-4 text-zinc-300 shrink-0" />
                                <div>
                                    <div class="text-[10px] text-zinc-400">Nominal Baru</div>
                                    <div class="font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($row->nominal_baru, 0, ',', '.') }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-zinc-400 mt-1.5">Berlaku: {{ Carbon::parse($row->tanggal_berlaku)->translatedFormat('F Y') }}</div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-400 py-12">
                            <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                            <p class="text-sm">Tidak ada pengajuan perubahan setoran.</p>
                        </div>
                    @endforelse
                </div>
                {{-- Desktop --}}
                <flux:table class="hidden sm:table">
                    <flux:table.columns>
                        <flux:table.column>Tgl. Pengajuan</flux:table.column>
                        <flux:table.column>Program LAZIS</flux:table.column>
                        <flux:table.column>Nominal Lama</flux:table.column>
                        <flux:table.column>Nominal Baru</flux:table.column>
                        <flux:table.column>Berlaku Mulai</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->daftarPengajuan as $row)
                            <flux:table.row :key="'p-'.$row->id">
                                <flux:table.cell class="text-zinc-400 text-sm">{{ Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="font-medium text-sm">{{ $row->sub_jenis_potongan === 'zakat' ? 'Zakat' : 'Infaq & Shodaqoh' }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-400 line-through text-sm">Rp {{ number_format($row->nominal_lama, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="font-semibold">Rp {{ number_format($row->nominal_baru, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500 text-sm">{{ Carbon::parse($row->tanggal_berlaku)->translatedFormat('F Y') }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($row->status === 'pending') <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                    @elseif($row->status === 'disetujui') <flux:badge color="green" size="sm" inset="top bottom">Disetujui</flux:badge>
                                    @else <flux:badge color="red" size="sm" inset="top bottom">Ditolak</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center text-zinc-400 py-10">
                                    Tidak ada pengajuan perubahan setoran.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                <div class="mt-4">{{ $this->daftarPengajuan->links() }}</div>
                </div>

            {{-- Tab: Riwayat --}}
            @else
                <div class="animate-fade-in-up">
                {{-- Mobile --}}
                <div class="sm:hidden space-y-2">
                    @forelse($this->riwayatLazis as $row)
                        @php $isZakat = $row->sub_jenis_potongan === 'zakat'; @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900/60 border border-zinc-100 dark:border-zinc-800">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                        {{ $isZakat ? 'bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400' }}">
                                <flux:icon name="{{ $isZakat ? 'hand-raised' : 'sparkles' }}" variant="solid" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start gap-2">
                                    <div>
                                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                            {{ $isZakat ? 'Zakat' : 'Infaq & Shodaqoh' }}
                                        </span>
                                        <span class="block text-xs text-zinc-400 font-mono">LZS-{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200 shrink-0">Rp {{ number_format($row->nominal, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-zinc-400">{{ $row->created_at->format('d/m/Y') }}</span>
                                    <flux:badge color="blue" size="sm">Payroll</flux:badge>
                                    <flux:badge color="green" size="sm">Berhasil</flux:badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-400 py-12">
                            <flux:icon name="inbox" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                            <p class="text-sm">Belum ada riwayat transaksi LAZIS.</p>
                        </div>
                    @endforelse
                </div>
                {{-- Desktop --}}
                <flux:table class="hidden sm:table">
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>No. Transaksi</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Metode</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse($this->riwayatLazis as $row)
                            @php $isZakat = $row->sub_jenis_potongan === 'zakat'; @endphp
                            <flux:table.row :key="'r-'.$row->id">
                                <flux:table.cell class="text-zinc-400 text-sm">{{ $row->created_at->format('d/m/Y') }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-xs text-zinc-400">LZS-{{ str_pad($row->id, 5, '0', STR_PAD_LEFT) }}</flux:table.cell>
                                <flux:table.cell>
                                    @if($isZakat)
                                        <flux:badge color="teal" size="sm" inset="top bottom">Zakat</flux:badge>
                                    @else
                                        <flux:badge color="indigo" size="sm" inset="top bottom">Infaq & Shodaqoh</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell><flux:badge color="blue" size="sm" inset="top bottom">Payroll</flux:badge></flux:table.cell>
                                <flux:table.cell class="font-semibold">Rp {{ number_format($row->nominal, 0, ',', '.') }}</flux:table.cell>
                                <flux:table.cell><flux:badge color="green" size="sm" inset="top bottom">Berhasil</flux:badge></flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="6" class="text-center text-zinc-400 py-10">Belum ada riwayat transaksi LAZIS.</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
                <div class="mt-4">{{ $this->riwayatLazis->links() }}</div>
                </div>
            @endif

        </flux:card>

        {{-- Info Box LAZIS --}}
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/60 dark:bg-emerald-950/20 p-4 flex gap-3">
            <flux:icon name="information-circle" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
            <div class="text-sm text-emerald-700 dark:text-emerald-300 space-y-1">
                <p class="font-semibold">Ketentuan LAZIS</p>
                <ul class="list-disc list-inside text-emerald-600/80 dark:text-emerald-400/80 space-y-0.5 text-xs">
                    <li>Perubahan setoran rutin memerlukan persetujuan pengurus dan berlaku mulai bulan berikutnya.</li>
                    <li>Setoran tambahan bersifat satu kali dan tidak memerlukan persetujuan (langsung diproses).</li>
                    <li>Setoran tambahan via payroll dipotong di bulan berikutnya; via QRIS diproses segera.</li>
                </ul>
            </div>
        </div>
        </div>

    @endif


    {{-- ══════════════════════════════════════════════════════════════
         MODALS – PPOB
    ══════════════════════════════════════════════════════════════ --}}

    {{-- Modal Tambah PPOB (Rutin & Tambahan) --}}
    <flux:modal name="tambah-ppob" class="md:w-[28rem] space-y-5">
        <div>
            <flux:heading size="lg">
                Tambah PPOB {{ $tipePpob === 'rutin' ? 'Rutin' : 'Tambahan' }}
            </flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">
                @if($tipePpob === 'rutin')
                    Pembayaran akan dipotong otomatis setiap bulan via payroll.
                @else
                    Pembayaran dipotong satu kali di payroll bulan berikutnya.
                @endif
            </flux:text>
        </div>
        <flux:separator variant="subtle" />

        <form wire:submit="submitTambahPpob" class="flex flex-col gap-4">
            {{-- Bagian Pencarian & Pemilihan Kategori --}}
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl space-y-4">
                <flux:heading size="sm">Kategori PPOB</flux:heading>
                
                @if(!$selectedKategoriPpob)
                    <div class="relative">
                        <flux:input wire:model.live.debounce.300ms="searchKategoriPpob" 
                                    icon="magnifying-glass" 
                                    placeholder="Cari kategori (misal: Listrik)..." />
                        
                        @if($searchKategoriPpob && count($this->availableKategoriPpob) > 0)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                @foreach($this->availableKategoriPpob as $kat)
                                    <div wire:click="selectKategoriPpob('{{ $kat->kode }}', '{{ $kat->nama }}')" 
                                         class="px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer">
                                        <div class="font-medium text-sm text-zinc-800 dark:text-zinc-200">{{ $kat->nama }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($searchKategoriPpob)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg p-3 text-center text-sm text-zinc-500">
                                Kategori tidak ditemukan.
                            </div>
                        @endif
                    </div>
                    @error('kategoriPpob') <flux:error>{{ $message }}</flux:error> @enderror
                @else
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div>
                                <div class="font-bold text-sm text-zinc-800 dark:text-zinc-200">{{ $selectedKategoriPpob['nama'] }}</div>
                                <div class="text-xs text-zinc-500">Kode: {{ $selectedKategoriPpob['kode'] }}</div>
                            </div>
                        </div>
                        <flux:button variant="subtle" size="sm" icon="x-mark" wire:click="removeSelectedKategoriPpob" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" />
                    </div>
                @endif
            </div>

            <flux:field>
                <flux:label>ID Pelanggan</flux:label>
                <flux:input wire:model="idPelangganPpob" placeholder="Masukkan ID / Nomor Pelanggan" />
                <flux:description>Nomor meteran listrik, nomor VA, nomor kartu BPJS, dll.</flux:description>
                @error('idPelangganPpob') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <div class="flex justify-end gap-2 pt-1">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus">Tambahkan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Konfirmasi Hapus PPOB --}}
    <flux:modal name="hapus-ppob" class="md:w-[24rem]">
        <div class="flex flex-col gap-5">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/40 rounded-full text-red-500 shrink-0">
                    <flux:icon name="trash" class="w-5 h-5" />
                </div>
                <div>
                    <flux:heading size="lg">Nonaktifkan PPOB?</flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500">
                        Anda akan menonaktifkan pembayaran <strong>{{ $ppobHapusNama }}</strong>. Pembayaran ini tidak akan diproses lagi di payroll berikutnya.
                    </flux:text>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="hapusPpob" icon="trash">Ya, Nonaktifkan</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Modal Sukses Tambah PPOB --}}
    <flux:modal name="sukses-ppob" class="md:w-[22rem]">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500">
                <flux:icon name="check-circle" class="w-9 h-9" />
            </div>
            <flux:heading size="lg">PPOB Ditambahkan!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pembayaran PPOB berhasil ditambahkan dan akan diproses pada payroll berikutnya.
            </flux:text>
            <flux:modal.close><flux:button variant="primary" class="w-full mt-1">Tutup</flux:button></flux:modal.close>
        </div>
    </flux:modal>

    {{-- Modal Sukses Hapus PPOB --}}
    <flux:modal name="sukses-hapus-ppob" class="md:w-[22rem]">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-14 h-14 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500">
                <flux:icon name="check-circle" class="w-9 h-9" />
            </div>
            <flux:heading size="lg">PPOB Dinonaktifkan</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pembayaran PPOB berhasil dinonaktifkan dan tidak akan diproses di payroll berikutnya.
            </flux:text>
            <flux:modal.close><flux:button variant="primary" class="w-full mt-1">Tutup</flux:button></flux:modal.close>
        </div>
    </flux:modal>


    {{-- ══════════════════════════════════════════════════════════════
         MODALS – LAZIS
    ══════════════════════════════════════════════════════════════ --}}

    {{-- Modal Ubah Setoran Rutin LAZIS --}}
    <flux:modal name="ubah-setoran" class="md:w-[28rem] space-y-5">
        <div>
            <flux:heading size="lg">Ubah Setoran Rutin</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Pengajuan ini memerlukan persetujuan pengurus dan berlaku mulai bulan berikutnya.</flux:text>
        </div>
        <flux:separator variant="subtle" />
        <form x-on:submit.prevent="$flux.modal('konfirmasi-ubah-setoran').show(); $flux.modal('ubah-setoran').close()" class="flex flex-col gap-4">
            <flux:field>
                <flux:label>Program LAZIS</flux:label>
                <flux:select wire:model.live="jenisLazisPilihan" disabled>
                    <flux:select.option value="zakat">Zakat</flux:select.option>
                    <flux:select.option value="infaq_shodaqoh">Infaq & Shodaqoh</flux:select.option>
                </flux:select>
            </flux:field>

            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                <span class="text-xs text-zinc-400">Setoran Saat Ini</span>
                <div class="text-lg font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">
                    Rp {{ number_format($jenisLazisPilihan === 'zakat' ? $nominalSaatIniZakat : $nominalSaatIniInfaq, 0, ',', '.') }}
                    <span class="text-xs font-normal text-zinc-400">/bulan</span>
                </div>
            </div>

            <flux:field>
                <flux:label>Nominal Baru (Rp)</flux:label>
                <flux:input wire:model.live="nominalBaru" type="number" placeholder="Contoh: 50000" autofocus />
                @error('nominalBaru') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <div class="flex justify-end gap-2 pt-1">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane">Kirim Pengajuan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Konfirmasi Ubah Setoran --}}
    <flux:modal name="konfirmasi-ubah-setoran" class="md:w-[22rem]">
        <div class="flex flex-col gap-5">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/40 rounded-full text-orange-500">
                    <flux:icon name="exclamation-triangle" class="w-5 h-5" />
                </div>
                <flux:heading size="lg">Konfirmasi Perubahan</flux:heading>
            </div>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl text-center border border-zinc-200 dark:border-zinc-800">
                <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Nominal Baru — {{ $jenisLazisPilihan === 'zakat' ? 'Zakat' : 'Infaq & Shodaqoh' }}</div>
                <div class="text-2xl font-bold mt-1 text-zinc-800 dark:text-zinc-100">
                    Rp {{ $nominalBaru ? number_format((int)$nominalBaru, 0, ',', '.') : 0 }}
                </div>
                <div class="text-xs text-zinc-400 mt-1">Berlaku mulai {{ Carbon::now()->addMonths(1)->translatedFormat('F Y') }}</div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button x-on:click="$flux.modal('ubah-setoran').show()" variant="ghost">Kembali</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" color="orange" wire:click="submitUbahSetoran">Ya, Ajukan</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Modal Sukses Ubah Setoran --}}
    <flux:modal name="sukses-ubah-setoran" class="md:w-[22rem]">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500">
                <flux:icon name="check-circle" class="w-9 h-9" />
            </div>
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Perubahan setoran {{ $jenisLazisPilihan === 'zakat' ? 'Zakat' : 'Infaq & Shodaqoh' }} Anda sedang menunggu verifikasi pengurus.
            </flux:text>
            <flux:modal.close><flux:button variant="primary" class="w-full mt-1">Tutup</flux:button></flux:modal.close>
        </div>
    </flux:modal>

    {{-- Modal Tambah Setoran Tambahan LAZIS --}}
    <flux:modal name="tambah-setoran-lazis" class="md:w-[28rem] space-y-5">
        <div>
            <flux:heading size="lg">Setoran Tambahan</flux:heading>
            <flux:text size="sm" class="mt-1 text-zinc-500">Setoran satu kali, tidak rutin, dan tidak memerlukan persetujuan pengurus.</flux:text>
        </div>
        <flux:separator variant="subtle" />
        <form wire:submit="submitSetoranTambahan" class="flex flex-col gap-4">
            <flux:field>
                <flux:label>Program LAZIS</flux:label>
                <flux:select wire:model="jenisLazisTambahan" disabled>
                    <flux:select.option value="zakat">Zakat</flux:select.option>
                    <flux:select.option value="infaq_shodaqoh">Infaq & Shodaqoh</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Nominal (Rp)</flux:label>
                <flux:input wire:model="nominalTambahan" type="number" placeholder="Minimal Rp 1.000" />
                @error('nominalTambahan') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <flux:field>
                <flux:radio.group label="Metode Pembayaran" variant="cards" :indicator="false" class="max-sm:flex-col">
                    <flux:radio wire:model="metodeTambahan" class="cursor-pointer" value="payroll" icon="banknotes" label="Payroll" description="Dipotong bulan depan" />
                    <flux:radio wire:model="metodeTambahan" class="cursor-pointer" value="qris" icon="qr-code" label="QRIS" description="Langsung diproses" />
                </flux:radio.group>
                @error('metodeTambahan') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>

            <div class="flex justify-end gap-2 pt-1">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary" icon="heart">Bayar Sekarang</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Sukses Setoran Tambahan --}}
    <flux:modal name="sukses-setoran-tambahan" class="md:w-[22rem]">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-500">
                <flux:icon name="heart" class="w-9 h-9" />
            </div>
            <flux:heading size="lg">Setoran Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Setoran tambahan LAZIS Anda berhasil diproses. Semoga menjadi amal yang berkah.
            </flux:text>
            <flux:modal.close><flux:button variant="primary" class="w-full mt-1">Tutup</flux:button></flux:modal.close>
        </div>
    </flux:modal>

</div>

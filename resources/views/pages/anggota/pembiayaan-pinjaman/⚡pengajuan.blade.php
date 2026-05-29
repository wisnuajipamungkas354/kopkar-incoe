<?php

use App\Models\NamaBank;
use App\Models\Pembiayaan;
use App\Models\Pinjaman;
use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::anggota', ['title' => 'Formulir Pengajuan Pembiayaan & Pinjaman'])] class extends Component
{
    // ==========================================
    // LIMIT & MEMBER STATE
    // ==========================================
    public $isAstra = false;
    public $limitTotal = 0;
    public $totalAktif = 0;
    public $sisaLimit = 0;

    // ==========================================
    // FORM STATE: PEMBIAYAAN
    // ==========================================
    public $kategoriPembiayaan = 'barang'; // default: barang
    public $tujuanPembiayaan = '';
    
    // Dynamic multiple items array for 'barang' purchase
    public $itemsBarang = [];
    
    public $nominalPembiayaan = ''; // used for non-barang categories
    public $tenorPembiayaan = '';
    
    // Referensi Pihak Ketiga
    public $referensiNama = '';
    public $referensiTelp = '';
    public $referensiAlamat = '';

    // Rekening Pencairan Pembiayaan
    public $namaBankPembiayaan = '';
    public $noRekeningPembiayaan = '';
    public $namaPemilikRekeningPembiayaan = '';

    // ==========================================
    // FORM STATE: PINJAMAN
    // ==========================================
    public $jenisPinjaman = 'qard'; // default: qard
    public $nominalPinjaman = '';
    public $tenorPinjaman = '';

    // Rekening Pencairan Pinjaman
    public $namaBankPinjaman = '';
    public $noRekeningPinjaman = '';
    public $namaPemilikRekeningPinjaman = '';
    public array $banks;

    public function mount()
    {
        $this->banks = NamaBank::query()->whereNot('kode_bank', 'CASH')->get()->map(fn($row) => $row->kode_bank)->toArray();
        $this->tenorPinjaman = '';
        $this->itemsBarang = [
            ['rincian' => '', 'harga' => '']
        ];

        // Retrieve and pre-fill bank details from profile
        $user = auth('web')->user();
        $employee = $user->userable;
        $member = $employee ? $employee->koperasiMember : null;
        if ($employee) {
            $this->namaBankPembiayaan = $employee->nama_bank ? strtoupper($employee->nama_bank) : '';
            $this->noRekeningPembiayaan = $employee->no_rekening;
            $this->namaPemilikRekeningPembiayaan = $employee->nama_pemilik_rekening;

            $this->namaBankPinjaman = $employee->nama_bank ? strtoupper($employee->nama_bank) : '';
            $this->noRekeningPinjaman = $employee->no_rekening;
            $this->namaPemilikRekeningPinjaman = $employee->nama_pemilik_rekening;
        }

        if ($member) {
            // Astra member logic & Limit calculations
            $this->isAstra = $member->is_koperasi_astra_member;
            $this->limitTotal = $this->isAstra ? 25000000 : 35000000;

            // Fetch active financing and loans total
            $pembiayaanAktif = Pembiayaan::where('employee_id', $employee->id)
                ->whereIn('status', ['dicairkan', 'berjalan'])
                ->get();
            $pinjamanAktif = Pinjaman::where('employee_id', $employee->id)
                ->whereIn('status', ['dicairkan', 'berjalan'])
                ->get();

            $this->totalAktif = 
                $pembiayaanAktif->sum(fn($i) => $i->nominal_disetujui ?: $i->nominal_pengajuan) +
                $pinjamanAktif->sum(fn($i) => $i->nominal_disetujui ?: $i->nominal_pengajuan);

            $this->sisaLimit = max(0, $this->limitTotal - $this->totalAktif);
        }
    }

    public function updatedJenisPinjaman($value)
    {
        if ($value === 'bon') {
            $this->tenorPinjaman = 1;
        } else {
            $this->tenorPinjaman = '';
        }
    }

    public function addItemBarang()
    {
        $this->itemsBarang[] = ['rincian' => '', 'harga' => ''];
    }

    public function removeItemBarang($index)
    {
        unset($this->itemsBarang[$index]);
        $this->itemsBarang = array_values($this->itemsBarang);
    }

    public function getTotalHargaBarang()
    {
        $total = 0;
        foreach ($this->itemsBarang as $item) {
            $total += (int) ($item['harga'] ?? 0);
        }
        return $total;
    }

    public function getActiveNominalPembiayaan()
    {
        if ($this->kategoriPembiayaan === 'barang') {
            return $this->getTotalHargaBarang();
        }
        return (float) $this->nominalPembiayaan;
    }

    public function shouldShowSimulasi()
    {
        $nominal = $this->getActiveNominalPembiayaan();
        $tenor = (int) $this->tenorPembiayaan;
        return $nominal > 0 && $tenor > 0;
    }

    public function getSimulasiData()
    {
        $nominal = (float) $this->getActiveNominalPembiayaan();
        $tenor = (int) $this->tenorPembiayaan;

        if ($nominal <= 0 || $tenor <= 0) {
            return [
                'angsuranPokok' => 0,
                'marginBulanan' => 0,
                'angsuranBulanan' => 0,
                'totalPembiayaan' => 0,
            ];
        }

        $angsuranPokok = $nominal / $tenor;
        $marginBulanan = ($nominal * 0.085) / 12;
        $angsuranBulanan = $angsuranPokok + $marginBulanan;
        $totalPembiayaan = $angsuranBulanan * $tenor;

        return [
            'angsuranPokok' => $angsuranPokok,
            'marginBulanan' => $marginBulanan,
            'angsuranBulanan' => $angsuranBulanan,
            'totalPembiayaan' => $totalPembiayaan,
        ];
    }

    public function submitPembiayaan()
    {
        if ($this->kategoriPembiayaan === 'barang') {
            $this->validate([
                'kategoriPembiayaan' => 'required|in:barang,pendidikan,kesehatan,renovasi,servis,lainnya',
                'tujuanPembiayaan' => 'required|string|max:255',
                'itemsBarang' => 'required|array|min:1',
                'itemsBarang.*.rincian' => 'required|string|max:255',
                'itemsBarang.*.harga' => 'required|numeric|min:1',
                'tenorPembiayaan' => 'required|integer|min:1',
                'referensiNama' => 'required|string|max:255',
                'referensiTelp' => 'required|string|max:20',
                'referensiAlamat' => 'required|string|max:500',
                'namaBankPembiayaan' => 'required|string|max:100',
                'noRekeningPembiayaan' => 'required|string|max:50',
                'namaPemilikRekeningPembiayaan' => 'required|string|max:150',
            ], [
                'tujuanPembiayaan.required' => 'Tujuan pembiayaan wajib diisi.',
                'itemsBarang.*.rincian.required' => 'Rincian barang wajib diisi.',
                'itemsBarang.*.harga.required' => 'Harga barang wajib diisi.',
                'itemsBarang.*.harga.min' => 'Harga barang minimal Rp 1.',
                'tenorPembiayaan.required' => 'Tenor pembiayaan wajib diisi.',
                'referensiNama.required' => 'Nama pihak ketiga wajib diisi.',
                'referensiTelp.required' => 'Nomor telepon pihak ketiga wajib diisi.',
                'referensiAlamat.required' => 'Alamat pihak ketiga wajib diisi.',
                'namaBankPembiayaan.required' => 'Nama bank wajib diisi.',
                'noRekeningPembiayaan.required' => 'Nomor rekening wajib diisi.',
                'namaPemilikRekeningPembiayaan.required' => 'Nama pemilik rekening wajib diisi.',
            ]);
        } else {
            $this->validate([
                'kategoriPembiayaan' => 'required|in:barang,pendidikan,kesehatan,renovasi,servis,lainnya',
                'tujuanPembiayaan' => 'required|string|max:255',
                'nominalPembiayaan' => 'required|numeric|min:1',
                'tenorPembiayaan' => 'required|integer|min:1',
                'referensiNama' => 'required|string|max:255',
                'referensiTelp' => 'required|string|max:20',
                'referensiAlamat' => 'required|string|max:500',
                'namaBankPembiayaan' => 'required|string|max:100',
                'noRekeningPembiayaan' => 'required|string|max:50',
                'namaPemilikRekeningPembiayaan' => 'required|string|max:150',
            ], [
                'tujuanPembiayaan.required' => 'Tujuan pembiayaan wajib diisi.',
                'nominalPembiayaan.required' => 'Nominal yang diajukan wajib diisi.',
                'nominalPembiayaan.min' => 'Nominal yang diajukan minimal Rp 1.',
                'tenorPembiayaan.required' => 'Tenor pembiayaan wajib diisi.',
                'referensiNama.required' => 'Nama pihak ketiga wajib diisi.',
                'referensiTelp.required' => 'Nomor telepon pihak ketiga wajib diisi.',
                'referensiAlamat.required' => 'Alamat pihak ketiga wajib diisi.',
                'namaBankPembiayaan.required' => 'Nama bank wajib diisi.',
                'noRekeningPembiayaan.required' => 'Nomor rekening wajib diisi.',
                'namaPemilikRekeningPembiayaan.required' => 'Nama pemilik rekening wajib diisi.',
            ]);
        }

        $user = User::with('userable')->find(auth('web')->user()->id);
        Pembiayaan::create([
            'nomor_pengajuan' => 'PB-' . date('YmdHis') . '-' . rand(1000, 9999),
            'employee_id' => $user->userable->id,
            'kategori_pembiayaan' => $this->kategoriPembiayaan,
            'tujuan_pembiayaan' => $this->tujuanPembiayaan,
            'rincian_barang' => $this->kategoriPembiayaan === 'barang' ? $this->itemsBarang : null,
            'nominal_pengajuan' => $this->getActiveNominalPembiayaan(),
            'tenor_bulan' => $this->tenorPembiayaan,
            'pencairan_dana_ke' => 'pihak_ketiga',
            'nama_pihak_ketiga' => $this->referensiNama,
            'no_telp_pihak_ketiga' => $this->referensiTelp,
            'alamat_pihak_ketiga' => $this->referensiAlamat,
            'nama_bank' => $this->namaBankPembiayaan,
            'no_rekening' => $this->noRekeningPembiayaan,
            'nama_pemilik_rekening' => $this->namaPemilikRekeningPembiayaan,
            'status' => 'diajukan',
            'diajukan_oleh' => $user->id,
            'diajukan_pada' => now(),
        ]);

        // Tampilkan modal sukses
        Flux::modal('sukses-pengajuan')->show();
    }

    public function submitPinjaman()
    {
        if ($this->jenisPinjaman === 'qard') {
            $this->validate([
                'jenisPinjaman' => 'required|in:qard,bon',
                'nominalPinjaman' => 'required|numeric|min:1|max:5000000',
                'tenorPinjaman' => 'required|integer|min:1|max:12',
                'namaBankPinjaman' => 'required|string|max:100',
                'noRekeningPinjaman' => 'required|string|max:50',
                'namaPemilikRekeningPinjaman' => 'required|string|max:150',
            ], [
                'nominalPinjaman.required' => 'Nominal pinjaman wajib diisi.',
                'nominalPinjaman.min' => 'Nominal pinjaman minimal Rp 1.',
                'nominalPinjaman.max' => 'Pinjaman Qard Hasan maksimal Rp 5.000.000.',
                'tenorPinjaman.required' => 'Tenor angsuran wajib diisi.',
                'tenorPinjaman.max' => 'Tenor Qard Hasan maksimal 12 bulan.',
                'namaBankPinjaman.required' => 'Nama bank wajib diisi.',
                'noRekeningPinjaman.required' => 'Nomor rekening wajib diisi.',
                'namaPemilikRekeningPinjaman.required' => 'Nama pemilik rekening wajib diisi.',
            ]);
        } else {
            // Bon Sementara
            $this->tenorPinjaman = 1; // force 1 month
            $this->validate([
                'jenisPinjaman' => 'required|in:qard,bon',
                'nominalPinjaman' => 'required|numeric|min:1|max:1000000',
                'tenorPinjaman' => 'required|integer|in:1',
                'namaBankPinjaman' => 'required|string|max:100',
                'noRekeningPinjaman' => 'required|string|max:50',
                'namaPemilikRekeningPinjaman' => 'required|string|max:150',
            ], [
                'nominalPinjaman.required' => 'Nominal pinjaman wajib diisi.',
                'nominalPinjaman.min' => 'Nominal pinjaman minimal Rp 1.',
                'nominalPinjaman.max' => 'Pinjaman Bon Sementara maksimal Rp 1.000.000.',
                'namaBankPinjaman.required' => 'Nama bank wajib diisi.',
                'noRekeningPinjaman.required' => 'Nomor rekening wajib diisi.',
                'namaPemilikRekeningPinjaman.required' => 'Nama pemilik rekening wajib diisi.',
            ]);
        }

        $user = User::with('userable')->find(auth('web')->user()->id);
        
        Pinjaman::create([
            'employee_id' => $user->userable->id,
            'nomor_pengajuan' => 'PJ-' . date('YmdHis') . '-' . rand(1000, 9999),
            'jenis_pinjaman' => $this->jenisPinjaman,
            'nominal_pengajuan' => $this->nominalPinjaman,
            'tenor_bulan' => $this->tenorPinjaman,
            'nama_bank' => $this->namaBankPinjaman,
            'no_rekening' => $this->noRekeningPinjaman,
            'nama_pemilik_rekening' => $this->namaPemilikRekeningPinjaman,
            'status' => 'diajukan',
            'diajukan_oleh' => $user->id,
            'diajukan_pada' => now(),
        ]);

        // Tampilkan modal sukses
        Flux::modal('sukses-pengajuan')->show();
    }
};
?>

<div x-data="{ activeTab: 'pembiayaan' }" class="space-y-6">
    <!-- Breadcrumbs -->
    <div class="mb-6 hidden md:block">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item href="/anggota/pembiayaan-pinjaman">Pembiayaan & Pinjaman</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Pengajuan Baru</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
        <div>
            <flux:heading size="xl" level="1">Pengajuan Baru</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Pilih jenis pengajuan di bawah ini dan lengkapi detail data yang diperlukan.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Tab Navigation (Senada dengan Profile & Pembayaran) -->
    <div class="flex border-b border-zinc-200 dark:border-zinc-700 gap-1 overflow-x-auto">
        <button @click="activeTab = 'pembiayaan'" 
                :class="activeTab === 'pembiayaan' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:border-blue-400 font-semibold' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                class="pb-3 px-1 mr-6 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 focus:outline-none">
            <flux:icon name="shopping-bag" variant="outline" class="w-4 h-4" />
            <span>Pembiayaan</span>
        </button>
        <button @click="activeTab = 'pinjaman'" 
                :class="activeTab === 'pinjaman' ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:border-blue-400 font-semibold' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                class="pb-3 px-1 mr-6 text-sm font-semibold border-b-2 transition-all flex items-center gap-2 focus:outline-none">
            <flux:icon name="banknotes" variant="outline" class="w-4 h-4" />
            <span>Pinjaman</span>
        </button>
    </div>

    <!-- ════════════════════════════════════════════════
         TAB CONTENT: PEMBIAYAAN
    ════════════════════════════════════════════════ -->
    <div x-show="activeTab === 'pembiayaan'" x-transition class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Form Pembiayaan -->
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <form wire:submit.prevent="submitPembiayaan" class="space-y-6">
                    <div>
                        <flux:heading size="md">Formulir Pengajuan Pembiayaan</flux:heading>
                        <flux:subheading class="mt-1">Lengkapi rincian pembelian barang atau keperluan pembiayaan lainnya.</flux:subheading>
                    </div>
                    
                    <flux:separator variant="subtle" />

                    <!-- Kategori Pembiayaan (Radio Button) -->
                    <flux:field>
                        <flux:label class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mb-2">Pilih Kategori Pembiayaan</flux:label>
                        <flux:radio.group wire:model.live="kategoriPembiayaan" class="flex flex-col gap-3">
                            <flux:radio value="barang" label="Pembiayaan Pembelian Barang" />
                            <flux:radio value="pendidikan" label="Pembiayaan Pendidikan" />
                            <flux:radio value="kesehatan" label="Pembiayaan Kesehatan" />
                            <flux:radio value="renovasi" label="Pembiayaan Renovasi Rumah" />
                            <flux:radio value="servis" label="Pembiayaan Servis Kendaraan" />
                            <flux:radio value="lainnya" label="Pembiayaan Lainnya" />
                        </flux:radio.group>
                        <flux:error name="kategoriPembiayaan" />
                    </flux:field>

                    <flux:separator variant="subtle" />

                    <!-- Input Tujuan Pembiayaan -->
                    <flux:field>
                        <flux:label>Tujuan Pembiayaan</flux:label>
                        <flux:input wire:model="tujuanPembiayaan" placeholder="Masukkan tujuan/keperluan pembiayaan..." />
                        <flux:error name="tujuanPembiayaan" />
                    </flux:field>

                    <!-- Dynamic Input Sections -->
                    @if($kategoriPembiayaan === 'barang')
                        <div class="space-y-4 p-4 bg-emerald-500/5 dark:bg-emerald-950/10 rounded-xl border border-emerald-200/50 dark:border-emerald-900/30">
                            <flux:heading size="sm" class="text-emerald-700 dark:text-emerald-400">Daftar Barang yang Dibeli</flux:heading>
                            
                            <div class="flex flex-col gap-4">
                                @foreach($itemsBarang as $index => $item)
                                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800 flex flex-col gap-4 relative">
                                        @if(count($itemsBarang) > 1)
                                            <div class="absolute top-4 right-4">
                                                <flux:button size="xs" variant="danger" icon="trash" wire:click="removeItemBarang({{ $index }})">Hapus</flux:button>
                                            </div>
                                        @endif
                                        <div class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Barang #{{ $index + 1 }}</div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <flux:field>
                                                <flux:label>Rincian Barang</flux:label>
                                                <flux:input wire:model="itemsBarang.{{ $index }}.rincian" placeholder="Contoh: Asus ROG Strix..." />
                                                <flux:error name="itemsBarang.{{ $index }}.rincian" />
                                            </flux:field>
                                            <flux:field>
                                                <flux:label>Nominal Harga Barang (Rp)</flux:label>
                                                <flux:input type="number" wire:model.live.debounce.500ms="itemsBarang.{{ $index }}.harga" placeholder="Contoh: 15000000" />
                                                <flux:error name="itemsBarang.{{ $index }}.harga" />
                                            </flux:field>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex justify-between items-center mt-4">
                                <flux:button size="sm" variant="outline" icon="plus" wire:click="addItemBarang">Tambah Barang</flux:button>
                                <div class="text-sm font-bold text-zinc-700 dark:text-zinc-300">
                                    Total Harga: <span class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($this->getTotalHargaBarang(), 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="space-y-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Detail Pengajuan Pembiayaan</flux:heading>
                            <div class="grid grid-cols-1 gap-4">
                                <flux:field>
                                    <flux:label>Nominal yang Diajukan (Rp)</flux:label>
                                    <flux:input type="number" wire:model.live.debounce.500ms="nominalPembiayaan" placeholder="Contoh: 5000000" />
                                    <flux:error name="nominalPembiayaan" />
                                </flux:field>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4">
                        <flux:field>
                            <flux:label>Tenor Pembiayaan (Bulan)</flux:label>
                            <flux:input type="number" wire:model.live.debounce.500ms="tenorPembiayaan" placeholder="Contoh: 12" />
                            <flux:error name="tenorPembiayaan" />
                        </flux:field>
                    </div>

                    <!-- Simulasi Pembiayaan Section (Dinamis & Responsive) -->
                    @if($this->shouldShowSimulasi())
                        <div class="p-5 bg-gradient-to-br from-emerald-500/10 to-teal-500/5 dark:from-emerald-900/20 dark:to-teal-900/10 rounded-xl border border-emerald-200 dark:border-emerald-900/50 space-y-4">
                            <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                                <flux:icon name="presentation-chart-line" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                                <flux:heading size="md" class="!text-emerald-800 dark:!text-emerald-300">Simulasi Pembiayaan (Margin 8.5% / Tahun Flat)</flux:heading>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                                <div class="p-3 bg-white dark:bg-zinc-900 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-800">
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Angsuran Pokok / Bulan</div>
                                    <div class="text-base font-bold mt-1 text-zinc-800 dark:text-zinc-200">
                                        Rp {{ number_format($this->getSimulasiData()['angsuranPokok'], 0, ',', '.') }}
                                    </div>
                                </div>
                                
                                <div class="p-3 bg-white dark:bg-zinc-900 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-800">
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Margin Koperasi / Bulan</div>
                                    <div class="text-base font-bold mt-1 text-zinc-800 dark:text-zinc-200">
                                        Rp {{ number_format($this->getSimulasiData()['marginBulanan'], 0, ',', '.') }}
                                    </div>
                                </div>

                                <div class="p-3 bg-emerald-500/5 dark:bg-emerald-500/10 rounded-lg border border-emerald-200 dark:border-emerald-800/80 shadow-sm">
                                    <div class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold">Total Angsuran / Bulan</div>
                                    <div class="text-lg font-extrabold mt-1 text-emerald-600 dark:text-emerald-400">
                                        Rp {{ number_format($this->getSimulasiData()['angsuranBulanan'], 0, ',', '.') }}
                                    </div>
                                </div>

                                <div class="p-3 bg-emerald-600 text-white rounded-lg shadow-md flex flex-col justify-center">
                                    <div class="text-xs text-emerald-100 font-medium">Total Pembiayaan</div>
                                    <div class="text-lg font-black mt-0.5">
                                        Rp {{ number_format($this->getSimulasiData()['totalPembiayaan'], 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Card Referensi Pihak Ketiga -->
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700 flex flex-col gap-4">
                        <div>
                            <flux:heading size="sm">Referensi Pihak Ketiga</flux:heading>
                            <flux:text size="xs" class="text-zinc-500 mt-1">Isi referensi toko, lembaga pendidikan, faskes, atau penyedia jasa terkait.</flux:text>
                        </div>
                        <flux:field>
                            <flux:label>Nama Lembaga/Toko/Pihak Ketiga</flux:label>
                            <flux:input wire:model="referensiNama" placeholder="Masukkan nama toko/pihak ketiga..." />
                            <flux:error name="referensiNama" />
                        </flux:field>
                        <flux:field>
                            <flux:label>No Telp / WA Pihak Ketiga</flux:label>
                            <flux:input wire:model="referensiTelp" placeholder="Contoh: 081234567890" />
                            <flux:error name="referensiTelp" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Alamat Pihak Ketiga</flux:label>
                            <flux:textarea wire:model="referensiAlamat" placeholder="Alamat lengkap pihak ketiga..." />
                            <flux:error name="referensiAlamat" />
                        </flux:field>
                    </div>

                    <!-- Rekening Pencairan (Pembiayaan) -->
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                        <div>
                            <flux:heading size="sm">Rekening Pencairan Dana</flux:heading>
                            <flux:text size="xs" class="text-zinc-500 mt-1">Harap pastikan rekening pencairan aktif dan sesuai data Anda.</flux:text>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:field>
                                <flux:label>Nama Bank</flux:label>
                                <flux:select wire:model="namaBankPembiayaan" placeholder="Pilih Bank...">
                                    @foreach($banks as $bank)
                                        <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="namaBankPembiayaan" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Nomor Rekening</flux:label>
                                <flux:input wire:model="noRekeningPembiayaan" placeholder="Contoh: 1234567890..." />
                                <flux:error name="noRekeningPembiayaan" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Nama Pemilik Rekening</flux:label>
                                <flux:input wire:model="namaPemilikRekeningPembiayaan" placeholder="Sesuai buku tabungan..." />
                                <flux:error name="namaPemilikRekeningPembiayaan" />
                            </flux:field>
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Actions & Submit -->
                    <div class="flex justify-end gap-2">
                        <flux:button href="/anggota/pembiayaan-pinjaman" wire:navigate variant="ghost">Batal</flux:button>
                        <flux:button type="submit" variant="primary" icon="paper-airplane">Kirim Pembiayaan</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <!-- Kolom Kanan: Ketentuan & Sisa Limit Pembiayaan -->
        <div class="lg:col-span-1 space-y-6">
            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="md">Batas Pengajuan Anggota</flux:heading>
                    <flux:text class="text-xs text-zinc-500 mt-1">Berikut sisa batas limit pembiayaan & pinjaman Anda.</flux:text>
                </div>
                <flux:separator variant="subtle" />

                <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-zinc-900 dark:to-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status Anggota</span>
                        @if($isAstra)
                            <flux:badge color="blue" size="sm">Anggota Astra</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Non-Anggota Astra</flux:badge>
                        @endif
                    </div>
                    <flux:separator variant="subtle" />
                    <div>
                        <div class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Limit Gabungan</div>
                        <div class="text-lg font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">
                            Rp {{ number_format($limitTotal, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Aktif (Plafond Berjalan)</div>
                        <div class="text-base font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">
                            Rp {{ number_format($totalAktif, 0, ',', '.') }}
                        </div>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="p-3 bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-200/50 dark:border-emerald-900/30 rounded-xl">
                        <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sisa Limit Diajukan</div>
                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                            Rp {{ number_format($sisaLimit, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="sm">Ketentuan Batas Maksimal</flux:heading>
                </div>
                <flux:separator variant="subtle" />
                <div class="space-y-3 text-xs text-zinc-600 dark:text-zinc-400">
                    <div class="flex justify-between gap-2">
                        <span>Anggota Astra:</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">Maks. Rp 25.000.000</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span>Non-Anggota Astra:</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">Maks. Rp 35.000.000</span>
                    </div>
                    <flux:separator variant="subtle" />
                    <p class="text-[11px] leading-relaxed text-zinc-400">
                        Batas di atas merupakan akumulasi total gabungan dari seluruh fasilitas pembiayaan & pinjaman aktif yang Anda miliki.
                    </p>
                </div>
            </flux:card>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════
         TAB CONTENT: PINJAMAN
    ════════════════════════════════════════════════ -->
    <div x-show="activeTab === 'pinjaman'" x-transition class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Form Pinjaman -->
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <form wire:submit.prevent="submitPinjaman" class="space-y-6">
                    <div>
                        <flux:heading size="md">Formulir Pengajuan Pinjaman</flux:heading>
                        <flux:subheading class="mt-1">Lengkapi informasi untuk pengajuan pinjaman dana darurat Anda.</flux:subheading>
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Jenis Pinjaman (Radio Button) -->
                    <flux:field>
                        <flux:label class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mb-2">Pilih Jenis Pinjaman</flux:label>
                        <div class="space-y-3">
                            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors
                                {{ $jenisPinjaman === 'qard' ? 'bg-blue-50 dark:bg-blue-950/20 border-blue-300 dark:border-blue-800' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-zinc-300' }}">
                                <input type="radio" wire:model.live="jenisPinjaman" value="qard" class="mt-1 accent-blue-600">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">Pinjaman Qard Hasan</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 space-y-0.5">
                                        <div class="flex justify-between">
                                            <span>Maksimal nominal</span>
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Rp 5.000.000</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Maksimal tenor</span>
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">12 bulan</span>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-colors
                                {{ $jenisPinjaman === 'bon' ? 'bg-amber-50 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800' : 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:border-zinc-300' }}">
                                <input type="radio" wire:model.live="jenisPinjaman" value="bon" class="mt-1 accent-amber-600">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-200">Bon Sementara</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 space-y-0.5">
                                        <div class="flex justify-between">
                                            <span>Maksimal nominal</span>
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">Rp 1.000.000</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Tenor</span>
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">1x potong bulan berikutnya</span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <flux:error name="jenisPinjaman" />
                    </flux:field>

                    <flux:separator variant="subtle" />

                    <!-- Nominal Pinjaman -->
                    <flux:field>
                        <flux:label>Nominal Pinjaman (Rp)</flux:label>
                        <flux:input type="number" wire:model="nominalPinjaman" placeholder="Masukkan nominal pinjaman..." />
                        @if($jenisPinjaman === 'qard')
                            <flux:text size="xs" class="text-zinc-400 mt-1 block">Batas limit Qard Hasan: Rp 5.000.000.</flux:text>
                        @else
                            <flux:text size="xs" class="text-zinc-400 mt-1 block">Batas limit Bon Sementara: Rp 1.000.000.</flux:text>
                        @endif
                        <flux:error name="nominalPinjaman" />
                    </flux:field>

                    <!-- Tenor Angsuran -->
                    <flux:field>
                        <flux:label>Tenor Angsuran (Bulan)</flux:label>
                        @if($jenisPinjaman === 'bon')
                            <flux:input type="number" wire:model="tenorPinjaman" disabled />
                            <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-950/20 rounded-lg border border-amber-200 dark:border-amber-900/40 text-xs text-amber-800 dark:text-amber-300">
                                <strong>Bon Sementara</strong> wajib dilunasi penuh pada siklus penggajian berikutnya (1 bulan tenor).
                            </div>
                        @else
                            <flux:input type="number" wire:model="tenorPinjaman" placeholder="Contoh: 10" />
                        @endif
                        <flux:error name="tenorPinjaman" />
                    </flux:field>

                    <!-- Rekening Pencairan (Pinjaman) -->
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                        <div>
                            <flux:heading size="sm">Rekening Pencairan Dana</flux:heading>
                            <flux:text size="xs" class="text-zinc-500 mt-1">Harap pastikan rekening pencairan aktif dan sesuai data Anda.</flux:text>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:field>
                                <flux:label>Nama Bank</flux:label>
                                <flux:select wire:model="namaBankPinjaman" placeholder="Pilih Bank...">
                                    @foreach($banks as $bank)
                                        <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="namaBankPinjaman" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Nomor Rekening</flux:label>
                                <flux:input wire:model="noRekeningPinjaman" placeholder="Contoh: 1234567890..." />
                                <flux:error name="noRekeningPinjaman" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Nama Pemilik Rekening</flux:label>
                                <flux:input wire:model="namaPemilikRekeningPinjaman" placeholder="Sesuai buku tabungan..." />
                                <flux:error name="namaPemilikRekeningPinjaman" />
                            </flux:field>
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <!-- Actions & Submit -->
                    <div class="flex justify-end gap-2">
                        <flux:button href="/anggota/pembiayaan-pinjaman" wire:navigate variant="ghost">Batal</flux:button>
                        <flux:button type="submit" variant="primary" icon="paper-airplane">Kirim Pinjaman</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <!-- Kolom Kanan: Ketentuan & Sisa Limit Pinjaman -->
        <div class="lg:col-span-1 space-y-6">
            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="md">Batas Pengajuan Anggota</flux:heading>
                    <flux:text class="text-xs text-zinc-500 mt-1">Berikut sisa batas limit pembiayaan & pinjaman Anda.</flux:text>
                </div>
                <flux:separator variant="subtle" />

                <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-zinc-900 dark:to-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Status Anggota</span>
                        @if($isAstra)
                            <flux:badge color="blue" size="sm">Anggota Astra</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Non-Anggota Astra</flux:badge>
                        @endif
                    </div>
                    <flux:separator variant="subtle" />
                    <div>
                        <div class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Limit Gabungan</div>
                        <div class="text-lg font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">
                            Rp {{ number_format($limitTotal, 0, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Aktif (Plafond Berjalan)</div>
                        <div class="text-base font-bold text-zinc-800 dark:text-zinc-200 mt-0.5">
                            Rp {{ number_format($totalAktif, 0, ',', '.') }}
                        </div>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="p-3 bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-200/50 dark:border-emerald-900/30 rounded-xl">
                        <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sisa Limit Diajukan</div>
                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                            Rp {{ number_format($sisaLimit, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="sm">Ketentuan Pinjaman</flux:heading>
                </div>
                <flux:separator variant="subtle" />
                <div class="space-y-4 text-xs text-zinc-600 dark:text-zinc-400">
                    <div class="space-y-1">
                        <div class="font-semibold text-zinc-800 dark:text-zinc-200">1. Qard Hasan</div>
                        <div class="pl-4 space-y-0.5 list-disc text-zinc-500">
                            <div>Maksimal: Rp 5.000.000</div>
                            <div>Tenor: 1 - 12 Bulan</div>
                        </div>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="space-y-1">
                        <div class="font-semibold text-zinc-800 dark:text-zinc-200">2. Bon Sementara</div>
                        <div class="pl-4 space-y-0.5 list-disc text-zinc-500">
                            <div>Maksimal: Rp 1.000.000</div>
                            <div>Tenor: 1x Potong Gaji</div>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>

    <!-- Modal Sukses (Pertahankan) -->
    <flux:modal name="sukses-pengajuan" class="md:w-md" :dismissible="false">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500 mb-2">
                <flux:icon name="check-circle" class="w-10 h-10" />
            </div>
            
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pengajuan Anda telah berhasil dikirim dan sedang menunggu proses verifikasi oleh pengurus koperasi.
            </flux:text>

            <div class="w-full mt-4">
                <flux:button href="/anggota/pembiayaan-pinjaman" wire:navigate variant="primary" class="w-full">Kembali ke Daftar Pinjaman</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

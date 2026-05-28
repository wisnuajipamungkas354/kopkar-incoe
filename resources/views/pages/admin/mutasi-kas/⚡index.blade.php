<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\MutasiKasKoperasi;
use App\Models\RekeningKoperasi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    // Active tab: 'mutasi' or 'rekening'
    public $activeTab = 'mutasi';

    // Filters for Mutasi
    public $search = '';
    public $filterRekening = '';
    public $filterJenis = '';
    public $filterKategori = '';
    public $filterMetode = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPageMutasi = 10;

    // Modal Mutation State
    public $mutasiId = null;
    public $rekening_koperasi_id = '';
    public $jenis_transaksi = 'pemasukan';
    public $kategori_transaksi = 'operasional';
    public $nominal = '';
    public $metode_transaksi = 'transfer';
    public $tanggal_transaksi = '';
    public $keterangan = '';

    // Modal Rekening State
    public $rekeningId = null;
    public $nama_rekening = '';
    public $kode_rekening = '';
    public $nama_bank = '';
    public $no_rekening = '';
    public $atas_nama = '';
    public $saldo_saat_ini = '';
    public $is_active = true;
    public $is_cash = false;
    public $bankSearch = '';

    public function mount()
    {
        $this->tanggal_transaksi = Carbon::now()->format('Y-m-d\TH:i');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage('mutasi-page');
    }

    public function updatingSearch() { $this->resetPage('mutasi-page'); }
    public function updatingFilterRekening() { $this->resetPage('mutasi-page'); }
    public function updatingFilterJenis() { $this->resetPage('mutasi-page'); }
    public function updatingFilterKategori() { $this->resetPage('mutasi-page'); }
    public function updatingFilterMetode() { $this->resetPage('mutasi-page'); }
    public function updatingDateFrom() { $this->resetPage('mutasi-page'); }
    public function updatingDateTo() { $this->resetPage('mutasi-page'); }

    // --- COMPUTED PROPERTIES ---

    #[Computed]
    public function stats()
    {
        $rekeningIds = RekeningKoperasi::pluck('id');
        
        $totalMasuk = MutasiKasKoperasi::where('jenis_transaksi', 'pemasukan')->sum('nominal');
        $totalKeluar = MutasiKasKoperasi::where('jenis_transaksi', 'pengeluaran')->sum('nominal');
        $totalSaldoRekening = RekeningKoperasi::sum('saldo_saat_ini');

        return [
            'total_pemasukan' => $totalMasuk,
            'total_pengeluaran' => $totalKeluar,
            'saldo_konsolidasi' => $totalSaldoRekening,
            'rekening_count' => count($rekeningIds),
        ];
    }

    #[Computed]
    public function rekeningOptions()
    {
        return RekeningKoperasi::where('is_active', true)->orderBy('nama_rekening', 'asc')->get();
    }

    #[Computed]
    public function mutasiList()
    {
        $query = MutasiKasKoperasi::with('rekeningKoperasi');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('keterangan', 'like', '%' . $this->search . '%')
                  ->orWhere('kategori_transaksi', 'like', '%' . $this->search . '%')
                  ->orWhereHas('rekeningKoperasi', function ($rq) {
                      $rq->where('nama_rekening', 'like', '%' . $this->search . '%')
                        ->orWhere('nama_bank', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rekening', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->filterRekening) {
            $query->where('rekening_koperasi_id', $this->filterRekening);
        }

        if ($this->filterJenis) {
            $query->where('jenis_transaksi', $this->filterJenis);
        }

        if ($this->filterKategori) {
            $query->where('kategori_transaksi', $this->filterKategori);
        }

        if ($this->filterMetode) {
            $query->where('metode_transaksi', $this->filterMetode);
        }

        if ($this->dateFrom) {
            $query->whereDate('tanggal_transaksi', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('tanggal_transaksi', '<=', $this->dateTo);
        }

        return $query->orderBy('tanggal_transaksi', 'desc')
            ->paginate($this->perPageMutasi, pageName: 'mutasi-page');
    }

    #[Computed]
    public function availableBanks()
    {
        $query = \App\Models\NamaBank::query();
        if ($this->bankSearch && !str_contains($this->bankSearch, ' - ')) {
            $query->where(function($q) {
                $q->where('nama_bank', 'like', '%' . $this->bankSearch . '%')
                  ->orWhere('kode_bank', 'like', '%' . $this->bankSearch . '%');
            });
        }
        return $query->orderBy('nama_bank', 'asc')->get();
    }

    public function selectBank($kode, $nama)
    {
        $this->kode_rekening = $kode;
        $this->nama_bank = $nama;
        $this->bankSearch = $kode . ' - ' . $nama;
        if (empty($this->nama_rekening)) {
            $this->nama_rekening = $nama;
        }
    }

    #[Computed]
    public function rekeningList()
    {
        return RekeningKoperasi::orderBy('nama_rekening', 'asc')->get();
    }

    // --- MUTATION OPERATIONS ---

    public function showAddMutation()
    {
        $this->resetValidation();
        $this->reset(['mutasiId', 'rekening_koperasi_id', 'nominal', 'keterangan']);
        $this->jenis_transaksi = 'pemasukan';
        $this->kategori_transaksi = 'operasional';
        $this->metode_transaksi = 'transfer';
        $this->tanggal_transaksi = Carbon::now()->format('Y-m-d\TH:i');
        
        $firstRek = $this->rekeningOptions->first();
        if ($firstRek) {
            $this->rekening_koperasi_id = $firstRek->id;
        }

        $this->js("Flux.modal('mutation-modal').show()");
    }

    public function showEditMutation($id)
    {
        $this->resetValidation();
        $mutasi = MutasiKasKoperasi::findOrFail($id);
        $this->mutasiId = $mutasi->id;
        $this->rekening_koperasi_id = $mutasi->rekening_koperasi_id;
        $this->jenis_transaksi = $mutasi->jenis_transaksi;
        $this->kategori_transaksi = $mutasi->kategori_transaksi;
        $this->nominal = $mutasi->nominal;
        $this->metode_transaksi = $mutasi->metode_transaksi;
        $this->tanggal_transaksi = Carbon::parse($mutasi->tanggal_transaksi)->format('Y-m-d\TH:i');
        $this->keterangan = $mutasi->keterangan ?? '';

        $this->js("Flux.modal('mutation-modal').show()");
    }

    public function saveMutation()
    {
        $this->validate([
            'rekening_koperasi_id' => 'required|exists:rekening_koperasi,id',
            'jenis_transaksi' => 'required|in:pemasukan,pengeluaran',
            'kategori_transaksi' => 'required|string',
            'nominal' => 'required|numeric|min:1',
            'metode_transaksi' => 'required|in:transfer,cash,payroll',
            'tanggal_transaksi' => 'required',
            'keterangan' => 'nullable|string',
        ], [
            'rekening_koperasi_id.required' => 'Rekening Kas wajib dipilih.',
            'rekening_koperasi_id.exists' => 'Rekening Kas tidak valid.',
            'nominal.required' => 'Nominal transaksi wajib diisi.',
            'nominal.min' => 'Nominal transaksi minimal Rp 1.',
        ]);

        DB::transaction(function () {
            $nominalFloat = (float) $this->nominal;
            $now = now();
            $userId = auth()->id();

            if ($this->mutasiId) {
                // Edit mode
                $mutasi = MutasiKasKoperasi::findOrFail($this->mutasiId);

                // 1. Rollback old balance
                $oldRekening = RekeningKoperasi::findOrFail($mutasi->rekening_koperasi_id);
                if ($mutasi->jenis_transaksi === 'pemasukan') {
                    $oldRekening->saldo_saat_ini -= $mutasi->nominal;
                } else {
                    $oldRekening->saldo_saat_ini += $mutasi->nominal;
                }
                $oldRekening->save();

                // 2. Apply new balance
                $newRekening = RekeningKoperasi::findOrFail($this->rekening_koperasi_id);
                if ($this->jenis_transaksi === 'pemasukan') {
                    $newRekening->saldo_saat_ini += $nominalFloat;
                } else {
                    $newRekening->saldo_saat_ini -= $nominalFloat;
                }
                $newRekening->save();

                // 3. Update mutasi record
                $mutasi->update([
                    'rekening_koperasi_id' => $this->rekening_koperasi_id,
                    'jenis_transaksi' => $this->jenis_transaksi,
                    'kategori_transaksi' => $this->kategori_transaksi,
                    'nominal' => $nominalFloat,
                    'metode_transaksi' => $this->metode_transaksi,
                    'keterangan' => $this->keterangan ?: null,
                    'tanggal_transaksi' => $this->tanggal_transaksi,
                    'diproses_oleh' => $userId,
                ]);

                $this->js("Flux.toast({ text: 'Mutasi kas berhasil diperbarui.', variant: 'success' })");
            } else {
                // Add mode
                // 1. Apply balance
                $rekening = RekeningKoperasi::findOrFail($this->rekening_koperasi_id);
                if ($this->jenis_transaksi === 'pemasukan') {
                    $rekening->saldo_saat_ini += $nominalFloat;
                } else {
                    $rekening->saldo_saat_ini -= $nominalFloat;
                }
                $rekening->save();

                // 2. Create mutasi record
                MutasiKasKoperasi::create([
                    'rekening_koperasi_id' => $this->rekening_koperasi_id,
                    'jenis_transaksi' => $this->jenis_transaksi,
                    'kategori_transaksi' => $this->kategori_transaksi,
                    'nominal' => $nominalFloat,
                    'metode_transaksi' => $this->metode_transaksi,
                    'keterangan' => $this->keterangan ?: null,
                    'tanggal_transaksi' => $this->tanggal_transaksi,
                    'diproses_oleh' => $userId,
                ]);

                $this->js("Flux.toast({ text: 'Mutasi kas baru berhasil dicatat.', variant: 'success' })");
            }
        });

        $this->js("Flux.modal('mutation-modal').close()");
    }

    public function deleteMutation($id)
    {
        DB::transaction(function () use ($id) {
            $mutasi = MutasiKasKoperasi::findOrFail($id);
            $rekening = RekeningKoperasi::findOrFail($mutasi->rekening_koperasi_id);

            // Revert balance
            if ($mutasi->jenis_transaksi === 'pemasukan') {
                $rekening->saldo_saat_ini -= $mutasi->nominal;
            } else {
                $rekening->saldo_saat_ini += $mutasi->nominal;
            }
            $rekening->save();

            $mutasi->delete();
        });

        $this->js("Flux.toast({ text: 'Data mutasi kas berhasil dihapus.', variant: 'success' })");
    }

    // --- REKENING OPERATIONS ---

    public function showAddRekening()
    {
        $this->resetValidation();
        $this->reset(['rekeningId', 'nama_rekening', 'kode_rekening', 'nama_bank', 'no_rekening', 'atas_nama', 'saldo_saat_ini', 'bankSearch']);
        $this->is_active = true;
        $this->is_cash = false;
        $this->js("Flux.modal('rekening-modal').show()");
    }

    public function showEditRekening($id)
    {
        $this->resetValidation();
        $rekening = RekeningKoperasi::findOrFail($id);
        $this->rekeningId = $rekening->id;
        $this->nama_rekening = $rekening->nama_rekening;
        $this->kode_rekening = $rekening->kode_rekening;
        $this->nama_bank = $rekening->nama_bank;
        $this->no_rekening = $rekening->no_rekening;
        $this->atas_nama = $rekening->atas_nama;
        $this->saldo_saat_ini = $rekening->saldo_saat_ini;
        $this->is_active = (bool) $rekening->is_active;
        $this->is_cash = (bool) $rekening->is_cash;
        
        if ($this->is_cash) {
            $this->bankSearch = '';
        } else {
            $this->bankSearch = $rekening->kode_rekening . ' - ' . $rekening->nama_bank;
        }

        $this->js("Flux.modal('rekening-modal').show()");
    }

    public function saveRekening()
    {
        $rules = [
            'nama_rekening' => 'required|string|max:255',
            'saldo_saat_ini' => 'required|numeric',
            'is_active' => 'required|boolean',
        ];

        if (!$this->is_cash) {
            $rules['kode_rekening'] = 'required|string|max:100';
            $rules['nama_bank'] = 'required|string|max:255';
            $rules['no_rekening'] = 'required|string|max:255';
            $rules['atas_nama'] = 'required|string|max:255';
        }

        $this->validate($rules, [
            'nama_rekening.required' => 'Nama rekening kas wajib diisi.',
            'kode_rekening.required' => 'Kode rekening kas wajib diisi.',
            'nama_bank.required' => 'Nama bank wajib diisi.',
            'no_rekening.required' => 'Nomor rekening wajib diisi.',
            'atas_nama.required' => 'Atas nama pemilik rekening wajib diisi.',
            'saldo_saat_ini.required' => 'Saldo awal wajib diisi.',
        ]);

        $kodeRekening = $this->is_cash ? 'CASH' : $this->kode_rekening;
        $namaBank = $this->is_cash ? 'CASH' : $this->nama_bank;
        $noRekening = $this->is_cash ? null : $this->no_rekening;
        $atasNama = $this->is_cash ? null : $this->atas_nama;

        $data = [
            'nama_rekening' => $this->nama_rekening,
            'kode_rekening' => $kodeRekening,
            'nama_bank' => $namaBank,
            'no_rekening' => $noRekening,
            'atas_nama' => $atasNama,
            'saldo_saat_ini' => $this->saldo_saat_ini,
            'is_cash' => (bool) $this->is_cash,
            'is_active' => (bool) $this->is_active,
        ];

        if ($this->rekeningId) {
            $rekening = RekeningKoperasi::findOrFail($this->rekeningId);
            $rekening->update($data);
            $this->js("Flux.toast({ text: 'Data Rekening Koperasi berhasil diperbarui.', variant: 'success' })");
        } else {
            RekeningKoperasi::create($data);
            $this->js("Flux.toast({ text: 'Rekening Koperasi baru berhasil ditambahkan.', variant: 'success' })");
        }

        $this->js("Flux.modal('rekening-modal').close()");
    }

    public function deleteRekening($id)
    {
        $rekening = RekeningKoperasi::findOrFail($id);

        if ($rekening->mutasiKasKoperasi()->exists()) {
            $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Rekening ini memiliki riwayat mutasi kas aktif.', variant: 'danger' })");
            return;
        }

        $rekening->delete();
        $this->js("Flux.toast({ text: 'Rekening Koperasi berhasil dihapus.', variant: 'success' })");
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterRekening', 'filterJenis', 'filterKategori', 'filterMetode', 'dateFrom', 'dateTo']);
    }
};
?>

<div>
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Pengelolaan Kas Koperasi</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Kelola dan pantau seluruh transaksi kas internal, rekening bank koperasi, dan saldo konsolidasi.</flux:text>
        </div>
        <div class="flex gap-2">
            @if($activeTab === 'mutasi')
                <flux:button wire:click="showAddMutation" variant="primary" icon="plus" :disabled="$this->rekeningOptions->isEmpty()">Catat Mutasi Kas</flux:button>
            @else
                <flux:button wire:click="showAddRekening" variant="primary" icon="plus">Tambah Rekening Kas</flux:button>
            @endif
        </div>
    </div>

    <!-- TABS NAVIGATION -->
    <div class="flex border-b border-zinc-200 dark:border-zinc-700 mb-6 gap-6">
        <button 
            wire:click="switchTab('mutasi')" 
            class="pb-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'mutasi' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Mutasi Kas (Transaksi)
        </button>
        <button 
            wire:click="switchTab('rekening')" 
            class="pb-3 text-sm font-semibold border-b-2 transition-all {{ $activeTab === 'rekening' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            Rekening Kas Koperasi
        </button>
    </div>

    @if($activeTab === 'mutasi')
        <!-- ==================== TAB 1: MUTASI KAS ==================== -->
        <!-- Stats Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <flux:card class="hover:shadow-md transition-all duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Total Pemasukan</flux:text>
                        <flux:heading size="xl" class="mt-2 text-green-600 dark:text-green-400 font-bold">
                            Rp {{ number_format($this->stats['total_pemasukan'], 0, ',', '.') }}
                        </flux:heading>
                    </div>
                    <div class="p-2 bg-green-50 dark:bg-green-950/30 rounded-lg text-green-600 dark:text-green-400">
                        <flux:icon name="arrow-down-left" class="w-5 h-5" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="hover:shadow-md transition-all duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Total Pengeluaran</flux:text>
                        <flux:heading size="xl" class="mt-2 text-rose-600 dark:text-rose-400 font-bold">
                            Rp {{ number_format($this->stats['total_pengeluaran'], 0, ',', '.') }}
                        </flux:heading>
                    </div>
                    <div class="p-2 bg-rose-50 dark:bg-rose-950/30 rounded-lg text-rose-600 dark:text-rose-400">
                        <flux:icon name="arrow-up-right" class="w-5 h-5" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="hover:shadow-md transition-all duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Saldo Konsolidasi</flux:text>
                        <flux:heading size="xl" class="mt-2 text-blue-600 dark:text-blue-400 font-bold">
                            Rp {{ number_format($this->stats['saldo_konsolidasi'], 0, ',', '.') }}
                        </flux:heading>
                    </div>
                    <div class="p-2 bg-blue-50 dark:bg-blue-950/30 rounded-lg text-blue-600 dark:text-blue-400">
                        <flux:icon name="wallet" class="w-5 h-5" />
                    </div>
                </div>
            </flux:card>

            <flux:card class="hover:shadow-md transition-all duration-200">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500">Jumlah Rekening</flux:text>
                        <flux:heading size="xl" class="mt-2 text-zinc-700 dark:text-zinc-200 font-bold">
                            {{ $this->stats['rekening_count'] }} Rekening
                        </flux:heading>
                    </div>
                    <div class="p-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg text-zinc-700 dark:text-zinc-300">
                        <flux:icon name="credit-card" class="w-5 h-5" />
                    </div>
                </div>
            </flux:card>
        </div>

        @if($this->rekeningOptions->isEmpty())
            <div class="p-6 border border-amber-200 dark:border-amber-900 rounded-xl bg-amber-50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300 flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div>
                    <h4 class="font-semibold text-sm">Belum ada Rekening Kas Koperasi aktif</h4>
                    <p class="text-xs mt-1">Anda harus mendaftarkan setidaknya satu Rekening Kas Koperasi terlebih dahulu sebelum dapat mencatat transaksi Mutasi Kas.</p>
                </div>
                <flux:button wire:click="switchTab('rekening')" size="sm" variant="filled" class="bg-amber-600 text-white hover:bg-amber-700">Pindah ke Tab Rekening</flux:button>
            </div>
        @endif

        <!-- Filter and Mutation Table -->
        <flux:card class="flex flex-col mt-4">
            <!-- Search and Filter Bar -->
            <div class="space-y-4 mb-4">
                <div class="flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center">
                    <flux:heading size="lg" level="2">Daftar Transaksi Kas</flux:heading>
                    
                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                        <flux:input wire:model.live.debounce.300ms="search" size="sm" class="sm:max-w-xs" placeholder="Cari keterangan, nomor, bank..." icon="magnifying-glass" />
                        <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">Reset Filter</flux:button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:field>
                        <flux:label class="text-xs">Rekening Koperasi</flux:label>
                        <flux:select wire:model.live="filterRekening" placeholder="Semua Rekening" size="sm">
                            @foreach($this->rekeningOptions as $ro)
                                <flux:select.option value="{{ $ro->id }}">{{ $ro->nama_rekening }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs">Jenis</flux:label>
                        <flux:select wire:model.live="filterJenis" placeholder="Semua Jenis" size="sm">
                            <flux:select.option value="pemasukan">Pemasukan</flux:select.option>
                            <flux:select.option value="pengeluaran">Pengeluaran</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs">Kategori</flux:label>
                        <flux:select wire:model.live="filterKategori" placeholder="Semua Kategori" size="sm">
                            <flux:select.option value="payroll">Payroll Anggota</flux:select.option>
                            <flux:select.option value="pembiayaan">Pembiayaan</flux:select.option>
                            <flux:select.option value="pinjaman">Pinjaman Karyawan</flux:select.option>
                            <flux:select.option value="penarikan_saldo">Penarikan Saldo</flux:select.option>
                            <flux:select.option value="ppob">Pembayaran PPOB</flux:select.option>
                            <flux:select.option value="toko">Penjualan Toko</flux:select.option>
                            <flux:select.option value="operasional">Biaya Operasional</flux:select.option>
                            <flux:select.option value="lazis">Program LAZIS</flux:select.option>
                            <flux:select.option value="koreksi_saldo">Koreksi Saldo</flux:select.option>
                            <flux:select.option value="migrasi_saldo">Saldo Migrasi</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs">Metode</flux:label>
                        <flux:select wire:model.live="filterMetode" placeholder="Semua Metode" size="sm">
                            <flux:select.option value="transfer">Transfer Bank</flux:select.option>
                            <flux:select.option value="cash">Tunai (Cash)</flux:select.option>
                            <flux:select.option value="payroll">Payroll Potongan</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs">Dari Tanggal</flux:label>
                        <flux:input type="date" wire:model.live="dateFrom" size="sm" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="text-xs">Sampai Tanggal</flux:label>
                        <flux:input type="date" wire:model.live="dateTo" size="sm" />
                    </flux:field>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <flux:table :paginate="$this->mutasiList">
                    <flux:table.columns>
                        <flux:table.column>Tanggal</flux:table.column>
                        <flux:table.column>Rekening Kas</flux:table.column>
                        <flux:table.column>Jenis</flux:table.column>
                        <flux:table.column>Kategori</flux:table.column>
                        <flux:table.column>Metode</flux:table.column>
                        <flux:table.column>Nominal</flux:table.column>
                        <flux:table.column>Keterangan</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->mutasiList as $m)
                            <flux:table.row :key="$m->id">
                                <flux:table.cell class="whitespace-nowrap text-xs text-zinc-500">
                                    {{ \Carbon\Carbon::parse($m->tanggal_transaksi)->format('d/m/Y H:i') }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="text-sm">
                                        <span class="font-medium text-zinc-900 dark:text-white block">{{ $m->rekeningKoperasi->nama_rekening ?? '-' }}</span>
                                        <span class="text-xs text-zinc-400 block font-mono">{{ $m->rekeningKoperasi->nama_bank ?? '' }} - {{ $m->rekeningKoperasi->no_rekening ?? '' }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($m->jenis_transaksi === 'pemasukan')
                                        <flux:badge color="green" size="sm" icon="arrow-down-left">Pemasukan</flux:badge>
                                    @else
                                        <flux:badge color="red" size="sm" icon="arrow-up-right">Pengeluaran</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    @if($m->kategori_transaksi === 'payroll')
                                        <flux:badge color="indigo" size="sm">Payroll Anggota</flux:badge>
                                    @elseif($m->kategori_transaksi === 'pembiayaan')
                                        <flux:badge color="cyan" size="sm">Pembiayaan</flux:badge>
                                    @elseif($m->kategori_transaksi === 'pinjaman')
                                        <flux:badge color="rose" size="sm">Pinjaman</flux:badge>
                                    @elseif($m->kategori_transaksi === 'penarikan_saldo')
                                        <flux:badge color="amber" size="sm">Penarikan Saldo</flux:badge>
                                    @elseif($m->kategori_transaksi === 'ppob')
                                        <flux:badge color="orange" size="sm">PPOB</flux:badge>
                                    @elseif($m->kategori_transaksi === 'toko')
                                        <flux:badge color="teal" size="sm">Toko</flux:badge>
                                    @elseif($m->kategori_transaksi === 'operasional')
                                        <flux:badge color="zinc" size="sm">Operasional</flux:badge>
                                    @elseif($m->kategori_transaksi === 'lazis')
                                        <flux:badge color="emerald" size="sm">Lazis</flux:badge>
                                    @elseif($m->kategori_transaksi === 'koreksi_saldo')
                                        <flux:badge color="purple" size="sm">Koreksi Saldo</flux:badge>
                                    @elseif($m->kategori_transaksi === 'migrasi_saldo')
                                        <flux:badge color="blue" size="sm">Migrasi Saldo</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ $m->kategori_transaksi }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="capitalize text-sm">{{ $m->metode_transaksi }}</flux:table.cell>
                                <flux:table.cell class="font-bold whitespace-nowrap {{ $m->jenis_transaksi === 'pemasukan' ? 'text-green-600 dark:text-green-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $m->jenis_transaksi === 'pemasukan' ? '+' : '-' }} Rp {{ number_format($m->nominal, 0, ',', '.') }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-xs truncate text-zinc-600 dark:text-zinc-400 text-sm" title="{{ $m->keterangan }}">
                                    {{ $m->keterangan ?? '-' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                        <flux:menu>
                                            <flux:menu.item icon="pencil-square" wire:click="showEditMutation({{ $m->id }})">Edit</flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger" wire:click="deleteMutation({{ $m->id }})" wire:confirm="Apakah Anda yakin ingin menghapus data mutasi kas ini? Saldo rekening kas koperasi akan secara otomatis disesuaikan.">Hapus</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center py-8 text-zinc-500">
                                    Tidak ada data mutasi kas yang ditemukan.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    @else
        <!-- ==================== TAB 2: REKENING KOPERASI ==================== -->
        <flux:card class="flex flex-col mt-4">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg" level="2">Daftar Rekening Bank & Kas Koperasi</flux:heading>
            </div>

            <div class="overflow-x-auto">
                <flux:table class="mt-3">
                    <flux:table.columns>
                        <flux:table.column>Nama Rekening</flux:table.column>
                        <flux:table.column>Kode Rekening</flux:table.column>
                        <flux:table.column>Bank</flux:table.column>
                        <flux:table.column>No. Rekening</flux:table.column>
                        <flux:table.column>Atas Nama</flux:table.column>
                        <flux:table.column>Saldo Saat Ini</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse($this->rekeningList as $r)
                            <flux:table.row :key="$r->id">
                                <flux:table.cell class="font-semibold text-zinc-900 dark:text-white">{{ $r->nama_rekening }}</flux:table.cell>
                                <flux:table.cell class="font-mono text-sm">
                                    @if($r->is_cash)
                                        <flux:badge color="zinc" size="sm">CASH</flux:badge>
                                    @else
                                        {{ $r->kode_rekening }}
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-medium">
                                    @if($r->is_cash)
                                        <span class="text-zinc-450 italic">Kas Tunai</span>
                                    @else
                                        {{ $r->nama_bank }}
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="font-mono text-sm">{{ $r->no_rekening ?? '-' }}</flux:table.cell>
                                <flux:table.cell>{{ $r->atas_nama ?? '-' }}</flux:table.cell>
                                <flux:table.cell class="font-bold text-zinc-900 dark:text-zinc-100">
                                    Rp {{ number_format($r->saldo_saat_ini, 0, ',', '.') }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if($r->is_active)
                                        <flux:badge color="green" size="sm">Aktif</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Nonaktif</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                        <flux:menu>
                                            <flux:menu.item icon="pencil-square" wire:click="showEditRekening({{ $r->id }})">Edit</flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger" wire:click="deleteRekening({{ $r->id }})" wire:confirm="Apakah Anda yakin ingin menghapus rekening koperasi ini?">Hapus</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="8" class="text-center py-8 text-zinc-500">
                                    Belum ada data rekening koperasi. Klik "Tambah Rekening Kas" untuk membuatnya.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </flux:card>
    @endif

    <!-- ==================== MODAL MUTASI KAS ==================== -->
    <flux:modal name="mutation-modal" class="md:w-lg max-h-[90vh] overflow-y-auto">
        <form wire:submit="saveMutation" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $mutasiId ? 'Edit' : 'Catat' }} Mutasi Kas Koperasi</flux:heading>
                <flux:text size="sm" class="mt-1">Pencatatan langsung arus kas keluar dan masuk koperasi secara akurat.</flux:text>
            </div>

            <flux:separator variant="subtle" />

            <div class="space-y-5">
                <!-- Rekening Koperasi -->
                <flux:field>
                    <flux:label>Rekening Kas Koperasi <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="rekening_koperasi_id">
                        <option value="">-- Pilih Rekening Kas --</option>
                        @foreach($this->rekeningOptions as $ro)
                            <flux:select.option value="{{ $ro->id }}">{{ $ro->nama_rekening }} ({{ $ro->nama_bank }} - {{ $ro->no_rekening }} • Saldo: Rp {{ number_format($ro->saldo_saat_ini, 0, ',', '.') }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="rekening_koperasi_id" />
                </flux:field>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Jenis Transaksi -->
                    <flux:field>
                        <flux:label>Jenis Mutasi <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="jenis_transaksi">
                            <flux:select.option value="pemasukan">Pemasukan (Uang Masuk)</flux:select.option>
                            <flux:select.option value="pengeluaran">Pengeluaran (Uang Keluar)</flux:select.option>
                        </flux:select>
                        <flux:error name="jenis_transaksi" />
                    </flux:field>

                    <!-- Kategori Transaksi -->
                    <flux:field>
                        <flux:label>Kategori Mutasi <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="kategori_transaksi">
                            <flux:select.option value="payroll">Payroll Anggota</flux:select.option>
                            <flux:select.option value="pembiayaan">Pembiayaan</flux:select.option>
                            <flux:select.option value="pinjaman">Pinjaman Karyawan</flux:select.option>
                            <flux:select.option value="penarikan_saldo">Penarikan Saldo</flux:select.option>
                            <flux:select.option value="ppob">Pembayaran PPOB</flux:select.option>
                            <flux:select.option value="toko">Penjualan Toko</flux:select.option>
                            <flux:select.option value="operasional">Biaya Operasional</flux:select.option>
                            <flux:select.option value="lazis">Program LAZIS</flux:select.option>
                            <flux:select.option value="koreksi_saldo">Koreksi Saldo</flux:select.option>
                            <flux:select.option value="migrasi_saldo">Saldo Migrasi</flux:select.option>
                        </flux:select>
                        <flux:error name="kategori_transaksi" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nominal -->
                    <flux:field>
                        <flux:label>Nominal (Rp) <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" wire:model="nominal" min="1" placeholder="Contoh: 1500000" />
                        <flux:error name="nominal" />
                    </flux:field>

                    <!-- Metode Transaksi -->
                    <flux:field>
                        <flux:label>Metode Transaksi <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="metode_transaksi">
                            <flux:select.option value="transfer">Transfer Bank</flux:select.option>
                            <flux:select.option value="cash">Tunai (Cash)</flux:select.option>
                            <flux:select.option value="payroll">Payroll Potongan</flux:select.option>
                        </flux:select>
                        <flux:error name="metode_transaksi" />
                    </flux:field>
                </div>

                <!-- Tanggal Transaksi -->
                <flux:field>
                    <flux:label>Tanggal Transaksi <span class="text-red-500">*</span></flux:label>
                    <flux:input type="datetime-local" wire:model="tanggal_transaksi" />
                    <flux:error name="tanggal_transaksi" />
                </flux:field>

                <!-- Keterangan -->
                <flux:field>
                    <flux:label>Keterangan / Keterangan Tambahan</flux:label>
                    <flux:textarea wire:model="keterangan" rows="2" placeholder="Catatan transaksi mutasi kas..." />
                    <flux:error name="keterangan" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button variant="subtle" x-on:click="$flux.modal('mutation-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Mutasi</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- ==================== MODAL REKENING KOPERASI ==================== -->
    <flux:modal name="rekening-modal" class="md:w-lg max-h-[90vh] overflow-y-auto">
        <form wire:submit="saveRekening" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $rekeningId ? 'Edit' : 'Tambah' }} Rekening Kas Koperasi</flux:heading>
                <flux:text size="sm" class="mt-1">Kelola data rekening bank dan kas internal milik koperasi.</flux:text>
            </div>

            <flux:separator variant="subtle" />
            <div class="space-y-5">
                <!-- Tipe Kas -->
                <flux:field>
                    <flux:label>Tipe Kas <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model.live="is_cash">
                        <flux:select.option value="0">Rekening Bank (Transfer/Payroll)</flux:select.option>
                        <flux:select.option value="1">Kas Tunai / Cash</flux:select.option>
                    </flux:select>
                    <flux:error name="is_cash" />
                </flux:field>

                @if(!$is_cash)
                    <!-- Search Bank from NamaBank Model -->
                    <div x-data="{ open: false }" class="relative w-full">
                        <flux:field>
                            <flux:label>Pilih Bank <span class="text-red-500">*</span></flux:label>
                            <flux:input 
                                type="text" 
                                placeholder="Cari Bank dari Master Data Bank..." 
                                wire:model.live="bankSearch"
                                x-on:focus="open = true"
                                x-on:click="open = true"
                                x-on:keydown.enter.prevent=""
                                icon="magnifying-glass"
                            />
                            
                            <div 
                                x-show="open" 
                                x-on:click.outside="open = false"
                                class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-48 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700"
                                style="display: none;"
                                x-transition
                            >
                                @forelse($this->availableBanks as $bank)
                                    <div 
                                        x-on:click="open = false; $wire.selectBank('{{ $bank->kode_bank }}', '{{ $bank->nama_bank }}')"
                                        class="px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer text-sm text-zinc-900 dark:text-zinc-100 flex justify-between"
                                    >
                                        <span class="font-medium">{{ $bank->nama_bank }}</span>
                                        <span class="font-mono text-zinc-400 text-xs">{{ $bank->kode_bank }}</span>
                                    </div>
                                @empty
                                    <div class="px-4 py-2 text-sm text-zinc-500">Bank tidak ditemukan.</div>
                                @endforelse
                            </div>
                            <flux:error name="nama_bank" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Kode Rekening -->
                        <flux:field>
                            <flux:label>Kode Rekening / Bank</flux:label>
                            <flux:input type="text" wire:model="kode_rekening" readonly class="bg-zinc-50 dark:bg-zinc-900 text-zinc-500" />
                            <flux:error name="kode_rekening" />
                        </flux:field>

                        <!-- Nama Bank -->
                        <flux:field>
                            <flux:label>Nama Bank</flux:label>
                            <flux:input type="text" wire:model="nama_bank" readonly class="bg-zinc-50 dark:bg-zinc-900 text-zinc-500" />
                            <flux:error name="nama_bank" />
                        </flux:field>
                    </div>
                @endif

                <div class="grid grid-cols-1 {{ $is_cash ? '' : 'sm:grid-cols-2' }} gap-4">
                    <!-- Nama Rekening -->
                    <flux:field>
                        <flux:label>Nama Rekening Kas <span class="text-red-500">*</span></flux:label>
                        <flux:input type="text" wire:model="nama_rekening" placeholder="Contoh: BCA Koperasi" />
                        <flux:error name="nama_rekening" />
                    </flux:field>

                    @if(!$is_cash)
                        <!-- Nomor Rekening -->
                        <flux:field>
                            <flux:label>Nomor Rekening <span class="text-red-500">*</span></flux:label>
                            <flux:input type="text" wire:model="no_rekening" placeholder="Contoh: 12400092144" />
                            <flux:error name="no_rekening" />
                        </flux:field>
                    @endif
                </div>

                @if(!$is_cash)
                    <!-- Atas Nama -->
                    <flux:field>
                        <flux:label>Atas Nama Pemilik <span class="text-red-500">*</span></flux:label>
                        <flux:input type="text" wire:model="atas_nama" placeholder="Contoh: KOPKAR INCOE" />
                        <flux:error name="atas_nama" />
                    </flux:field>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Saldo Saat Ini -->
                    <flux:field>
                        <flux:label>Saldo Saat Ini (Rp) <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" wire:model="saldo_saat_ini" placeholder="Contoh: 5000000" />
                        <flux:error name="saldo_saat_ini" />
                    </flux:field>

                    <!-- Status Aktif -->
                    <flux:field class="flex flex-col justify-end pb-2">
                        <flux:checkbox wire:model="is_active" label="Rekening Aktif" />
                        <flux:error name="is_active" />
                    </flux:field>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button variant="subtle" x-on:click="$flux.modal('rekening-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Rekening</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

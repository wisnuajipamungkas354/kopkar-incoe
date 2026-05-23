<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\TransaksiMutasi;
use App\Models\User;
use Carbon\Carbon;

new #[Layout('layouts::admin', ['title' => 'Mutasi Transaksi'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $filterKategori = '';
    public $filterMetode = '';
    public $filterStatus = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 10;

    // Selected transaction for details
    public $selectedTx = null;

    // Add cash transaction properties
    public $add_user_id = '';
    public $add_kategori_transaksi = 'sukarela';
    public $add_jenis_transaksi = 'setoran_tambahan';
    public $add_nominal = '';
    public $add_tanggal_transaksi = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterKategori' => ['except' => ''],
        'filterMetode' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => '']
    ];

    public function mount()
    {
        $this->add_tanggal_transaksi = Carbon::now()->format('Y-m-d\TH:i');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterKategori() { $this->resetPage(); }
    public function updatingFilterMetode() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }

    #[Computed]
    public function stats()
    {
        return [
            'total_masuk_sukses' => TransaksiMutasi::where('status_pembayaran', 'success')
                ->whereIn('jenis_transaksi', ['setoran_awal', 'payroll_rutin', 'setoran_tambahan', 'angsuran_bulanan'])
                ->sum('nominal'),
            'total_keluar_sukses' => TransaksiMutasi::where('status_pembayaran', 'success')
                ->whereIn('jenis_transaksi', ['pencairan_dana'])
                ->sum('nominal'),
            'total_pending' => TransaksiMutasi::where('status_pembayaran', 'pending')->count(),
            'total_hari_ini' => TransaksiMutasi::whereDate('tanggal_transaksi', Carbon::today())->count(),
        ];
    }

    #[Computed]
    public function transactions()
    {
        return TransaksiMutasi::with(['user', 'transaksiMutasiQris', 'ppobDetailTagihan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nomor_transaksi', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function ($uq) {
                          $uq->where('nama_anggota', 'like', '%' . $this->search . '%')
                             ->orWhere('username', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->filterKategori, function ($query) {
                $query->where('kategori_transaksi', $this->filterKategori);
            })
            ->when($this->filterMetode, function ($query) {
                $query->where('metode_pembayaran', $this->filterMetode);
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status_pembayaran', $this->filterStatus);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('tanggal_transaksi', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('tanggal_transaksi', '<=', $this->dateTo);
            })
            ->orderBy('tanggal_transaksi', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function users()
    {
        return User::where('status_user', 1)
            ->orderBy('nama_anggota')
            ->get();
    }

    public function showDetail($id)
    {
        $this->selectedTx = TransaksiMutasi::with(['user', 'transaksiMutasiQris', 'ppobDetailTagihan'])->find($id);
        if ($this->selectedTx) {
            $this->js("Flux.modal('detail-modal').show()");
        }
    }

    public function showAdd()
    {
        $this->resetValidation();
        $this->reset(['add_user_id', 'add_nominal']);
        $this->add_kategori_transaksi = 'sukarela';
        $this->add_jenis_transaksi = 'setoran_tambahan';
        $this->add_tanggal_transaksi = Carbon::now()->format('Y-m-d\TH:i');
        $this->js("Flux.modal('add-modal').show()");
    }

    public function store()
    {
        $this->validate([
            'add_user_id' => 'required|exists:users,id',
            'add_kategori_transaksi' => 'required|in:pokok,wajib,sukarela,shu,smp_lain_lain,lazis,ppob,pembiayaan,pinjaman',
            'add_jenis_transaksi' => 'required|in:setoran_awal,payroll_rutin,setoran_tambahan,pencairan_dana,angsuran_bulanan',
            'add_nominal' => 'required|numeric|min:1',
            'add_tanggal_transaksi' => 'required',
        ], [
            'add_user_id.required' => 'Anggota wajib dipilih.',
            'add_user_id.exists' => 'Anggota tidak valid.',
            'add_nominal.required' => 'Nominal wajib diisi.',
            'add_nominal.numeric' => 'Nominal harus berupa angka.',
            'add_nominal.min' => 'Nominal tidak boleh kurang dari Rp 1.',
            'add_tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
        ]);

        TransaksiMutasi::create([
            'user_id' => $this->add_user_id,
            'kategori_transaksi' => $this->add_kategori_transaksi,
            'jenis_transaksi' => $this->add_jenis_transaksi,
            'metode_pembayaran' => 'cash',
            'nominal' => $this->add_nominal,
            'status_pembayaran' => 'success',
            'admin_user_id' => auth()->id(),
            'tanggal_transaksi' => $this->add_tanggal_transaksi,
        ]);

        $this->js("Flux.modal('add-modal').close()");
        $this->js("Flux.toast({ text: 'Transaksi manual (cash) berhasil dicatat', variant: 'success' })");
    }

    public function updateStatus($id, $status)
    {
        $tx = TransaksiMutasi::findOrFail($id);
        
        $updateData = [
            'status_pembayaran' => $status
        ];

        if ($tx->metode_pembayaran === 'cash') {
            $updateData['admin_user_id'] = auth()->id();
        }

        $tx->update($updateData);

        $this->js("Flux.toast({ text: 'Status transaksi berhasil diperbarui', variant: 'success' })");
        
        // Refresh detail if open
        if ($this->selectedTx && $this->selectedTx->id === $id) {
            $this->selectedTx = TransaksiMutasi::with(['user', 'transaksiMutasiQris', 'ppobDetailTagihan'])->find($id);
        }
    }

    public function delete($id)
    {
        $tx = TransaksiMutasi::find($id);
        if ($tx) {
            $tx->delete();
            $this->js("Flux.toast({ text: 'Transaksi berhasil dihapus', variant: 'success' })");
        }
    }

    public function resetFilters()
    {
        $this->reset(['search', 'filterKategori', 'filterMetode', 'filterStatus', 'dateFrom', 'dateTo']);
    }
};
?>

<div>
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Mutasi & Riwayat Transaksi</flux:heading>
            <flux:text class="mt-2 text-base">Pantau, saring, dan catat semua riwayat transaksi masuk dan keluar Koperasi.</flux:text>
        </div>
        <flux:button wire:click="showAdd" variant="primary" icon="plus">Catat Transaksi Cash</flux:button>
    </div>

    <flux:separator variant="subtle" />

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
        <flux:card class="hover:shadow-md transition-all duration-200">
            <div class="flex justify-between items-start">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">Total Setoran / Masuk (Sukses)</flux:text>
                    <flux:heading size="xl" class="mt-2 text-green-600 dark:text-green-400">
                        Rp {{ number_format($this->stats['total_masuk_sukses'], 0, ',', '.') }}
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
                    <flux:text class="text-sm font-medium text-zinc-500">Total Pencairan / Keluar (Sukses)</flux:text>
                    <flux:heading size="xl" class="mt-2 text-rose-600 dark:text-rose-400">
                        Rp {{ number_format($this->stats['total_keluar_sukses'], 0, ',', '.') }}
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
                    <flux:text class="text-sm font-medium text-zinc-500">Transaksi Pending</flux:text>
                    <flux:heading size="xl" class="mt-2 text-amber-600 dark:text-amber-400">
                        {{ number_format($this->stats['total_pending'], 0, ',', '.') }}
                    </flux:heading>
                </div>
                <div class="p-2 bg-amber-50 dark:bg-amber-950/30 rounded-lg text-amber-600 dark:text-amber-400">
                    <flux:icon name="clock" class="w-5 h-5" />
                </div>
            </div>
        </flux:card>

        <flux:card class="hover:shadow-md transition-all duration-200">
            <div class="flex justify-between items-start">
                <div>
                    <flux:text class="text-sm font-medium text-zinc-500">Transaksi Hari Ini</flux:text>
                    <flux:heading size="xl" class="mt-2 text-blue-600 dark:text-blue-400">
                        {{ number_format($this->stats['total_hari_ini'], 0, ',', '.') }}
                    </flux:heading>
                </div>
                <div class="p-2 bg-blue-50 dark:bg-blue-950/30 rounded-lg text-blue-600 dark:text-blue-400">
                    <flux:icon name="calendar" class="w-5 h-5" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters and Table Container -->
    <flux:card class="flex flex-col mt-6">
        <!-- Search and Filters Panel -->
        <div class="space-y-4 mb-4">
            <div class="flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center">
                <flux:heading size="lg" level="2">Daftar Transaksi</flux:heading>
                
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <flux:input wire:model.live.debounce.300ms="search" size="sm" class="sm:max-w-xs" placeholder="Cari nomor, nama, NPK..." icon="magnifying-glass" />
                    
                    <div class="flex gap-2 self-end">
                        <flux:button wire:click="resetFilters" variant="ghost" size="sm" icon="arrow-path">Reset Filter</flux:button>
                    </div>
                </div>
            </div>

            <!-- Advance Filters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:field>
                    <flux:label class="text-xs">Kategori</flux:label>
                    <flux:select wire:model.live="filterKategori" placeholder="Semua Kategori" size="sm">
                        <flux:select.option value="pokok">Simpanan Pokok</flux:select.option>
                        <flux:select.option value="wajib">Simpanan Wajib</flux:select.option>
                        <flux:select.option value="sukarela">Simpanan Sukarela</flux:select.option>
                        <flux:select.option value="shu">SHU</flux:select.option>
                        <flux:select.option value="smp_lain_lain">Simpanan Lain-lain</flux:select.option>
                        <flux:select.option value="lazis">Lazis</flux:select.option>
                        <flux:select.option value="ppob">PPOB</flux:select.option>
                        <flux:select.option value="pembiayaan">Pembiayaan</flux:select.option>
                        <flux:select.option value="pinjaman">Pinjaman</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label class="text-xs">Metode</flux:label>
                    <flux:select wire:model.live="filterMetode" placeholder="Semua Metode" size="sm">
                        <flux:select.option value="payroll">Payroll</flux:select.option>
                        <flux:select.option value="qris">QRIS</flux:select.option>
                        <flux:select.option value="cash">Cash/Manual</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label class="text-xs">Status</flux:label>
                    <flux:select wire:model.live="filterStatus" placeholder="Semua Status" size="sm">
                        <flux:select.option value="pending">Pending</flux:select.option>
                        <flux:select.option value="success">Success</flux:select.option>
                        <flux:select.option value="failed">Failed</flux:select.option>
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

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
            <flux:table :paginate="$this->transactions">
                <flux:table.columns>
                    <flux:table.column>Anggota</flux:table.column>
                    <flux:table.column>No. Transaksi</flux:table.column>
                    <flux:table.column>Kategori & Jenis</flux:table.column>
                    <flux:table.column>Metode</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($this->transactions as $tx)
                        <flux:table.row :key="$tx->id">
                            <!-- Anggota Info -->
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($tx->user->nama_anggota ?? 'A', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $tx->user->nama_anggota ?? 'Unknown' }}</span>
                                        <span class="text-xs text-zinc-500">{{ $tx->user->username ?? '-' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <!-- Nomor Transaksi -->
                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $tx->nomor_transaksi }}
                            </flux:table.cell>

                            <!-- Kategori & Jenis -->
                            <flux:table.cell>
                                <div class="flex flex-col gap-1 items-start">
                                    <!-- Kategori Badge -->
                                    @if($tx->kategori_transaksi === 'pokok')
                                        <flux:badge color="blue" size="sm" inset="top bottom">Pokok</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'wajib')
                                        <flux:badge color="indigo" size="sm" inset="top bottom">Wajib</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'sukarela')
                                        <flux:badge color="green" size="sm" inset="top bottom">Sukarela</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'shu')
                                        <flux:badge color="purple" size="sm" inset="top bottom">SHU</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'smp_lain_lain')
                                        <flux:badge color="zinc" size="sm" inset="top bottom">Lain-lain</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'lazis')
                                        <flux:badge color="emerald" size="sm" inset="top bottom">Lazis</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'ppob')
                                        <flux:badge color="orange" size="sm" inset="top bottom">PPOB</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'pembiayaan')
                                        <flux:badge color="cyan" size="sm" inset="top bottom">Pembiayaan</flux:badge>
                                    @elseif($tx->kategori_transaksi === 'pinjaman')
                                        <flux:badge color="rose" size="sm" inset="top bottom">Pinjaman</flux:badge>
                                    @endif

                                    <!-- Jenis label -->
                                    <span class="text-xs text-zinc-500">
                                        @if($tx->jenis_transaksi === 'setoran_awal') Setoran Awal
                                        @elseif($tx->jenis_transaksi === 'payroll_rutin') Gaji Rutin
                                        @elseif($tx->jenis_transaksi === 'setoran_tambahan') Setoran Tambahan
                                        @elseif($tx->jenis_transaksi === 'pencairan_dana') Pencairan Dana
                                        @elseif($tx->jenis_transaksi === 'angsuran_bulanan') Angsuran Bulanan
                                        @else {{ $tx->jenis_transaksi }}
                                        @endif
                                    </span>
                                </div>
                            </flux:table.cell>

                            <!-- Metode Pembayaran -->
                            <flux:table.cell>
                                <div class="flex items-center gap-1.5 text-zinc-700 dark:text-zinc-300">
                                    @if($tx->metode_pembayaran === 'payroll')
                                        <flux:icon name="credit-card" class="w-4 h-4 text-purple-500" />
                                        <span class="text-sm">Payroll</span>
                                    @elseif($tx->metode_pembayaran === 'qris')
                                        <flux:icon name="qr-code" class="w-4 h-4 text-blue-500" />
                                        <span class="text-sm">QRIS</span>
                                    @elseif($tx->metode_pembayaran === 'cash')
                                        <flux:icon name="banknotes" class="w-4 h-4 text-green-500" />
                                        <span class="text-sm">Cash</span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <!-- Nominal -->
                            <flux:table.cell>
                                <span class="font-bold {{ $tx->jenis_transaksi === 'pencairan_dana' ? 'text-rose-600 dark:text-rose-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ $tx->jenis_transaksi === 'pencairan_dana' ? '-' : '+' }} Rp {{ number_format($tx->nominal, 0, ',', '.') }}
                                </span>
                            </flux:table.cell>

                            <!-- Tanggal -->
                            <flux:table.cell class="text-zinc-600 dark:text-zinc-400 text-sm">
                                {{ \Carbon\Carbon::parse($tx->tanggal_transaksi)->format('d/m/Y H:i') }}
                            </flux:table.cell>

                            <!-- Status -->
                            <flux:table.cell>
                                @if($tx->status_pembayaran === 'success')
                                    <flux:badge color="green" size="sm" inset="top bottom">Success</flux:badge>
                                @elseif($tx->status_pembayaran === 'pending')
                                    <flux:badge color="yellow" size="sm" inset="top bottom">Pending</flux:badge>
                                @elseif($tx->status_pembayaran === 'failed')
                                    <flux:badge color="red" size="sm" inset="top bottom">Failed</flux:badge>
                                @endif
                            </flux:table.cell>

                            <!-- Actions -->
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu class="min-w-48">
                                        <flux:menu.item icon="eye" wire:click="showDetail({{ $tx->id }})">Detail Transaksi</flux:menu.item>
                                        
                                        @if($tx->status_pembayaran === 'pending')
                                            <flux:menu.separator />
                                            <flux:menu.item icon="check" class="text-green-600 hover:text-green-700" wire:click="updateStatus({{ $tx->id }}, 'success')">Setujui (Success)</flux:menu.item>
                                            <flux:menu.item icon="x-mark" class="text-rose-600 hover:text-rose-700" wire:click="updateStatus({{ $tx->id }}, 'failed')">Tolak (Failed)</flux:menu.item>
                                        @endif

                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $tx->id }})" wire:confirm="Apakah Anda yakin ingin menghapus data mutasi transaksi ini? Seluruh saldo terkait tidak akan disesuaikan secara otomatis.">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center text-zinc-500 py-12">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <flux:icon name="document-magnifying-glass" class="w-8 h-8 text-zinc-300" />
                                    <span>Tidak ada transaksi yang cocok dengan kriteria pencarian/filter.</span>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Detail Transaction Modal -->
    <flux:modal name="detail-modal" class="md:w-xl">
        @if($selectedTx)
            <div>
                <flux:heading size="lg">Detail Transaksi</flux:heading>
                <flux:text size="sm" class="mt-1">Rincian mutasi transaksi keuangan koperasi.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Member details -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-lg font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedTx->user->nama_anggota ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedTx->user->nama_anggota ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedTx->user->username ?? '-' }} • {{ $selectedTx->user->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <!-- Transaction Details Grid -->
                <div class="grid grid-cols-2 gap-y-4 gap-x-6 text-sm">
                    <div>
                        <span class="block text-zinc-500 font-medium">Nomor Transaksi</span>
                        <span class="block font-mono text-zinc-800 dark:text-zinc-200 font-semibold">{{ $selectedTx->nomor_transaksi }}</span>
                    </div>
                    <div>
                        <span class="block text-zinc-500 font-medium">Tanggal Transaksi</span>
                        <span class="block text-zinc-800 dark:text-zinc-200">{{ \Carbon\Carbon::parse($selectedTx->tanggal_transaksi)->format('d F Y, H:i') }} WIB</span>
                    </div>
                    <div>
                        <span class="block text-zinc-500 font-medium">Kategori Transaksi</span>
                        <span class="block mt-1 font-semibold text-zinc-800 dark:text-zinc-200">
                            @if($selectedTx->kategori_transaksi === 'pokok') Simpanan Pokok
                            @elseif($selectedTx->kategori_transaksi === 'wajib') Simpanan Wajib
                            @elseif($selectedTx->kategori_transaksi === 'sukarela') Simpanan Sukarela
                            @elseif($selectedTx->kategori_transaksi === 'shu') Sisa Hasil Usaha (SHU)
                            @elseif($selectedTx->kategori_transaksi === 'smp_lain_lain') Simpanan Lain-lain
                            @elseif($selectedTx->kategori_transaksi === 'lazis') Lazis
                            @elseif($selectedTx->kategori_transaksi === 'ppob') PPOB
                            @elseif($selectedTx->kategori_transaksi === 'pembiayaan') Pembiayaan
                            @elseif($selectedTx->kategori_transaksi === 'pinjaman') Pinjaman
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="block text-zinc-500 font-medium">Jenis Aksi</span>
                        <span class="block text-zinc-800 dark:text-zinc-200">
                            @if($selectedTx->jenis_transaksi === 'setoran_awal') Setoran Awal
                            @elseif($selectedTx->jenis_transaksi === 'payroll_rutin') Gaji Rutin
                            @elseif($selectedTx->jenis_transaksi === 'setoran_tambahan') Setoran Tambahan
                            @elseif($selectedTx->jenis_transaksi === 'pencairan_dana') Pencairan Dana
                            @elseif($selectedTx->jenis_transaksi === 'angsuran_bulanan') Angsuran Bulanan
                            @else {{ $selectedTx->jenis_transaksi }}
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="block text-zinc-500 font-medium">Metode Pembayaran</span>
                        <span class="block text-zinc-800 dark:text-zinc-200 capitalize">{{ $selectedTx->metode_pembayaran }}</span>
                    </div>
                    <div>
                        <span class="block text-zinc-500 font-medium">Status Pembayaran</span>
                        <div class="mt-1">
                            @if($selectedTx->status_pembayaran === 'success')
                                <flux:badge color="green">Success</flux:badge>
                            @elseif($selectedTx->status_pembayaran === 'pending')
                                <flux:badge color="yellow">Pending</flux:badge>
                            @elseif($selectedTx->status_pembayaran === 'failed')
                                <flux:badge color="red">Failed</flux:badge>
                            @endif
                        </div>
                    </div>
                    
                    <div class="col-span-2">
                        <flux:separator variant="subtle" />
                    </div>

                    <div>
                        <span class="block text-zinc-500 font-medium">Nominal Bersih</span>
                        <span class="block text-lg font-bold {{ $selectedTx->jenis_transaksi === 'pencairan_dana' ? 'text-rose-600 dark:text-rose-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $selectedTx->jenis_transaksi === 'pencairan_dana' ? '-' : '+' }} Rp {{ number_format($selectedTx->nominal, 0, ',', '.') }}
                        </span>
                    </div>

                    @if($selectedTx->admin_user_id)
                        <div>
                            <span class="block text-zinc-500 font-medium">Admin Penanggung Jawab</span>
                            <span class="block text-zinc-800 dark:text-zinc-200">
                                @php $adm = \App\Models\User::find($selectedTx->admin_user_id); @endphp
                                {{ $adm ? $adm->nama_anggota : 'System/Admin' }} (ID: {{ $selectedTx->admin_user_id }})
                            </span>
                        </div>
                    @endif
                </div>

                <!-- QRIS specific metadata -->
                @if($selectedTx->metode_pembayaran === 'qris' && $selectedTx->transaksiMutasiQris)
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl space-y-4">
                        <flux:heading size="sm">Rincian Pembayaran QRIS</flux:heading>
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="block text-zinc-500 font-medium">Vendor Transaction ID</span>
                                <span class="block text-zinc-800 dark:text-zinc-200 font-mono">{{ $selectedTx->transaksiMutasiQris->transaction_id_vendor ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-zinc-500 font-medium">Biaya Aplikasi</span>
                                <span class="block text-zinc-800 dark:text-zinc-200">Rp {{ number_format($selectedTx->transaksiMutasiQris->fee_aplikasi_diwajibkan, 0, ',', '.') }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-zinc-500 font-medium">Total Bayar Anggota</span>
                                <span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($selectedTx->transaksiMutasiQris->total_bayar_anggota, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- QR Code Display if pending -->
                        @if($selectedTx->status_pembayaran === 'pending' && $selectedTx->transaksiMutasiQris->url_image_qris)
                            <div class="flex flex-col items-center justify-center p-4 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                                <flux:text class="text-xs text-center mb-2 font-medium">QRIS QR Code (Scan to Pay)</flux:text>
                                <img src="{{ $selectedTx->transaksiMutasiQris->url_image_qris }}" class="w-48 h-48 object-contain" alt="QRIS Code" />
                            </div>
                        @endif
                    </div>
                @endif

                <!-- PPOB specific metadata -->
                @if($selectedTx->kategori_transaksi === 'ppob' && $selectedTx->ppobDetailTagihan)
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl space-y-3">
                        <flux:heading size="sm">Rincian Transaksi PPOB</flux:heading>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="block text-zinc-500 font-medium">Layanan</span>
                                <span class="block text-zinc-800 dark:text-zinc-200 uppercase">{{ $selectedTx->ppobDetailTagihan->jenis_layanan }}</span>
                            </div>
                            <div>
                                <span class="block text-zinc-500 font-medium">Produk Vendor</span>
                                <span class="block text-zinc-800 dark:text-zinc-200">{{ $selectedTx->ppobDetailTagihan->produk_vendor }}</span>
                            </div>
                            <div>
                                <span class="block text-zinc-500 font-medium">Nomor Pelanggan</span>
                                <span class="block text-zinc-800 dark:text-zinc-200 font-mono text-sm font-semibold">{{ $selectedTx->ppobDetailTagihan->nomor_pelanggan ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-zinc-500 font-medium">Harga Pokok (HPP)</span>
                                <span class="block text-zinc-800 dark:text-zinc-200">Rp {{ number_format($selectedTx->ppobDetailTagihan->hpp, 0, ',', '.') }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-zinc-500 font-medium">Keuntungan Koperasi (Fee)</span>
                                <span class="block text-zinc-800 dark:text-zinc-200 font-semibold text-green-600">Rp {{ number_format($selectedTx->ppobDetailTagihan->fee_koperasi, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Buttons inside modal -->
                <div class="flex justify-between items-center pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <div>
                        @if($selectedTx->status_pembayaran === 'pending')
                            <div class="flex gap-2">
                                <flux:button variant="primary" icon="check" wire:click="updateStatus({{ $selectedTx->id }}, 'success')">Setuju (Success)</flux:button>
                                <flux:button variant="danger" icon="x-mark" wire:click="updateStatus({{ $selectedTx->id }}, 'failed')">Tolak (Failed)</flux:button>
                            </div>
                        @endif
                    </div>
                    <flux:button variant="subtle" x-on:click="$flux.modal('detail-modal').close()">Tutup</flux:button>
                </div>
            </div>
        @else
            <div class="py-12 text-center text-zinc-500">
                Memuat rincian transaksi...
            </div>
        @endif
    </flux:modal>

    <!-- Add Cash Transaction Modal -->
    <flux:modal name="add-modal" class="md:w-xl">
        <div>
            <flux:heading size="lg">Catat Transaksi Manual (Cash)</flux:heading>
            <flux:text size="sm" class="mt-1">Pencatatan langsung transaksi kas manual untuk anggota.</flux:text>
        </div>

        <form wire:submit="store" class="mt-6 space-y-6">
            <!-- Select User -->
            <flux:field>
                <flux:label>Pilih Anggota</flux:label>
                <flux:select wire:model="add_user_id" placeholder="Pilih Anggota..." filterable>
                    @foreach($this->users as $u)
                        <flux:select.option value="{{ $u->id }}">{{ $u->nama_anggota }} ({{ $u->username }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="add_user_id" />
            </flux:field>

            <!-- Grid for category and type -->
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Kategori Transaksi</flux:label>
                    <flux:select wire:model="add_kategori_transaksi">
                        <flux:select.option value="sukarela">Simpanan Sukarela</flux:select.option>
                        <flux:select.option value="wajib">Simpanan Wajib</flux:select.option>
                        <flux:select.option value="pokok">Simpanan Pokok</flux:select.option>
                        <flux:select.option value="lazis">Lazis</flux:select.option>
                        <flux:select.option value="shu">Sisa Hasil Usaha (SHU)</flux:select.option>
                        <flux:select.option value="smp_lain_lain">Simpanan Lain-lain</flux:select.option>
                        <flux:select.option value="pembiayaan">Pembiayaan</flux:select.option>
                        <flux:select.option value="pinjaman">Pinjaman</flux:select.option>
                    </flux:select>
                    <flux:error name="add_kategori_transaksi" />
                </flux:field>

                <flux:field>
                    <flux:label>Jenis Transaksi</flux:label>
                    <flux:select wire:model="add_jenis_transaksi">
                        <flux:select.option value="setoran_tambahan">Setoran Tambahan</flux:select.option>
                        <flux:select.option value="payroll_rutin">Payroll Rutin</flux:select.option>
                        <flux:select.option value="setoran_awal">Setoran Awal</flux:select.option>
                        <flux:select.option value="pencairan_dana">Pencairan Dana (Keluar)</flux:select.option>
                        <flux:select.option value="angsuran_bulanan">Angsuran Bulanan</flux:select.option>
                    </flux:select>
                    <flux:error name="add_jenis_transaksi" />
                </flux:field>
            </div>

            <!-- Nominal & Date -->
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Nominal (Rp)</flux:label>
                    <flux:input type="number" min="1" wire:model="add_nominal" placeholder="Contoh: 150000" />
                    <flux:error name="add_nominal" />
                </flux:field>

                <flux:field>
                    <flux:label>Tanggal Transaksi</flux:label>
                    <flux:input type="datetime-local" wire:model="add_tanggal_transaksi" />
                    <flux:error name="add_tanggal_transaksi" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button variant="subtle" x-on:click="$flux.modal('add-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Transaksi</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

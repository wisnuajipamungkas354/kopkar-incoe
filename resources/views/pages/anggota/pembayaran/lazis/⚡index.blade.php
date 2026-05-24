<?php

use App\Models\PengajuanPerubahanPotonganPayroll;
use App\Models\PotonganPayrollEmployee;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\TransaksiMutasi;
use Carbon\Carbon;
use Flux\Flux;

new #[Layout('layouts::anggota', ['title' => 'Pembayaran LAZIS'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $userId;
    public $employeeId;
    public $totalLazis = 0;
    
    // Core inputs for the consolidated modal
    public $nominalBaru = '';
    public $jenisLazisPilihan = 'zakat'; // 'zakat' or 'infaq_shodaqoh'

    // Active stats
    public $nominalSaatIniZakat = 0;
    public $pengajuanPendingZakat = 0;
    public $nominalSaatIniInfaq = 0;
    public $pengajuanPendingInfaq = 0;

    public function mount()
    {
        $user = auth('web')->user();
        $this->userId = $user->id;
        $this->employeeId = $user->userable->id;
        $this->refreshStats();
    }

    public function refreshStats()
    {
        // Hitung total LAZIS yang sukses
        $this->totalLazis = TransaksiMutasi::where('user_id', $this->userId)
            ->where('kategori_transaksi', 'lazis')
            ->where('status_pembayaran', 'success')
            ->sum('nominal');

        $tanggalSekarang = Carbon::now()->format('Y-m-d');

        // Zakat
        $zakatPotongan = PotonganPayrollEmployee::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'zakat')
            ->where('employee_id', $this->employeeId)
            ->where('tanggal_mulai_berlaku', '<=', $tanggalSekarang)
            ->latest()
            ->first();
        $this->nominalSaatIniZakat = $zakatPotongan ? $zakatPotongan->nominal : 0;

        $this->pengajuanPendingZakat = PengajuanPerubahanPotonganPayroll::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'zakat')
            ->where('employee_id', $this->employeeId)
            ->where('status', 'pending')
            ->count();

        // Infaq
        $infaqPotongan = PotonganPayrollEmployee::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'infaq_shodaqoh')
            ->where('employee_id', $this->employeeId)
            ->where('tanggal_mulai_berlaku', '<=', $tanggalSekarang)
            ->latest()
            ->first();
        $this->nominalSaatIniInfaq = $infaqPotongan ? $infaqPotongan->nominal : 0;

        $this->pengajuanPendingInfaq = PengajuanPerubahanPotonganPayroll::where('jenis_potongan', 'lazis')
            ->where('sub_jenis_potongan', 'infaq_shodaqoh')
            ->where('employee_id', $this->employeeId)
            ->where('status', 'pending')
            ->count();
    }

    public function submitUbahSetoran()
    {
        $this->validate([
            'nominalBaru' => 'required|numeric|min:0',
            'jenisLazisPilihan' => 'required|in:zakat,infaq_shodaqoh',
        ], [
            'nominalBaru.required' => 'Nominal baru wajib diisi.',
            'nominalBaru.numeric' => 'Nominal baru harus berupa angka.',
            'nominalBaru.min' => 'Nominal baru tidak boleh kurang dari Rp 0.',
            'jenisLazisPilihan.required' => 'Jenis program LAZIS wajib dipilih.',
            'jenisLazisPilihan.in' => 'Program yang dipilih tidak valid.',
        ]);

        $tanggalBerlaku = Carbon::now()->addMonths(1)->firstOfMonth()->format('Y-m-d');
        $nominalLama = $this->jenisLazisPilihan === 'zakat' ? $this->nominalSaatIniZakat : $this->nominalSaatIniInfaq;
        $subJenisPotongan = $this->jenisLazisPilihan === 'zakat' ? 'zakat' : 'infaq_shodaqoh';

        PengajuanPerubahanPotonganPayroll::create([
            'employee_id' => $this->employeeId,
            'jenis_potongan' => 'lazis',
            'sub_jenis_potongan' => $subJenisPotongan,
            'nominal_lama' => $nominalLama,
            'nominal_baru' => (int) $this->nominalBaru,
            'status' => 'pending',
            'tanggal_berlaku' => $tanggalBerlaku,
            'diajukan_oleh' => $this->userId,
            'tanggal_pengajuan' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->reset('nominalBaru');
        $this->refreshStats();
        
        Flux::modal('konfirmasi-ubah-setoran')->close();
        Flux::modal('sukses-ubah-setoran')->show();
    }

    #[Computed]
    public function riwayatLazis()
    {
        $query = TransaksiMutasi::where('user_id', $this->userId)
            ->where('kategori_transaksi', 'lazis');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('nomor_transaksi', 'like', '%' . $this->search . '%')
                  ->orWhere('jenis_transaksi', 'like', '%' . $this->search . '%')
                  ->orWhere('metode_pembayaran', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest()->paginate(10);
    }
};
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">Lembaga Amil Zakat, Infaq, dan Shadaqah (LAZIS)</flux:heading>
            <flux:text class="mt-2 text-base">Layanan penyaluran dan riwayat pembayaran Zakat, Infaq, serta Shadaqah anggota.</flux:text>
        </div>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <flux:modal.trigger name="ubah-setoran">
                <flux:button size="sm" variant="outline" icon="pencil-square">Ubah Setoran</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Foundation Info Banner -->
    <flux:card class="flex flex-col md:flex-row items-center gap-6 p-6 bg-gradient-to-tr from-emerald-500/10 to-indigo-500/5 border border-zinc-200 dark:border-zinc-700/50">
        <div class="w-48 h-20 shrink-0 bg-white p-2 rounded-2xl shadow-sm flex items-center justify-center">
            <img src="{{ asset('img/logo-yayasan-lazis-light.png') }}" class="object-contain max-h-16" alt="Logo Yayasan">
        </div>
        <div class="space-y-1 text-center md:text-left">
            <flux:heading size="lg">Kemitraan Lazis Yayasan Amaliah Astra (YAA)</flux:heading>
            <flux:text class="text-sm">Penyaluran Zakat, Infaq, dan Sedekah (LAZIS) diproses secara transparan dan amanah melalui kerjasama resmi dengan <strong>Yayasan Amaliah Astra</strong>.</flux:text>
        </div>
    </flux:card>

    <!-- Top Total Summary Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <flux:card class="flex items-center gap-4 md:col-span-3">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl">
                <flux:icon name="heart" class="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Donasi Ditunaikan</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->totalLazis, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>
    </div>

    <!-- 2 Types of LAZIS Cards (Display only) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Zakat Card -->
        <flux:card class="space-y-6 flex flex-col justify-between">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg text-emerald-600 dark:text-emerald-400">
                        <flux:icon name="hand-raised" variant="solid" class="w-6 h-6" />
                    </div>
                    <div>
                        <flux:heading size="lg">Zakat Profesi / Maal</flux:heading>
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Kewajiban mensucikan pendapatan bulanan Anda (potong gaji).</flux:text>
                    </div>
                </div>
                <flux:separator variant="subtle" />
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Setoran Zakat</span>
                        <span class="text-xl font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($nominalSaatIniZakat, 0, ',', '.') }}<span class="text-xs font-normal text-zinc-400">/bln</span></span>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Pengajuan Pending</span>
                        <flux:badge :color="$pengajuanPendingZakat > 0 ? 'orange' : 'zinc'" size="sm">{{ $pengajuanPendingZakat }} Berkas</flux:badge>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Infaq & Shadaqah Card -->
        <flux:card class="space-y-6 flex flex-col justify-between">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-50 dark:bg-indigo-950/40 rounded-lg text-indigo-600 dark:text-indigo-400">
                        <flux:icon name="sparkles" variant="solid" class="w-6 h-6" />
                    </div>
                    <div>
                        <flux:heading size="lg">Infaq & Shadaqah</flux:heading>
                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">Penyaluran dana kebajikan sukarela bulanan (potong gaji).</flux:text>
                    </div>
                </div>
                <flux:separator variant="subtle" />
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Setoran Infaq</span>
                        <span class="text-xl font-bold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($nominalSaatIniInfaq, 0, ',', '.') }}<span class="text-xs font-normal text-zinc-400">/bln</span></span>
                    </div>
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Pengajuan Pending</span>
                        <flux:badge :color="$pengajuanPendingInfaq > 0 ? 'orange' : 'zinc'" size="sm">{{ $pengajuanPendingInfaq }} Berkas</flux:badge>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Tabel Riwayat LAZIS -->
    <flux:card class="flex flex-col">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Riwayat Pembayaran LAZIS</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari transaksi..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>No. Transaksi</flux:table.column>
                    <flux:table.column>Jenis Potongan</flux:table.column>
                    <flux:table.column>Metode</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->riwayatLazis as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal_transaksi)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-800 dark:text-zinc-200">{{ $row->nomor_transaksi }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->jenis_transaksi === 'payroll_rutin')
                                    <flux:badge color="teal" size="sm" inset="top bottom">LAZIS Rutin</flux:badge>
                                @elseif($row->jenis_transaksi === 'setoran_tambahan')
                                    <flux:badge color="indigo" size="sm" inset="top bottom">LAZIS Tambahan</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm" inset="top bottom">{{ ucfirst(str_replace('_', ' ', $row->jenis_transaksi)) }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if(stripos($row->metode_pembayaran, 'payroll') !== false)
                                    <flux:badge color="blue" size="sm" inset="top bottom">{{ ucfirst($row->metode_pembayaran) }}</flux:badge>
                                @elseif(stripos($row->metode_pembayaran, 'qris') !== false)
                                    <flux:badge color="purple" size="sm" inset="top bottom">{{ strtoupper($row->metode_pembayaran) }}</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm" inset="top bottom">{{ ucfirst($row->metode_pembayaran) }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($row->nominal, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->status_pembayaran === 'success')
                                    <flux:badge color="green" size="sm" inset="top bottom">Berhasil</flux:badge>
                                @elseif($row->status_pembayaran === 'pending')
                                    <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm" inset="top bottom">Gagal</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-gray-500 py-6">Tidak ada data riwayat donasi.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="mt-4">
            {{ $this->riwayatLazis->links() }}
        </div>
    </flux:card>

    <!-- Modal Ubah Setoran -->
    <flux:modal name="ubah-setoran" class="md:w-lg space-y-6">
        <div>
            <flux:heading size="lg">Pengajuan Perubahan Setoran LAZIS</flux:heading>
            <flux:text size="sm" class="mt-1">Pilih jenis program LAZIS dan masukkan nominal potongan bulanan rutin yang baru.</flux:text>
        </div>

        <form x-on:submit.prevent="$flux.modal('konfirmasi-ubah-setoran').show(); $flux.modal('ubah-setoran').close()" class="flex flex-col gap-4 mt-4">
            <!-- Jenis LAZIS Pilihan -->
            <flux:field>
                <flux:label>Pilih Program LAZIS</flux:label>
                <flux:select wire:model.live="jenisLazisPilihan">
                    <flux:select.option value="zakat">Zakat Profesi / Maal</flux:select.option>
                    <flux:select.option value="infaq_shodaqoh">Infaq & Shadaqah</flux:select.option>
                </flux:select>
            </flux:field>

            <!-- Nominal Saat Ini (Dynamic) -->
            <flux:input 
                label="Nominal Saat Ini" 
                value="Rp {{ number_format($jenisLazisPilihan === 'zakat' ? $this->nominalSaatIniZakat : $this->nominalSaatIniInfaq, 0, ',', '.') }}" 
                disabled 
            />
            
            <!-- Nominal Baru -->
            <flux:input 
                wire:model.live="nominalBaru" 
                type="number" 
                label="Nominal Baru Potongan (Rp)" 
                placeholder="Contoh: 50000"
                autofocus
            />

            <div class="flex justify-end gap-2 mt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane">Kirim Pengajuan</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Konfirmasi Ubah Setoran -->
    <flux:modal name="konfirmasi-ubah-setoran" class="md:w-md">
        <div class="flex flex-col gap-6">
            <div>
                <div class="flex items-center gap-3 text-orange-500">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/40 rounded-full">
                        <flux:icon name="exclamation-triangle" class="w-6 h-6" />
                    </div>
                    <flux:heading size="lg">Konfirmasi Perubahan</flux:heading>
                </div>
                <flux:text size="sm" class="mt-4">
                    Apakah Anda yakin ingin mengajukan perubahan nominal setoran bulanan rutin <strong>{{ $jenisLazisPilihan === 'zakat' ? 'Zakat' : 'Infaq & Shadaqah' }}</strong> Anda menjadi:
                </flux:text>
            </div>

            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl text-center border border-zinc-200 dark:border-zinc-800">
                <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Nominal Baru ({{ $jenisLazisPilihan === 'zakat' ? 'Zakat' : 'Infaq & Shadaqah' }})</div>
                <div class="text-2xl font-bold mt-1 text-zinc-800 dark:text-zinc-100">
                    Rp {{ $nominalBaru ? number_format((int)$nominalBaru, 0, ',', '.') : 0 }}
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button x-on:click="$flux.modal('ubah-setoran').show()" variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" color="orange" wire:click="submitUbahSetoran">Ya, Ajukan Sekarang</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Sukses -->
    <flux:modal name="sukses-ubah-setoran" class="md:w-md">
        <div class="flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-green-500 mb-2">
                <flux:icon name="check-circle" class="w-10 h-10" />
            </div>
            
            <flux:heading size="lg">Pengajuan Berhasil!</flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pengajuan perubahan nominal potongan {{ $jenisLazisPilihan === 'zakat' ? 'Zakat' : 'Infaq & Shadaqah' }} bulanan Anda telah berhasil dikirim dan sedang menunggu proses verifikasi oleh pengurus.
            </flux:text>

            <div class="w-full mt-4">
                <flux:modal.close>
                    <flux:button variant="primary" class="w-full">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
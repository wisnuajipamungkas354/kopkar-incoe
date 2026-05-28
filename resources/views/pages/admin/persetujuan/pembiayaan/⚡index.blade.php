<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pembiayaan;
use App\Models\User;
use App\Mail\NotifikasiPersetujuanPembiayaan;
use App\Models\TagihanPayrollEmployee;
use Illuminate\Support\Facades\Mail;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Pembiayaan'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPembiayaan = null;
    public $alasanPenolakan = '';

    #[Computed]
    public function pengajuan()
    {
        $data = Pembiayaan::with(['employee', 'employee.user'])
                ->whereIn('status', ['diajukan', 'disetujui_bendahara', 'disetujui_ketua', 'dicairkan'])
                ->orderBy('updated_at', 'DESC')
                ->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap ?? '', $this->search) !== false || 
                       stripos($item->employee->npk ?? '', $this->search) !== false ||
                       stripos($item->nomor_pengajuan ?? '', $this->search) !== false ||
                       stripos($item->kategori_pembiayaan ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPengajuan($id)
    {
        $this->selectedPembiayaan = $this->pengajuan()->firstWhere('id', $id);
        $this->alasanPenolakan = '';
        Flux::modal('detail-pengajuan')->show();
    }

    public function approveKetua($id)
    {
        if (!$this->selectedPembiayaan || $this->selectedPembiayaan->id !== $id) {
            $this->selectedPembiayaan = Pembiayaan::find($id);
        }

        if (!$this->selectedPembiayaan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        // Hitung akumulasi margin dan estimasi total pembiayaan
        $nominal = (float) $this->selectedPembiayaan->nominal_pengajuan;
        $tenor = (int) $this->selectedPembiayaan->tenor_bulan;
        $marginTotal = ($nominal * 0.085 * ($tenor / 12));
        $totalPembiayaan = $nominal + $marginTotal;
        $nominalAngsuran = $totalPembiayaan / $tenor;

        $this->selectedPembiayaan->update([
            'status' => 'disetujui_ketua',
            'nominal_disetujui' => $nominal,
            'total_margin' => $marginTotal,
            'total_pembiayaan' => $totalPembiayaan,
            'nominal_angsuran' => $nominalAngsuran,
            'disetujui_ketua_oleh' => auth('web')->user()->id,
            'disetujui_ketua_pada' => now(),
        ]);

        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPembiayaan->employee_id)
            ->first();

        if ($user) {
            Mail::to($user->email)->send(new NotifikasiPersetujuanPembiayaan(
                namaAnggota: $user->userable->nama_lengkap,
                statusApprove: 'disetujui_ketua',
                nomorPengajuan: $this->selectedPembiayaan->nomor_pengajuan,
                nominal: $this->selectedPembiayaan->nominal_pengajuan
            ));
        }

        Flux::toast(
            heading: 'Disetujui Ketua',
            text: 'Pengajuan pembiayaan telah disetujui oleh Ketua. Menunggu proses selanjutnya oleh Staf untuk pencairan dana.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
    }

    public function approveStaff($id)
    {
        if (!$this->selectedPembiayaan || $this->selectedPembiayaan->id !== $id) {
            $this->selectedPembiayaan = Pembiayaan::find($id);
        }

        if (!$this->selectedPembiayaan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPembiayaan->update([
            'status' => 'dicairkan',
            'diproses_oleh' => auth('web')->user()->id,
            'diproses_pada' => now(),
        ]);

        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPembiayaan->employee_id)
            ->first();

        if ($user) {
            Mail::to($user->email)->send(new NotifikasiPersetujuanPembiayaan(
                namaAnggota: $user->userable->nama_lengkap,
                statusApprove: 'dicairkan',
                nomorPengajuan: $this->selectedPembiayaan->nomor_pengajuan,
                nominal: $this->selectedPembiayaan->nominal_pengajuan
            ));
        }

        Flux::toast(
            heading: 'Diproses Staf',
            text: 'Pengajuan pembiayaan akan diproses oleh Staf. Menunggu proses selanjutnya oleh Bendahara untuk Approval Terakhir.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
    }

    public function approveBendahara($id)
    {
        if (!$this->selectedPembiayaan || $this->selectedPembiayaan->id !== $id) {
            $this->selectedPembiayaan = Pembiayaan::find($id);
        }

        if (!$this->selectedPembiayaan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPembiayaan->update([
            'status' => 'disetujui_bendahara',
            'disetujui_bendahara_oleh' => auth('web')->user()->id,
            'disetujui_bendahara_pada' => now(),
        ]);

        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPembiayaan->employee_id)
            ->first();

        if ($user) {
            Mail::to($user->email)->send(new NotifikasiPersetujuanPembiayaan(
                namaAnggota: $user->userable->nama_lengkap,
                statusApprove: 'disetujui_bendahara',
                nomorPengajuan: $this->selectedPembiayaan->nomor_pengajuan,
                nominal: $this->selectedPembiayaan->nominal_pengajuan
            ));
        }

        Flux::toast(
            heading: 'Disetujui Bendahara',
            text: 'Pengajuan pembiayaan telah berhasil disetujui oleh Bendahara',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
    }

    public function aktifkanAngsuran($id)
    {
        if (!$this->selectedPembiayaan || $this->selectedPembiayaan->id !== $id) {
            $this->selectedPembiayaan = Pembiayaan::find($id);
        }

        if (!$this->selectedPembiayaan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPembiayaan->update([
            'status' => 'berjalan',
        ]);

        TagihanPayrollEmployee::create([
            'employee_id' => $this->selectedPembiayaan->employee_id,
            'jenis_tagihan' => 'pembiayaan',
            'tagihanable_type' => Pembiayaan::class,
            'tagihanable_id' => $this->selectedPembiayaan->id,
            'periode_bulan' => now()->format('m'),
            'periode_tahun' => now()->format('Y'),
            'periode_payroll_bulan' => now()->addMonth()->format('m'),
            'periode_payroll_tahun' => now()->addMonth()->format('Y'),
            'nominal' => $this->selectedPembiayaan->nominal_angsuran,
            'status' => 'pending',
        ]);

        Flux::toast(
            heading: 'Angsuran Aktif',
            text: 'Angsuran untuk pembiayaan ini telah diaktifkan. Akan muncul di tagihan payroll bulan berikutnya.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
    }

    public function tolak($id)
    {
        if (!$this->selectedPembiayaan || $this->selectedPembiayaan->id !== $id) {
            $this->selectedPembiayaan = Pembiayaan::find($id);
        }

        if (!$this->selectedPembiayaan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->validate([
            'alasanPenolakan' => 'required|string|max:500'
        ], [
            'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.'
        ]);

        $this->selectedPembiayaan->update([
            'status' => 'ditolak',
            'alasan_penolakan' => $this->alasanPenolakan,
            'ditolak_oleh' => auth('web')->user()->id,
            'ditolak_pada' => now(),
        ]);

        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPembiayaan->employee_id)
            ->first();

        if ($user) {
            Mail::to($user->email)->send(new NotifikasiPersetujuanPembiayaan(
                namaAnggota: $user->userable->nama_lengkap,
                statusApprove: 'ditolak',
                nomorPengajuan: $this->selectedPembiayaan->nomor_pengajuan,
                nominal: $this->selectedPembiayaan->nominal_pengajuan,
                alasanPenolakan: $this->alasanPenolakan
            ));
        }

        Flux::toast(
            heading: 'Pengajuan Ditolak',
            text: 'Pengajuan pembiayaan berhasil ditolak.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
        $this->alasanPenolakan = '';
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Pembiayaan</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan pembiayaan anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan Pembiayaan</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK / nomor..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Diajukan</flux:table.column>
                    <flux:table.column>No. Pengajuan</flux:table.column>
                    <flux:table.column>NPK & Nama Anggota</flux:table.column>
                    <flux:table.column>Kategori Pembiayaan</flux:table.column>
                    <flux:table.column>Nominal Diajukan</flux:table.column>
                    <flux:table.column>Tenor</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ $row->diajukan_pada ? \Carbon\Carbon::parse($row->diajukan_pada)->format('d/m/Y') : '-' }}</flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-950 dark:text-white">{{ $row->nomor_pengajuan }}</flux:table.cell>
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
                            <flux:table.cell>
                                @if($row->kategori_pembiayaan === 'barang')
                                    <flux:badge color="blue" size="sm">Pembelian Barang</flux:badge>
                                @elseif($row->kategori_pembiayaan === 'pendidikan')
                                    <flux:badge color="purple" size="sm">Pendidikan</flux:badge>
                                @elseif($row->kategori_pembiayaan === 'kesehatan')
                                    <flux:badge color="teal" size="sm">Kesehatan</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ ucfirst($row->kategori_pembiayaan) }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($row->nominal_pengajuan, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>{{ $row->tenor_bulan }} Bulan</flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'diajukan')
                                    <flux:badge color="orange" size="sm" icon="clock">Menunggu Bendahara</flux:badge>
                                @elseif($row->status === 'disetujui_bendahara')
                                    <flux:badge color="sky" size="sm" icon="clock">Menunggu Ketua</flux:badge>
                                @elseif($row->status === 'disetujui_ketua')
                                    <flux:badge color="sky" size="sm" icon="clock">Menunggu Pencairan Dana</flux:badge>
                                @elseif($row->status === 'dicairkan')
                                    <flux:badge color="emerald" size="sm" icon="check-circle">Menunggu Aktivasi</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ $row->status }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPengajuan({{ $row->id }})">Detail</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center text-gray-500 py-6">Tidak ada pengajuan pembiayaan yang perlu disetujui.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail Pengajuan -->
    <flux:modal name="detail-pengajuan" class="md:w-2xl max-h-[90vh] overflow-y-auto">
        @if($selectedSelected = $selectedPembiayaan)
            <div>
                <flux:heading size="lg">Detail Pengajuan Pembiayaan</flux:heading>
                <flux:text size="sm" class="mt-1">Tinjau informasi pengajuan pembiayaan anggota koperasi.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Info Anggota -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ substr($selectedSelected->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedSelected->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">NPK: {{ $selectedSelected->employee->npk ?? '-' }} • Seksi: {{ $selectedSelected->employee->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <!-- Detail Pengajuan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-400 mb-1">Nomor Pengajuan</flux:text>
                        <flux:text class="font-semibold text-zinc-900 dark:text-white">{{ $selectedSelected->nomor_pengajuan }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-400 mb-1">Kategori Pembiayaan</flux:text>
                        <flux:text class="font-semibold text-zinc-900 dark:text-white">{{ ucfirst($selectedSelected->kategori_pembiayaan) }}</flux:text>
                    </div>
                    <div class="col-span-2">
                        <flux:text class="text-sm font-medium text-zinc-400 mb-1">Tujuan Pembiayaan</flux:text>
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap">{{ $selectedSelected->tujuan_pembiayaan }}</flux:text>
                    </div>
                </div>

                <!-- Rincian Barang (Jika ada) -->
                @if($selectedSelected->kategori_pembiayaan === 'barang' && !empty($selectedSelected->rincian_barang))
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rincian Barang yang Diajukan</flux:heading>
                        <div class="space-y-2">
                            @foreach($selectedSelected->rincian_barang as $index => $item)
                                <div class="flex justify-between items-center py-2 border-b border-zinc-200 dark:border-zinc-800 last:border-0">
                                    <div>
                                        <span class="text-xs text-zinc-400 block">Barang #{{ $index + 1 }}</span>
                                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $item['rincian'] ?? '-' }}</span>
                                    </div>
                                    <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Perhitungan Simulasi & Tenor -->
                <div class="p-4 bg-gradient-to-tr from-emerald-500/5 to-teal-500/5 dark:from-emerald-950/10 dark:to-teal-950/10 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                    <flux:heading size="sm" class="mb-3 text-emerald-800 dark:text-emerald-300">Simulasi Perhitungan</flux:heading>
                    <flux:separator />
                    @php
                        $nominal = (float) $selectedSelected->nominal_pengajuan;
                        $tenor = (int) $selectedSelected->tenor_bulan;
                        $angsuranPokok = $nominal / $tenor;
                        $marginBulanan = ($nominal * 0.085) / 12;
                        $angsuranBulanan = $angsuranPokok + $marginBulanan;
                        $totalPembiayaan = $angsuranBulanan * $tenor;
                    @endphp

                    <div class="space-y-2 text-sm mt-3">
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Nominal Pengajuan Pokok</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">Rp {{ number_format($nominal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between pt-2">
                            <span class="text-zinc-500 dark:text-zinc-400">Tenor Angsuran</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $tenor }} Bulan</span>
                        </div>
                        <div class="flex justify-between border-t border-dashed border-zinc-200 dark:border-zinc-800 pt-2">
                            <span class="text-zinc-500 dark:text-zinc-400">Angsuran Pokok / Bulan</span>
                            <span class="font-semibold text-zinc-850 dark:text-zinc-200">Rp {{ number_format($angsuranPokok, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between pt-2">
                            <span class="text-zinc-500 dark:text-zinc-400">Margin Koperasi (8.5%) / Bulan</span>
                            <span class="font-semibold text-zinc-850 dark:text-zinc-200">Rp {{ number_format($marginBulanan, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-800 pt-2 text-emerald-800 dark:text-emerald-400 font-semibold">
                            <span>Total Angsuran / Bulan</span>
                            <span class="text-base font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($angsuranBulanan, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t-2 border-double border-zinc-300 dark:border-zinc-700 pt-2 text-zinc-900 dark:text-zinc-100 font-bold">
                            <span>Total Pembiayaan</span>
                            <span class="text-lg text-emerald-700 dark:text-emerald-300">Rp {{ number_format($totalPembiayaan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Info Rekening Pencairan -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rekening Pencairan Anggota</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-450 block text-xs">Nama Bank</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedSelected->nama_bank }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-455 block text-xs">Nomor Rekening</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedSelected->no_rekening }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-460 block text-xs">Nama Pemilik</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedSelected->nama_pemilik_rekening }}</span>
                        </div>
                    </div>
                </div>

                <!-- Referensi Pihak Ketiga -->
                @if(!empty($selectedSelected->nama_pihak_ketiga))
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Referensi Pihak Ketiga</flux:heading>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-zinc-450 block text-xs">Nama Pihak Ketiga</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedSelected->nama_pihak_ketiga }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-455 block text-xs">No. Telepon / WA</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedSelected->no_telp_pihak_ketiga }}</span>
                            </div>
                            <div class="md:col-span-3 mt-2">
                                <span class="text-zinc-460 block text-xs">Alamat</span>
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $selectedSelected->alamat_pihak_ketiga }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <flux:separator variant="subtle" />

                <!-- Action Form Penolakan / Persetujuan -->
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Catatan / Alasan Penolakan (Wajib diisi jika menolak pengajuan)</flux:label>
                        <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan di sini..." rows="2" />
                        <flux:error name="alasanPenolakan" />
                    </flux:field>

                    <div class="flex justify-end gap-3 pt-2">
                        @if(in_array($selectedSelected->status, ['disetujui_ketua', 'dicairkan', 'diajukan', 'disetujui_bendahara']))
                            <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedSelected->id }})">Tolak Pengajuan</flux:button>
                        @endif
                        
                        @if($selectedSelected->status === 'diajukan')
                            <flux:button variant="primary" color="emerald" icon="check-circle" wire:click="approveBendahara({{ $selectedSelected->id }})">Setujui (Bendahara)</flux:button>
                        @elseif($selectedSelected->status === 'disetujui_bendahara')
                            <flux:button variant="primary" color="blue" icon="check" wire:click="approveKetua({{ $selectedSelected->id }})">Setujui (Ketua)</flux:button>
                        @elseif($selectedSelected->status === 'disetujui_ketua')
                            <flux:button variant="primary" color="amber" icon="check-circle" wire:click="approveStaff({{ $selectedSelected->id }})">Proses Pencairan Dana (Staf)</flux:button>
                        @elseif($selectedSelected->status === 'dicairkan')
                            <flux:button variant="primary" color="emerald" icon="check-circle" wire:click="aktifkanAngsuran({{ $selectedSelected->id }})">Aktifkan Angsuran</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pengajuan...
            </div>
        @endif
    </flux:modal>
</div>
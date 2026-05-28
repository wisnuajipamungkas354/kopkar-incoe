<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pinjaman;
use App\Models\User;
use App\Mail\NotifikasiPersetujuanPinjaman;
use Illuminate\Support\Facades\Mail;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Pinjaman'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPinjaman = null;
    public $alasanPenolakan = '';

    #[Computed]
    public function pengajuan()
    {
        $data = Pinjaman::with(['employee', 'employee.user'])
                ->whereIn('status', ['diajukan', 'disetujui_bendahara', 'disetujui_ketua', 'dicairkan'])
                ->orderBy('updated_at', 'DESC')
                ->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap ?? '', $this->search) !== false || 
                       stripos($item->employee->npk ?? '', $this->search) !== false ||
                       stripos($item->nomor_pengajuan ?? '', $this->search) !== false ||
                       stripos($item->jenis_pinjaman ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPengajuan($id)
    {
        $this->selectedPinjaman = $this->pengajuan()->firstWhere('id', $id);
        $this->alasanPenolakan = '';
        Flux::modal('detail-pengajuan')->show();
    }

    public function approveBendahara($id)
    {
        if (!$this->selectedPinjaman || $this->selectedPinjaman->id !== $id) {
            $this->selectedPinjaman = Pinjaman::find($id);
        }

        if (!$this->selectedPinjaman) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPinjaman->update([
            'status' => 'disetujui_bendahara',
            'disetujui_bendahara_oleh' => auth('web')->user()->id,
            'disetujui_bendahara_pada' => now(),
        ]);

        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPinjaman->employee_id)
            ->first();

        if ($user) {
            Mail::to($user->email)->send(new NotifikasiPersetujuanPinjaman(
                namaAnggota: $user->userable->nama_lengkap,
                statusApprove: 'disetujui_bendahara',
                nomorPengajuan: $this->selectedPinjaman->nomor_pengajuan,
                nominal: $this->selectedPinjaman->nominal_pengajuan
            ));
        }

        Flux::toast(
            heading: 'Disetujui Bendahara',
            text: 'Pengajuan pinjaman telah disetujui oleh Bendahara dan menunggu persetujuan Ketua.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
    }

    public function approveKetua($id)
    {
        if (!$this->selectedPinjaman || $this->selectedPinjaman->id !== $id) {
            $this->selectedPinjaman = Pinjaman::find($id);
        }

        if (!$this->selectedPinjaman) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $nominal = (float) $this->selectedPinjaman->nominal_pengajuan;
        $tenor = (int) $this->selectedPinjaman->tenor_bulan;
        $nominalAngsuran = $nominal / $tenor;

        $this->selectedPinjaman->update([
            'status' => 'disetujui_ketua',
            'nominal_disetujui' => $nominal,
            'nominal_angsuran' => $nominalAngsuran,
            'disetujui_ketua_oleh' => auth('web')->user()->id,
            'disetujui_ketua_pada' => now(),
        ]);

        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPinjaman->employee_id)
            ->first();

        if ($user) {
            Mail::to($user->email)->send(new NotifikasiPersetujuanPinjaman(
                namaAnggota: $user->userable->nama_lengkap,
                statusApprove: 'disetujui_ketua',
                nomorPengajuan: $this->selectedPinjaman->nomor_pengajuan,
                nominal: $this->selectedPinjaman->nominal_pengajuan
            ));
        }

        Flux::toast(
            heading: 'Disetujui Ketua',
            text: 'Pengajuan pinjaman telah disetujui sepenuhnya oleh Ketua.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
    }

    public function approveStaffKoperasi($id)
    {
        if (!$this->selectedPinjaman || $this->selectedPinjaman->id !== $id) {
            $this->selectedPinjaman = Pinjaman::find($id);
        }

        if (!$this->selectedPinjaman) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPinjaman->update([
            'status' => 'dicairkan',
        ]);

        Flux::toast(
            heading: 'Diproses Staff',
            text: 'Dana pinjaman telah dicairkan oleh staff koperasi.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
    }

    public function aktivasiCicilan($id)
    {
        if (!$this->selectedPinjaman || $this->selectedPinjaman->id !== $id) {
            $this->selectedPinjaman = Pinjaman::find($id);
        }

        if (!$this->selectedPinjaman) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPinjaman->update([
            'status' => 'berjalan',
        ]);

        Flux::toast(
            heading: 'Diaktivasi Staff',
            text: 'Pinjaman telah diaktifkan oleh staff koperasi.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
    }

    public function tolak($id)
    {
        if (!$this->selectedPinjaman || $this->selectedPinjaman->id !== $id) {
            $this->selectedPinjaman = Pinjaman::find($id);
        }

        if (!$this->selectedPinjaman) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->validate([
            'alasanPenolakan' => 'required|string|max:500'
        ], [
            'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.'
        ]);

        $this->selectedPinjaman->update([
            'status' => 'ditolak',
            'alasan_penolakan' => $this->alasanPenolakan,
            'ditolak_oleh' => auth('web')->user()->id,
            'ditolak_pada' => now(),
        ]);

        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPinjaman->employee_id)
            ->first();

        if ($user) {
            Mail::to($user->email)->send(new NotifikasiPersetujuanPinjaman(
                namaAnggota: $user->userable->nama_lengkap,
                statusApprove: 'ditolak',
                nomorPengajuan: $this->selectedPinjaman->nomor_pengajuan,
                nominal: $this->selectedPinjaman->nominal_pengajuan,
                alasanPenolakan: $this->alasanPenolakan
            ));
        }

        Flux::toast(
            heading: 'Pengajuan Ditolak',
            text: 'Pengajuan pinjaman berhasil ditolak.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
        $this->alasanPenolakan = '';
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Pinjaman</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan pinjaman qard hasan dan bon sementara anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan Pinjaman</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK / nomor..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Diajukan</flux:table.column>
                    <flux:table.column>No. Pengajuan</flux:table.column>
                    <flux:table.column>NPK & Nama Anggota</flux:table.column>
                    <flux:table.column>Program Pinjaman</flux:table.column>
                    <flux:table.column>Nominal Diajukan</flux:table.column>
                    <flux:table.column>Tenor</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ $row->diajukan_pada ? \Carbon\Carbon::parse($row->diajukan_pada)->format('d/m/Y') : '-' }}</flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-955 dark:text-white">{{ $row->nomor_pengajuan }}</flux:table.cell>
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
                                @if($row->jenis_pinjaman === 'qard')
                                    <flux:badge color="emerald" size="sm" icon="hand-raised">Qard Hasan</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm" icon="credit-card">Bon Sementara</flux:badge>
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
                                    <flux:badge color="orange" size="sm" icon="clock">Menunggu Pencairan Dana</flux:badge>
                                @elseif($row->status === 'dicairkan')
                                    <flux:badge color="orange" size="sm" icon="clock">Menunggu Aktivasi Cicilan</flux:badge>
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
                            <flux:table.cell colspan="8" class="text-center text-gray-500 py-6">Tidak ada pengajuan pinjaman yang perlu disetujui.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail Pengajuan -->
    <flux:modal name="detail-pengajuan" class="md:w-xl max-h-[90vh] overflow-y-auto">
        @if($selectedSelected = $selectedPinjaman)
            <div>
                <flux:heading size="lg">Detail Pengajuan Pinjaman</flux:heading>
                <flux:text size="sm" class="mt-1">Tinjau informasi pengajuan pinjaman anggota.</flux:text>
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
                        <flux:text class="text-sm font-medium text-zinc-400 mb-1">Kategori Pinjaman</flux:text>
                        <div>
                            @if($selectedSelected->jenis_pinjaman === 'qard')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    Qard Hasan
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                    Bon Sementara
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-400 mb-1">Nominal Diajukan</flux:text>
                        <flux:text class="text-lg font-bold text-zinc-900 dark:text-white">Rp {{ number_format($selectedSelected->nominal_pengajuan, 0, ',', '.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-400 mb-1">Tenor Angsuran</flux:text>
                        <flux:text class="text-lg font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedSelected->tenor_bulan }} Bulan</flux:text>
                    </div>
                    
                    @if($selectedSelected->jenis_pinjaman === 'bon')
                        <div class="col-span-2 p-3 bg-amber-50 dark:bg-amber-950/20 rounded-lg border border-amber-200 dark:border-amber-900/40 text-xs text-amber-800 dark:text-amber-300">
                            <strong>Penting:</strong> Pinjaman Bon Sementara akan dipotong penuh 100% pada siklus penggajian berikutnya.
                        </div>
                    @else
                        <div class="col-span-2 p-3 bg-emerald-50 dark:bg-emerald-950/20 rounded-lg border border-emerald-200 dark:border-emerald-900/40 text-xs text-emerald-800 dark:text-emerald-300">
                            <strong>Perkiraan Angsuran per Bulan:</strong> Rp {{ number_format($selectedSelected->nominal_pengajuan / $selectedSelected->tenor_bulan, 0, ',', '.') }} /bulan.
                        </div>
                    @endif
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

                <flux:separator variant="subtle" />

                <!-- Action Form Penolakan / Persetujuan -->
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Catatan / Alasan Penolakan (Wajib diisi jika menolak pengajuan)</flux:label>
                        <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan di sini..." rows="2" />
                        <flux:error name="alasanPenolakan" />
                    </flux:field>

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedSelected->id }})">Tolak Pengajuan</flux:button>
                        
                        @if($selectedSelected->status === 'diajukan')
                            <flux:button variant="primary" icon="check" wire:click="approveBendahara({{ $selectedSelected->id }})">Setujui (Bendahara)</flux:button>
                        @elseif($selectedSelected->status === 'disetujui_bendahara')
                            <flux:button variant="primary" color="emerald" icon="check-circle" wire:click="approveKetua({{ $selectedSelected->id }})">Setujui (Ketua)</flux:button>
                        @elseif($selectedSelected->status === 'disetujui_ketua')
                            <flux:button variant="primary" color="orange" icon="check-circle" wire:click="approveStaffKoperasi({{ $selectedSelected->id }})">Proses Pencairan Dana</flux:button>
                        @elseif($selectedSelected->status === 'dicairkan')
                            <flux:button variant="primary" color="emerald" icon="check-circle" wire:click="aktivasiCicilan({{ $selectedSelected->id }})">Aktifkan Cicilan</flux:button>
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
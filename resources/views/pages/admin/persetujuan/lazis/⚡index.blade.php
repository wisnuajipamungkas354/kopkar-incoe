<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\PengajuanPerubahanPotonganPayroll;
use App\Models\PotonganPayrollEmployee;
use App\Models\User;
use App\Mail\NotifikasiPerubahanLazis;
use Illuminate\Support\Facades\Mail;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Perubahan LAZIS'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPengajuan = null;

    #[Computed]
    public function pengajuan()
    {
        $data = PengajuanPerubahanPotonganPayroll::with(['employee', 'employee.user'])
                ->where('jenis_potongan', 'lazis')
                ->where('status', 'pending')
                ->orderBy('updated_at', 'DESC')
                ->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap ?? '', $this->search) !== false || 
                       stripos($item->employee->npk ?? '', $this->search) !== false ||
                       stripos($item->employee->user->username ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPengajuan($id)
    {
        $this->selectedPengajuan = $this->pengajuan()->firstWhere('id', $id);
    }

    public function approve($id)
    {
        if (!$this->selectedPengajuan || $this->selectedPengajuan->id !== $id) {
            $this->selectedPengajuan = PengajuanPerubahanPotonganPayroll::find($id);
        }

        if (!$this->selectedPengajuan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPengajuan->update([
            'status' => 'disetujui',
            'disetujui_oleh' => auth('web')->user()->id,
            'tanggal_persetujuan' => now(),
        ]);
        
        PotonganPayrollEmployee::create([
            'employee_id' => $this->selectedPengajuan->employee_id,
            'jenis_potongan' => 'lazis',
            'sub_jenis_potongan' => $this->selectedPengajuan->sub_jenis_potongan,
            'nominal' => $this->selectedPengajuan->nominal_baru,
            'tanggal_mulai_berlaku' => $this->selectedPengajuan->tanggal_berlaku,
            'pengajuan_perubahan_id' => $this->selectedPengajuan->id,
        ]);
        
        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPengajuan->employee_id)
            ->first();

        if (!empty($user)) {
            $jenisLazis = $this->selectedPengajuan->sub_jenis_potongan === 'zakat' ? 'Zakat Profesi / Maal' : 'Infaq & Shadaqah';

            Mail::to($user->email)->send(new NotifikasiPerubahanLazis(
                namaAnggota: $user->userable->nama_lengkap, 
                statusApprove: 'disetujui',
                jenisLazis: $jenisLazis,
                nominalSebelum: $this->selectedPengajuan->nominal_lama,
                nominalSesudah: $this->selectedPengajuan->nominal_baru
            ));
        }

        Flux::toast(
            heading: 'Berhasil di Approve',
            text: 'Perubahan nominal LAZIS berhasil disetujui',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
    }

    public function tolak($id)
    {
        if (!$this->selectedPengajuan || $this->selectedPengajuan->id !== $id) {
            $this->selectedPengajuan = PengajuanPerubahanPotonganPayroll::find($id);
        }

        if (!$this->selectedPengajuan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->selectedPengajuan->update([
            'status' => 'ditolak',
            'catatan' => 'Pengajuan ditolak oleh Ketua Koperasi',
        ]);
        
        $user = User::with('userable')
            ->where('userable_type', 'App\Models\Employee')
            ->where('userable_id', $this->selectedPengajuan->employee_id)
            ->first();

        if ($user) {
            $jenisLazis = $this->selectedPengajuan->sub_jenis_potongan === 'zakat' ? 'Zakat Profesi / Maal' : 'Infaq & Shadaqah';

            Mail::to($user->email)->send(new NotifikasiPerubahanLazis(
                namaAnggota: $user->userable->nama_lengkap, 
                statusApprove: 'ditolak',
                jenisLazis: $jenisLazis,
                alasanPenolakan: 'Pengajuan perubahan nominal potongan LAZIS Anda ditolak. Silakan hubungi bagian administrasi untuk informasi lebih lanjut.'
            ));
        }

        Flux::toast(
            heading: 'Berhasil ditolak',
            text: 'Perubahan nominal LAZIS telah ditolak',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Perubahan LAZIS</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan perubahan nominal potongan rutin LAZIS (Zakat & Infaq/Shodaqoh).</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Pengajuan</flux:table.column>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Anggota</flux:table.column>
                    <flux:table.column>Program LAZIS</flux:table.column>
                    <flux:table.column>Nominal Lama</flux:table.column>
                    <flux:table.column>Nominal Baru</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal_pengajuan)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->employee->npk ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->employee->nama_lengkap ?? 'A', 0, 1) }}
                                    </div>
                                    <span class="font-medium">{{ $row->employee->nama_lengkap ?? 'Unknown' }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->sub_jenis_potongan === 'zakat')
                                    <flux:badge color="emerald" size="sm" icon="hand-raised">Zakat</flux:badge>
                                @else
                                    <flux:badge color="indigo" size="sm" icon="sparkles">Infaq & Shadaqah</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>Rp {{ number_format($row->nominal_lama, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell class="font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($row->nominal_baru, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPengajuan({{ $row->id }})" x-on:click="$flux.modal('detail-pengajuan').show()">Detail</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center text-gray-500 py-6">Tidak ada pengajuan perubahan LAZIS.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail -->
    <flux:modal name="detail-pengajuan" class="md:w-xl">
        @if($selectedPengajuan)
            <div>
                <flux:heading size="lg">Detail Pengajuan LAZIS</flux:heading>
                <flux:text size="sm" class="mt-1">Periksa kembali pengajuan perubahan potongan LAZIS anggota.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ substr($selectedPengajuan->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPengajuan->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedPengajuan->employee->npk ?? '-' }} • {{ $selectedPengajuan->employee->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div class="col-span-2">
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Program LAZIS</flux:text>
                        <div>
                            @if($selectedPengajuan->sub_jenis_potongan === 'zakat')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <flux:icon name="hand-raised" class="w-4 h-4" />
                                    Zakat Profesi / Maal
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                                    <flux:icon name="sparkles" class="w-4 h-4" />
                                    Infaq & Shadaqah
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nominal Lama</flux:text>
                        <flux:text class="text-lg font-medium text-zinc-800 dark:text-zinc-200">Rp {{ number_format($selectedPengajuan->nominal_lama, 0, ',', '.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nominal Baru (Diajukan)</flux:text>
                        <flux:text class="text-lg font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($selectedPengajuan->nominal_baru, 0, ',', '.') }}</flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <div class="space-y-3 bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                    <div class="flex gap-3">
                        <flux:icon name="information-circle" class="w-5 h-5 text-emerald-600 mt-0.5 shrink-0" />
                        <flux:text class="text-sm text-emerald-800 dark:text-emerald-200">Jika disetujui, nominal potongan bulanan rutin anggota untuk program ini akan diperbarui mulai bulan berikutnya.</flux:text>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedPengajuan->id }})">Tolak</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="approve({{ $selectedPengajuan->id }})">Approve Perubahan</flux:button>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pengajuan...
            </div>
        @endif
    </flux:modal>
</div>

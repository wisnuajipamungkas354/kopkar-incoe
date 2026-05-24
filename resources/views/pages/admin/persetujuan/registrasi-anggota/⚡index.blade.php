<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\User;
use App\Mail\NotifikasiApprovalAnggotaBaru;
use App\Models\KoperasiMember;
use App\Models\PotonganPayrollEmployee;
use App\Models\TagihanPayrollEmployee;
use App\Models\UserPreferences;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Pendaftaran Anggota'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedAnggota = null;

    #[Computed]
    public function pendaftarBaru()
    {
        $data = KoperasiMember::with('employee')->where('status', 'pending')->where('is_approved', false)->orderBy('updated_at', 'DESC')->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap, $this->search) !== false || 
                       stripos($item->employee->npk, $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPendaftar($id)
    {
        $this->selectedAnggota = $this->pendaftarBaru()->firstWhere('id', $id);
    }

    public function approve($id)
    {
        $member = KoperasiMember::with(['employee', 'employee.user'])->find($id);

        try {
            if(!empty($member)) {
                $newPassword = $member->employee->npk . '@' . rand(1000, 9999); 
                
                $member->update([
                    'status' => 'active',
                    'join_date' => now(),
                    'is_approved' => true,
                    'approved_at' => now(),
                ]);

                $member->employee->user->update([
                    'password' => bcrypt($newPassword),            
                ]);

                // Membuat potongan payroll untuk simpanan wajib
                PotonganPayrollEmployee::create([
                    'employee_id' => $member->employee_id,
                    'jenis_potongan' => 'simpanan_wajib',
                    'nominal' => 150000,
                    'tanggal_mulai_berlaku' => Carbon::now()->addMonth()->startOfMonth(),
                ]);

                // Membuat tagihan payroll untuk simpanan pokok
                TagihanPayrollEmployee::create([
                    'employee_id' => $member->employee_id,
                    'jenis_tagihan' => 'simpanan_pokok',
                    'periode_bulan' => Carbon::now()->format('m'),
                    'periode_tahun' => Carbon::now()->format('Y'),
                    'periode_payroll_bulan' => Carbon::now()->addMonth()->format('m'),
                    'periode_payroll_tahun' => Carbon::now()->addMonth()->format('Y'),
                    'nominal' => 50000,
                ]);
    
                Mail::to($member->employee->user->email)->send(new NotifikasiApprovalAnggotaBaru($member, $newPassword));

                Flux::toast(
                    heading: 'Berhasil di Approve',
                    text: 'Akun anggota berhasil diaktivasi',
                    variant: 'success',
                );
            }
        } catch(\Exception $e) {
            session()->flash('error', 'Gagal melakukan proses approve, terjadi kesalahan pada server!');
        }
    }

    public function tolak($id)
    {
        $member = KoperasiMember::with(['employee', 'employee.user'])->find($id);

        try {
            if(!empty($member)) {
                $member->update([
                    'status' => 'rejected',
                    'is_approved' => false,
                ]);

                Mail::to($member->employee->user->email)->send(new NotifikasiApprovalAnggotaBaru($member));

                session()->flash('message', 'Berhasil melakukan penolakan!');
            };
        } catch(\Exception $e) {
            session()->flash('error', 'Gagal melakukan proses penolakan, terjadi kesalahan pada server!');
        }
    }

    public function hapus($id)
    {
        $member = KoperasiMember::find($id);
        try {
            if(!empty($member)) {
                $member->delete();
                
                Flux::toast(
                    heading: 'Berhasil di Hapus',
                    text: 'Akun anggota berhasil dihapus',
                    variant: 'success',
                );
            };
        } catch(\Exception $e) {
            Flux::toast(
                    heading: 'Gagal Menghapus',
                    text: $e->getMessage(),
                    variant: 'danger',
                );
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Pendaftaran Anggota</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pendaftaran anggota koperasi baru.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pendaftar Baru</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Daftar</flux:table.column>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>Departemen</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pendaftarBaru as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->updated_at)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->employee->npk }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->employee->nama_lengkap, 0, 1) }}
                                    </div>
                                    <span class="font-medium">{{ $row->employee->nama_lengkap }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->employee->seksi }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="orange" size="sm" inset="top bottom">Menunggu Verifikasi</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPendaftar({{ $row->id }})" x-on:click="$flux.modal('detail-pendaftar').show()">Detail</flux:button>
                                    <flux:button size="sm" variant="danger" icon="trash" wire:click="hapus({{ $row->id }})">Hapus</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-gray-500 py-6">Tidak ada pendaftaran anggota baru.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail -->
    <flux:modal name="detail-pendaftar" class="md:w-xl">
        @if($selectedAnggota)
            <div>
                <flux:heading size="lg">Detail Pendaftar</flux:heading>
                <flux:text size="sm" class="mt-1">Periksa kembali data pendaftar sebelum memberikan persetujuan.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedAnggota->employee->nama_lengkap, 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedAnggota->employee->nama_lengkap }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedAnggota->employee->npk }} • {{ $selectedAnggota->employee->seksi }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Tanggal Pendaftaran</flux:text>
                        <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ \Carbon\Carbon::parse($selectedAnggota->updated_at)->format('d F Y') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">No. WhatsApp</flux:text>
                        <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedAnggota->employee->no_telp }}</flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <div class="space-y-3 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-900/40">
                    <div class="flex gap-3">
                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" />
                        <flux:text class="text-sm text-blue-800 dark:text-blue-200">Email anggota sudah terverifikasi.</flux:text>
                    </div>
                    <div class="flex gap-3">
                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" />
                        <flux:text class="text-sm text-blue-800 dark:text-blue-200">Anggota bersedia untuk iuran Simpanan Pokok & Wajib yang akan dipotong via Payroll CBI.</flux:text>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedAnggota->id }})" x-on:click="$flux.modal('detail-pendaftar').close()">Tolak</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="approve({{ $selectedAnggota->id }})" x-on:click="$flux.modal('detail-pendaftar').close()">Approve & Aktifkan</flux:button>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pendaftar...
            </div>
        @endif
    </flux:modal>
</div>
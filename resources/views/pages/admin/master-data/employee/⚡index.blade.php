<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\Employee;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        $query = Employee::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('npk', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_lengkap', 'like', '%' . $this->search . '%')
                  ->orWhere('seksi', 'like', '%' . $this->search . '%')
                  ->orWhere('employment_status', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('npk', 'asc')->paginate($this->perPage);
    }

    public function delete($id)
    {
        $employee = Employee::find($id);

        if ($employee) {
            // Check relationships for integrity
            if ($employee->koperasiMember()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan terdaftar sebagai anggota koperasi.', variant: 'danger' })");
                return;
            }
            if ($employee->koperasiManagements()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan terdaftar sebagai pengurus/manajemen.', variant: 'danger' })");
                return;
            }
            if ($employee->user()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki akun pengguna aktif.', variant: 'danger' })");
                return;
            }
            if ($employee->pengajuanPerubahanPotonganPayroll()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki riwayat pengajuan potongan payroll.', variant: 'danger' })");
                return;
            }
            if ($employee->potonganPayrollEmployee()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki potongan payroll aktif.', variant: 'danger' })");
                return;
            }
            if ($employee->tagihanPayrollEmployee()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki tagihan payroll.', variant: 'danger' })");
                return;
            }

            $employee->delete();
            $this->js("Flux.toast({ text: 'Data karyawan berhasil dihapus.', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Karyawan</flux:heading>
            <flux:text class="mt-2 text-base">Kelola seluruh data master karyawan perusahaan</flux:text>
        </div>
        <flux:button href="/admin/employee/create" wire:navigate variant="primary" icon="plus">Tambah Karyawan</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-3">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="md">Daftar Karyawan</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-72" placeholder="Cari NPK, nama, seksi, status..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-3 mb-1" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->employees">
                <flux:table.columns>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>L/P</flux:table.column>
                    <flux:table.column>Seksi / Departemen</flux:table.column>
                    <flux:table.column>Grade</flux:table.column>
                    <flux:table.column>Status Kerja</flux:table.column>
                    <flux:table.column>No. Telepon</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->employees as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->npk }}</flux:table.cell>
                            <flux:table.cell>{{ $row->nama_lengkap }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->jk === 'L')
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Laki-laki</flux:badge>
                                @elseif($row->jk === 'P')
                                    <flux:badge size="sm" color="pink" inset="top bottom">Perempuan</flux:badge>
                                @else
                                    -
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->seksi ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="indigo" inset="top bottom">{{ $row->grade_category ?? '-' }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->employment_status === 'tetap')
                                    <flux:badge size="sm" color="green" inset="top bottom">Tetap</flux:badge>
                                @elseif($row->employment_status === 'kontrak')
                                    <flux:badge size="sm" color="yellow" inset="top bottom">Kontrak</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $row->employment_status ?? '-' }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->no_telp ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" href="/admin/employee/{{ $row->id }}/edit" wire:navigate>Edit</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus data karyawan ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-6 text-zinc-500">
                                Tidak ada data karyawan yang ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>

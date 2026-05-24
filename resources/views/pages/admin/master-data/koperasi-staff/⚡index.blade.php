<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\KoperasiStaff;

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
    public function staffs()
    {
        $query = KoperasiStaff::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('npk', 'like', '%' . $this->search . '%')
                  ->orWhere('nama', 'like', '%' . $this->search . '%')
                  ->orWhere('jabatan', 'like', '%' . $this->search . '%')
                  ->orWhere('employment_status', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('npk', 'asc')->paginate($this->perPage);
    }

    public function delete($id)
    {
        $staff = KoperasiStaff::find($id);

        if ($staff) {
            // Delete associated user record if any to maintain polymorphic integrity
            if ($staff->user) {
                $staff->user->delete();
            }

            $staff->delete();
            $this->js("Flux.toast({ text: 'Data staff koperasi berhasil dihapus.', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Staff Koperasi</flux:heading>
            <flux:text class="mt-2 text-base">Kelola seluruh data staff pengelola Koperasi</flux:text>
        </div>
        <flux:button href="/admin/koperasi-staff/create" wire:navigate variant="primary" icon="plus">Tambah Staff</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-3">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="md">Daftar Staff</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-72" placeholder="Cari NPK, nama, jabatan, status..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-3 mb-1" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->staffs">
                <flux:table.columns>
                    <flux:table.column>NPK / Kode Staff</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>L/P</flux:table.column>
                    <flux:table.column>Jabatan</flux:table.column>
                    <flux:table.column>Tanggal Masuk</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>No. Telepon</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->staffs as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->npk }}</flux:table.cell>
                            <flux:table.cell>{{ $row->nama }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->jk === 'L')
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Laki-laki</flux:badge>
                                @elseif($row->jk === 'P')
                                    <flux:badge size="sm" color="pink" inset="top bottom">Perempuan</flux:badge>
                                @else
                                    -
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->jabatan ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $row->hire_date ? $row->hire_date->format('d M Y') : '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->employment_status === 'active')
                                    <flux:badge size="sm" color="green" inset="top bottom">Aktif</flux:badge>
                                @elseif($row->employment_status === 'inactive')
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Tidak Aktif</flux:badge>
                                @elseif($row->employment_status === 'resign')
                                    <flux:badge size="sm" color="red" inset="top bottom">Resign</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $row->employment_status ?? '-' }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->no_telp ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" href="/admin/koperasi-staff/{{ $row->id }}/edit" wire:navigate>Edit</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus staff ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-6 text-zinc-500">
                                Tidak ada data staff koperasi yang ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>

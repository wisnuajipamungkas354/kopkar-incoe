<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\KoperasiMember;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;
    public $perPage = 10;
    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function members()
    {
        $query = KoperasiMember::with(['employee', 'employee.user']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('employee', function ($eq) {
                    $eq->where('npk', 'like', '%' . $this->search . '%')
                       ->orWhere('nama_lengkap', 'like', '%' . $this->search . '%')
                       ->orWhere('no_telp', 'like', '%' . $this->search . '%');
                })->orWhere('member_number', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_bank', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('join_date', 'desc')->paginate($this->perPage);
    }

    public function delete($id)
    {
        $member = KoperasiMember::find($id);
        if ($member) {
            // Delete associated User login account if exists to maintain sync
            if ($member->employee && $member->employee->user) {
                $member->employee->user->delete();
            }
            $member->delete();
            $this->js("Flux.toast({ text: 'Anggota berhasil dihapus', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Anggota Koperasi</flux:heading>
            <flux:text class="mt-2 text-base">Kelola seluruh data Anggota Koperasi</flux:text>
        </div>
        <flux:button href="/admin/anggota/create" wire:navigate variant="primary" icon="plus">Tambah Anggota</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-3">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="md">List Anggota</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-72" placeholder="Cari NPK, nama, bank..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-3 mb-1" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->members">
                <flux:table.columns>
                    <flux:table.column>No. Anggota</flux:table.column>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>L/P</flux:table.column>
                    <flux:table.column>Bank & Rekening</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Tanggal Gabung</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->members as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-white">{{ $row->member_number }}</flux:table.cell>
                            <flux:table.cell>{{ $row->employee->npk ?? '-' }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $row->employee->nama_lengkap ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @if(($row->employee->jk ?? '') === 'L')
                                    <flux:badge size="sm" color="zinc" inset="top bottom">L</flux:badge>
                                @elseif(($row->employee->jk ?? '') === 'P')
                                    <flux:badge size="sm" color="pink" inset="top bottom">P</flux:badge>
                                @else
                                    -
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-xs">
                                    <span class="font-mono text-zinc-900 dark:text-white block">{{ $row->nama_bank }}</span>
                                    <span class="text-zinc-500 block">{{ $row->no_rekening }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'active')
                                    <flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm" inset="top bottom">Nonaktif</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->join_date ? $row->join_date->format('d M Y') : '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="eye" href="/admin/anggota/{{ $row->id }}" wire:navigate>Lihat Detail</flux:menu.item>
                                        <flux:menu.item icon="pencil-square" href="/admin/anggota/{{ $row->id }}/edit" wire:navigate>Edit</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus anggota ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-6 text-zinc-500">
                                Tidak ada data anggota koperasi yang ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>
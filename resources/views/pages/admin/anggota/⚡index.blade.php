<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;
    public $perPage = 5;

    #[Computed]
    public function anggota()
    {
        return User::where('status_user', 1)->where('ext_is_approved', true)->orderBy('join_date', 'DESC')->paginate($this->perPage);
    }

    public function delete($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            $this->js("Flux.toast({ text: 'Anggota berhasil dihapus', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Anggota</flux:heading>
            <flux:text class="mt-2 text-base">List Data Anggota</flux:text>
        </div>
        <flux:button href="/admin/anggota/create" wire:navigate variant="primary" icon="plus">Tambah Anggota</flux:button>
    </div>
    <flux:separator variant="subtle" />
    <flux:card class="flex flex-col mt-3">
        <div class="flex justify-between">
            <flux:button size="sm"  variant="primary" color="green">Filter</flux:button>
            <flux:input size="sm" class="max-w-42" placeholder="Search..." icon="magnifying-glass"/>
        </div>
        <flux:separator variant="subtle" class="mt-3 mb-1" />
        <div>
            <flux:table class="mt-3" :paginate="$this->anggota">
                <flux:table.columns>
                    <flux:table.column>ID</flux:table.column>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>L/P</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>No HP</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->anggota as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell >{{ $row->id }}</flux:table.cell>
                            <flux:table.cell >{{ $row->username }}</flux:table.cell>
                            <flux:table.cell >{{ $row->nama_anggota}}</flux:table.cell>
                            <flux:table.cell >{{ $row->gender }}</flux:table.cell>
                            <flux:table.cell ><flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge></flux:table.cell>
                            <flux:table.cell >{{ $row->no_telp}}</flux:table.cell>
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
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>
</div>
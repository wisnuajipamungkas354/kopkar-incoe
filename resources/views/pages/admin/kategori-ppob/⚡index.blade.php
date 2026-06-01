<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\KategoriPpob;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Master Kategori PPOB'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Create fields
    public $addKode = '';
    public $addNama = '';
    public $addAktif = true;

    // Edit fields
    public $editingId;
    public $editKode = '';
    public $editNama = '';
    public $editAktif = true;

    // Delete field
    public $deletingId;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        $query = KategoriPpob::query();

        if ($this->search) {
            $query->where('kode', 'like', '%' . $this->search . '%')
                ->orWhere('nama', 'like', '%' . $this->search . '%');
        }

        return $query->latest()->paginate($this->perPage);
    }

    public function openAddModal()
    {
        $this->reset(['addKode', 'addNama', 'addAktif']);
        $this->addAktif = true;
        $this->resetValidation();
        Flux::modal('add-modal')->show();
    }

    public function save()
    {
        $this->validate([
            'addKode' => 'required|string|max:50|unique:kategori_ppob,kode',
            'addNama' => 'required|string|max:100',
            'addAktif' => 'boolean',
        ]);

        KategoriPpob::create([
            'kode' => strtolower(trim($this->addKode)),
            'nama' => trim($this->addNama),
            'aktif' => $this->addAktif,
        ]);

        Flux::modal('add-modal')->close();
        Flux::toast(text: 'Kategori PPOB berhasil ditambahkan.', variant: 'success');
        $this->reset(['addKode', 'addNama', 'addAktif']);
    }

    public function edit($id)
    {
        $kategori = KategoriPpob::findOrFail($id);
        $this->editingId = $kategori->id;
        $this->editKode = $kategori->kode;
        $this->editNama = $kategori->nama;
        $this->editAktif = (bool) $kategori->aktif;
        
        $this->resetValidation();
        Flux::modal('edit-modal')->show();
    }

    public function update()
    {
        $this->validate([
            'editKode' => 'required|string|max:50|unique:kategori_ppob,kode,' . $this->editingId,
            'editNama' => 'required|string|max:100',
            'editAktif' => 'boolean',
        ]);

        $kategori = KategoriPpob::findOrFail($this->editingId);
        $kategori->update([
            'kode' => strtolower(trim($this->editKode)),
            'nama' => trim($this->editNama),
            'aktif' => $this->editAktif,
        ]);

        Flux::modal('edit-modal')->close();
        Flux::toast(text: 'Kategori PPOB berhasil diperbarui.', variant: 'success');
    }

    public function toggleAktif($id)
    {
        $kategori = KategoriPpob::find($id);
        if ($kategori) {
            $kategori->update(['aktif' => !$kategori->aktif]);
            Flux::toast(text: 'Status kategori berhasil diubah.', variant: 'success');
        }
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        Flux::modal('delete-modal')->show();
    }

    public function delete()
    {
        if ($this->deletingId) {
            $kategori = KategoriPpob::find($this->deletingId);

            if ($kategori) {
                $kategori->delete();
                Flux::toast(text: 'Kategori PPOB berhasil dihapus.', variant: 'success');
            }
        }
        Flux::modal('delete-modal')->close();
        $this->deletingId = null;
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Master Kategori PPOB</flux:heading>
            <flux:text class="mt-2 text-base">Kelola daftar kategori pembayaran PPOB</flux:text>
        </div>
        <flux:button wire:click="openAddModal" variant="primary" icon="plus">Tambah Kategori</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-4">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="md">Daftar Kategori PPOB</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-72" placeholder="Cari kode atau nama kategori..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->categories">
                <flux:table.columns>
                    <flux:table.column>Kode</flux:table.column>
                    <flux:table.column>Nama Kategori</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->categories as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-mono text-zinc-600 dark:text-zinc-400">{{ $row->kode }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->nama }}</flux:table.cell>
                            <flux:table.cell>
                                <button wire:click="toggleAktif({{ $row->id }})" class="focus:outline-none transition-opacity hover:opacity-80">
                                    @if($row->aktif)
                                        <flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm" inset="top bottom">Non-Aktif</flux:badge>
                                    @endif
                                </button>
                            </flux:table.cell>
                            <flux:table.cell class="flex gap-2 justify-end">
                                <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $row->id }})">Edit</flux:button>
                                <flux:button variant="ghost" size="sm" icon="trash" variant="danger" wire:click="confirmDelete({{ $row->id }})">Hapus</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center py-8 text-zinc-500">
                                Tidak ada data kategori PPOB yang ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Tambah -->
    <flux:modal name="add-modal" class="md:w-lg space-y-6">
        <div>
            <flux:heading size="lg">Tambah Kategori PPOB</flux:heading>
            <flux:text class="text-sm">Masukkan informasi kategori baru di bawah ini.</flux:text>
        </div>

        <form wire:submit="save" class="space-y-5">
            <flux:field>
                <flux:label>Kode Kategori</flux:label>
                <flux:input wire:model="addKode" placeholder="Contoh: listrik, pdam, internet" required />
                <flux:description>Gunakan huruf kecil tanpa spasi.</flux:description>
                <flux:error name="addKode" />
            </flux:field>

            <flux:field>
                <flux:label>Nama Kategori</flux:label>
                <flux:input wire:model="addNama" placeholder="Contoh: Listrik (PLN)" required />
                <flux:error name="addNama" />
            </flux:field>

            <flux:field class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <flux:label>Status Aktif</flux:label>
                <flux:switch wire:model="addAktif" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Edit -->
    <flux:modal name="edit-modal" class="md:w-lg space-y-6">
        <div>
            <flux:heading size="lg">Edit Kategori PPOB</flux:heading>
            <flux:text class="text-sm">Ubah informasi kategori PPOB terpilih di bawah ini.</flux:text>
        </div>

        <form wire:submit="update" class="space-y-5">
            <flux:field>
                <flux:label>Kode Kategori</flux:label>
                <flux:input wire:model="editKode" required />
                <flux:error name="editKode" />
            </flux:field>

            <flux:field>
                <flux:label>Nama Kategori</flux:label>
                <flux:input wire:model="editNama" required />
                <flux:error name="editNama" />
            </flux:field>

            <flux:field class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <flux:label>Status Aktif</flux:label>
                <flux:switch wire:model="editAktif" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Hapus -->
    <flux:modal name="delete-modal" class="md:w-md space-y-6">
        <div>
            <flux:heading size="lg">Hapus Kategori PPOB</flux:heading>
            <flux:text class="text-sm">Apakah Anda yakin ingin menghapus kategori ini? Data yang dihapus tidak dapat dikembalikan.</flux:text>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <flux:modal.close>
                <flux:button variant="subtle">Batal</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" wire:click="delete">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
</div>

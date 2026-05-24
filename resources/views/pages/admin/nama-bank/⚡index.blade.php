<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\NamaBank;
use Flux\Flux;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 5;

    // Create fields
    public $kodeBank = '';
    public $namaBank = '';

    // Edit fields
    public $editingBankId;
    public $editKodeBank = '';
    public $editNamaBank = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function banks()
    {
        $query = NamaBank::query();

        if ($this->search) {
            $query->where('kode_bank', 'like', '%' . $this->search . '%')
                ->orWhere('nama_bank', 'like', '%' . $this->search . '%');
        }

        return $query->paginate($this->perPage);
    }

    public function openAddModal()
    {
        $this->reset(['kodeBank', 'namaBank']);
        $this->resetValidation();
        Flux::modal('add-modal')->show();
    }

    public function save()
    {
        $this->validate([
            'kodeBank' => 'required|string|max:50|unique:nama_bank,kode_bank',
            'namaBank' => 'required|string|max:100|unique:nama_bank,nama_bank',
        ]);

        NamaBank::create([
            'kode_bank' => strtoupper(trim($this->kodeBank)),
            'nama_bank' => trim($this->namaBank),
        ]);

        Flux::modal('add-modal')->close();
        Flux::toast(['text' => 'Data bank berhasil ditambahkan.', 'variant' => 'success']);
        $this->reset(['kodeBank', 'namaBank']);
    }

    public function edit($id)
    {
        $bank = NamaBank::findOrFail($id);
        $this->editingBankId = $bank->id;
        $this->editKodeBank = $bank->kode_bank;
        $this->editNamaBank = $bank->nama_bank;
        $this->resetValidation();
        Flux::modal('edit-modal')->show();
    }

    public function update()
    {
        $this->validate([
            'editKodeBank' => 'required|string|max:50|unique:nama_bank,kode_bank,' . $this->editingBankId,
            'editNamaBank' => 'required|string|max:100|unique:nama_bank,nama_bank,' . $this->editingBankId,
        ]);

        $bank = NamaBank::findOrFail($this->editingBankId);
        $bank->update([
            'kode_bank' => strtoupper(trim($this->editKodeBank)),
            'nama_bank' => trim($this->editNamaBank),
        ]);

        Flux::modal('edit-modal')->close();
        Flux::toast(['text' => 'Data bank berhasil diperbarui.', 'variant' => 'success']);
    }

    public function delete($id)
    {
        $bank = NamaBank::find($id);

        if ($bank) {
            $bank->delete();
            Flux::toast(['text' => 'Data bank berhasil dihapus.', 'variant' => 'success']);
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Master Nama Bank</flux:heading>
            <flux:text class="mt-2 text-base">Kelola daftar bank penerima transfer/pencairan</flux:text>
        </div>
        <flux:button wire:click="openAddModal" variant="primary" icon="plus">Tambah Bank</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-3">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="md">Daftar Bank</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-72" placeholder="Cari kode atau nama bank..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-3 mb-1" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->banks">
                <flux:table.columns>
                    <flux:table.column>ID</flux:table.column>
                    <flux:table.column>Kode Bank</flux:table.column>
                    <flux:table.column>Nama Bank</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->banks as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ $row->id }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-zinc-900 dark:text-white">{{ $row->kode_bank }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->nama_bank }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $row->id }})">Edit</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus bank ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center py-6 text-zinc-500">
                                Tidak ada data bank yang ditemukan.
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
            <flux:heading size="lg">Tambah Bank Baru</flux:heading>
            <flux:text class="text-sm">Masukkan informasi kode dan nama bank baru di bawah ini.</flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:field>
                <flux:label>Kode Bank</flux:label>
                <flux:input wire:model="kodeBank" placeholder="Contoh: BCA, MANDIRI, BRI" required />
                <flux:error name="kodeBank" />
            </flux:field>

            <flux:field>
                <flux:label>Nama Bank</flux:label>
                <flux:input wire:model="namaBank" placeholder="Contoh: Bank Central Asia" required />
                <flux:error name="namaBank" />
            </flux:field>

            <div class="flex justify-end gap-3">
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
            <flux:heading size="lg">Edit Informasi Bank</flux:heading>
            <flux:text class="text-sm">Ubah kode atau nama bank terpilih di bawah ini.</flux:text>
        </div>

        <form wire:submit="update" class="space-y-6">
            <flux:field>
                <flux:label>Kode Bank</flux:label>
                <flux:input wire:model="editKodeBank" required />
                <flux:error name="editKodeBank" />
            </flux:field>

            <flux:field>
                <flux:label>Nama Bank</flux:label>
                <flux:input wire:model="editNamaBank" required />
                <flux:error name="editNamaBank" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

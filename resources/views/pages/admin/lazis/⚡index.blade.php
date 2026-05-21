<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\LazisPengaturan;
use App\Models\User;

new #[Layout('layouts::admin', ['title' => 'Pengaturan Lazis Anggota'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Properties for Add Form
    public $add_user_id;
    public $add_nominal_rutin = 0;

    // Properties for Edit Form
    public $edit_id;
    public $edit_nominal_rutin;

    // Selected setting for Detail view
    public $selectedSetting = null;

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function settings()
    {
        return LazisPengaturan::with('user')
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($uq) {
                    $uq->where('nama_anggota', 'like', '%' . $this->search . '%')
                       ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->latest('updated_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function eligibleUsers()
    {
        return User::where('status_user', 1)
            ->where('ext_is_approved', true)
            ->whereDoesntHave('lazisPengaturan')
            ->orderBy('nama_anggota')
            ->get();
    }

    // Detail Modal Trigger
    public function showDetail($id)
    {
        $this->selectedSetting = LazisPengaturan::with('user')->find($id);
        if ($this->selectedSetting) {
            $this->js("Flux.modal('detail-modal').show()");
        }
    }

    // Add Modal Trigger
    public function showAdd()
    {
        $this->resetValidation();
        $this->reset(['add_user_id', 'add_nominal_rutin']);
        $this->add_nominal_rutin = 0;
        $this->js("Flux.modal('add-modal').show()");
    }

    // Save Add
    public function store()
    {
        $this->validate([
            'add_user_id' => 'required|exists:users,id|unique:lazis_pengaturan,user_id',
            'add_nominal_rutin' => 'required|numeric|min:0',
        ], [
            'add_user_id.required' => 'Anggota wajib dipilih.',
            'add_user_id.exists' => 'Anggota tidak valid.',
            'add_user_id.unique' => 'Anggota ini sudah memiliki pengaturan LAZIS.',
            'add_nominal_rutin.required' => 'Nominal rutin wajib diisi.',
            'add_nominal_rutin.numeric' => 'Nominal harus berupa angka.',
            'add_nominal_rutin.min' => 'Nominal tidak boleh kurang dari 0.',
        ]);

        LazisPengaturan::create([
            'user_id' => $this->add_user_id,
            'nominal_rutin' => $this->add_nominal_rutin,
        ]);

        $this->js("Flux.modal('add-modal').close()");
        $this->js("Flux.toast({ text: 'Pengaturan LAZIS berhasil ditambahkan', variant: 'success' })");
    }

    // Edit Modal Trigger
    public function showEdit($id)
    {
        $this->resetValidation();
        $setting = LazisPengaturan::with('user')->findOrFail($id);
        $this->edit_id = $setting->id;
        $this->edit_nominal_rutin = (int) $setting->nominal_rutin;
        $this->selectedSetting = $setting;
        $this->js("Flux.modal('edit-modal').show()");
    }

    // Save Edit
    public function update()
    {
        $this->validate([
            'edit_nominal_rutin' => 'required|numeric|min:0',
        ], [
            'edit_nominal_rutin.required' => 'Nominal rutin wajib diisi.',
            'edit_nominal_rutin.numeric' => 'Nominal harus berupa angka.',
            'edit_nominal_rutin.min' => 'Nominal tidak boleh kurang dari 0.',
        ]);

        $setting = LazisPengaturan::findOrFail($this->edit_id);
        
        $setting->update([
            'nominal_rutin' => $this->edit_nominal_rutin,
        ]);

        $this->js("Flux.modal('edit-modal').close()");
        $this->js("Flux.toast({ text: 'Pengaturan LAZIS berhasil diperbarui', variant: 'success' })");
    }

    // Delete configuration
    public function delete($id)
    {
        $setting = LazisPengaturan::find($id);
        if ($setting) {
            $setting->delete();
            $this->js("Flux.toast({ text: 'Pengaturan LAZIS berhasil dihapus', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Pengaturan Lazis Anggota</flux:heading>
            <flux:text class="mt-2 text-base">Kelola nominal pemotongan rutin bulanan Lazis anggota.</flux:text>
        </div>
        <flux:button wire:click="showAdd" variant="primary" icon="plus">Tambah Pengaturan</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-4">
        <div class="flex justify-between items-center mb-4">
            <flux:heading size="lg" level="2">Daftar Pengaturan</flux:heading>
            <flux:input wire:model.live.debounce.300ms="search" size="sm" class="max-w-64" placeholder="Cari nama atau NPK..." icon="magnifying-glass"/>
        </div>
        
        <div>
            <flux:table class="mt-3" :paginate="$this->settings">
                <flux:table.columns>
                    <flux:table.column>Anggota</flux:table.column>
                    <flux:table.column>Nominal Potongan Rutin</flux:table.column>
                    <flux:table.column>Pembaruan Terakhir</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->settings as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->user->nama_anggota ?? 'A', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->user->nama_anggota ?? 'Unknown' }}</span>
                                        <span class="text-xs text-zinc-500">{{ $row->user->username ?? '-' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="font-semibold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($row->nominal_rutin, 0, ',', '.') }}
                            </flux:table.cell>
                            <flux:table.cell class="text-zinc-500">
                                {{ \Carbon\Carbon::parse($row->updated_at)->diffForHumans() }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="eye" wire:click="showDetail({{ $row->id }})">Detail Pengaturan</flux:menu.item>
                                        <flux:menu.item icon="pencil-square" wire:click="showEdit({{ $row->id }})">Edit Pengaturan</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus pengaturan LAZIS anggota ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center text-zinc-500 py-8">
                                Tidak ada data pengaturan Lazis ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail -->
    <flux:modal name="detail-modal" class="md:w-xl">
        @if($selectedSetting)
            <div>
                <flux:heading size="lg">Detail Pengaturan Lazis</flux:heading>
                <flux:text size="sm" class="mt-1">Rincian pengaturan pemotongan rutin bulanan Lazis anggota.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ substr($selectedSetting->user->nama_anggota ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedSetting->user->nama_anggota ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedSetting->user->username ?? '-' }} • {{ $selectedSetting->user->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nominal Potongan Rutin</flux:text>
                        <flux:text class="text-lg font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($selectedSetting->nominal_rutin, 0, ',', '.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Tanggal Pembaruan</flux:text>
                        <flux:text class="text-base text-zinc-800 dark:text-zinc-200">
                            {{ \Carbon\Carbon::parse($selectedSetting->updated_at)->format('d F Y, H:i') }}
                        </flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="subtle" x-on:click="$flux.modal('detail-modal').close()">Tutup</flux:button>
                    <flux:button variant="primary" icon="pencil-square" wire:click="showEdit({{ $selectedSetting->id }})" x-on:click="$flux.modal('detail-modal').close()">Edit Pengaturan</flux:button>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pengaturan...
            </div>
        @endif
    </flux:modal>

    <!-- Modal Edit -->
    <flux:modal name="edit-modal" class="md:w-xl">
        @if($selectedSetting)
            <div>
                <flux:heading size="lg">Edit Pengaturan Lazis</flux:heading>
                <flux:text size="sm" class="mt-1">Perbarui nominal potongan rutin bulanan untuk anggota <strong>{{ $selectedSetting->user->nama_anggota }}</strong>.</flux:text>
            </div>

            <form wire:submit="update" class="mt-6 space-y-6">
                <flux:field>
                    <flux:label>Nominal Potongan Rutin (Rp)</flux:label>
                    <flux:input type="number" wire:model="edit_nominal_rutin" placeholder="Masukkan nominal potongan rutin..." min="0" required />
                    <flux:error name="edit_nominal_rutin" />
                </flux:field>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="subtle" x-on:click="$flux.modal('edit-modal').close()">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                </div>
            </form>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pengaturan...
            </div>
        @endif
    </flux:modal>

    <!-- Modal Add -->
    <flux:modal name="add-modal" class="md:w-xl">
        <div>
            <flux:heading size="lg">Tambah Pengaturan Lazis</flux:heading>
            <flux:text size="sm" class="mt-1">Buat pengaturan pemotongan rutin Lazis bulanan baru untuk anggota yang belum terdaftar.</flux:text>
        </div>

        <form wire:submit="store" class="mt-6 space-y-6">
            <flux:field>
                <flux:label>Pilih Anggota</flux:label>
                <flux:select wire:model="add_user_id" placeholder="Pilih Anggota...">
                    @foreach($this->eligibleUsers as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->nama_anggota }} ({{ $user->username }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="add_user_id" />
            </flux:field>

            <flux:field>
                <flux:label>Nominal Potongan Rutin (Rp)</flux:label>
                <flux:input type="number" wire:model="add_nominal_rutin" placeholder="0" min="0" required />
                <flux:error name="add_nominal_rutin" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button variant="subtle" x-on:click="$flux.modal('add-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

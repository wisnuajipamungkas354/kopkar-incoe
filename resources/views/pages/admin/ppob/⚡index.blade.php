<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\PpobRutinPengaturan;
use App\Models\User;

new #[Layout('layouts::admin', ['title' => 'Pengaturan PPOB Rutin'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Properties for Add Form
    public $add_user_id;
    public $add_jenis_layanan = 'listrik_pasca';
    public $add_nomor_pelanggan = '';
    public $add_nominal_maksimal_gaji;
    public $add_is_active = true;

    // Properties for Edit Form
    public $edit_id;
    public $edit_jenis_layanan;
    public $edit_nomor_pelanggan;
    public $edit_nominal_maksimal_gaji;
    public $edit_is_active;

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
        return PpobRutinPengaturan::with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('nomor_pelanggan', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function ($uq) {
                          $uq->where('nama_anggota', 'like', '%' . $this->search . '%')
                             ->orWhere('username', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->latest('updated_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function allUsers()
    {
        return User::where('status_user', 1)
            ->where('ext_is_approved', true)
            ->orderBy('nama_anggota')
            ->get();
    }

    public function formatJenisLayanan($value)
    {
        return match ($value) {
            'listrik_pasca' => 'Listrik Pascabayar',
            'internet' => 'Internet / Wi-Fi',
            'pdam' => 'PDAM (Air)',
            'bpjs' => 'BPJS Kesehatan/Ketenagakerjaan',
            default => 'Lainnya',
        };
    }

    // Detail Modal Trigger
    public function showDetail($id)
    {
        $this->selectedSetting = PpobRutinPengaturan::with('user')->find($id);
        if ($this->selectedSetting) {
            $this->js("Flux.modal('detail-modal').show()");
        }
    }

    // Add Modal Trigger
    public function showAdd()
    {
        $this->resetValidation();
        $this->reset(['add_user_id', 'add_jenis_layanan', 'add_nomor_pelanggan', 'add_nominal_maksimal_gaji', 'add_is_active']);
        $this->add_jenis_layanan = 'listrik_pasca';
        $this->add_is_active = true;
        $this->js("Flux.modal('add-modal').show()");
    }

    // Save Add
    public function store()
    {
        $this->validate([
            'add_user_id' => 'required|exists:users,id',
            'add_jenis_layanan' => 'required|in:listrik_pasca,internet,pdam,bpjs,lainnya',
            'add_nomor_pelanggan' => 'required|string|max:50',
            'add_nominal_maksimal_gaji' => 'nullable|numeric|min:0',
            'add_is_active' => 'required|boolean',
        ], [
            'add_user_id.required' => 'Anggota wajib dipilih.',
            'add_user_id.exists' => 'Anggota tidak valid.',
            'add_jenis_layanan.required' => 'Jenis layanan wajib diisi.',
            'add_jenis_layanan.in' => 'Jenis layanan tidak valid.',
            'add_nomor_pelanggan.required' => 'Nomor pelanggan wajib diisi.',
            'add_nomor_pelanggan.string' => 'Nomor pelanggan harus berupa teks.',
            'add_nomor_pelanggan.max' => 'Nomor pelanggan maksimal 50 karakter.',
            'add_nominal_maksimal_gaji.numeric' => 'Nominal maksimal harus berupa angka.',
            'add_nominal_maksimal_gaji.min' => 'Nominal maksimal tidak boleh kurang dari 0.',
            'add_is_active.required' => 'Status aktif wajib diisi.',
        ]);

        PpobRutinPengaturan::create([
            'user_id' => $this->add_user_id,
            'jenis_layanan' => $this->add_jenis_layanan,
            'nomor_pelanggan' => $this->add_nomor_pelanggan,
            'nominal_maksimal_gaji' => $this->add_nominal_maksimal_gaji ?: null,
            'is_active' => $this->add_is_active,
        ]);

        $this->js("Flux.modal('add-modal').close()");
        $this->js("Flux.toast({ text: 'Pengaturan PPOB Rutin berhasil ditambahkan', variant: 'success' })");
    }

    // Edit Modal Trigger
    public function showEdit($id)
    {
        $this->resetValidation();
        $setting = PpobRutinPengaturan::with('user')->findOrFail($id);
        $this->edit_id = $setting->id;
        $this->edit_jenis_layanan = $setting->jenis_layanan;
        $this->edit_nomor_pelanggan = $setting->nomor_pelanggan;
        $this->edit_nominal_maksimal_gaji = $setting->nominal_maksimal_gaji !== null ? (int) $setting->nominal_maksimal_gaji : null;
        $this->edit_is_active = (bool) $setting->is_active;
        $this->selectedSetting = $setting;
        $this->js("Flux.modal('edit-modal').show()");
    }

    // Save Edit
    public function update()
    {
        $this->validate([
            'edit_jenis_layanan' => 'required|in:listrik_pasca,internet,pdam,bpjs,lainnya',
            'edit_nomor_pelanggan' => 'required|string|max:50',
            'edit_nominal_maksimal_gaji' => 'nullable|numeric|min:0',
            'edit_is_active' => 'required|boolean',
        ], [
            'edit_jenis_layanan.required' => 'Jenis layanan wajib diisi.',
            'edit_jenis_layanan.in' => 'Jenis layanan tidak valid.',
            'edit_nomor_pelanggan.required' => 'Nomor pelanggan wajib diisi.',
            'edit_nomor_pelanggan.string' => 'Nomor pelanggan harus berupa teks.',
            'edit_nomor_pelanggan.max' => 'Nomor pelanggan maksimal 50 karakter.',
            'edit_nominal_maksimal_gaji.numeric' => 'Nominal maksimal harus berupa angka.',
            'edit_nominal_maksimal_gaji.min' => 'Nominal maksimal tidak boleh kurang dari 0.',
            'edit_is_active.required' => 'Status aktif wajib diisi.',
        ]);

        $setting = PpobRutinPengaturan::findOrFail($this->edit_id);
        
        $setting->update([
            'jenis_layanan' => $this->edit_jenis_layanan,
            'nomor_pelanggan' => $this->edit_nomor_pelanggan,
            'nominal_maksimal_gaji' => $this->edit_nominal_maksimal_gaji ?: null,
            'is_active' => $this->edit_is_active,
        ]);

        $this->js("Flux.modal('edit-modal').close()");
        $this->js("Flux.toast({ text: 'Pengaturan PPOB Rutin berhasil diperbarui', variant: 'success' })");
    }

    // Delete configuration
    public function delete($id)
    {
        $setting = PpobRutinPengaturan::find($id);
        if ($setting) {
            $setting->delete();
            $this->js("Flux.toast({ text: 'Pengaturan PPOB Rutin berhasil dihapus', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Pengaturan PPOB Rutin</flux:heading>
            <flux:text class="mt-2 text-base">Kelola daftar pemotongan payroll otomatis untuk utilitas bulanan (listrik, internet, PDAM, dll.) anggota.</flux:text>
        </div>
        <flux:button wire:click="showAdd" variant="primary" icon="plus">Tambah Pengaturan</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-4">
        <div class="flex justify-between items-center mb-4">
            <flux:heading size="lg" level="2">Daftar Pengaturan</flux:heading>
            <flux:input wire:model.live.debounce.300ms="search" size="sm" class="max-w-64" placeholder="Cari nama, NPK, nomor..." icon="magnifying-glass"/>
        </div>
        
        <div>
            <flux:table class="mt-3" :paginate="$this->settings">
                <flux:table.columns>
                    <flux:table.column>Anggota</flux:table.column>
                    <flux:table.column>Layanan</flux:table.column>
                    <flux:table.column>No. Pelanggan</flux:table.column>
                    <flux:table.column>Max. Gaji</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
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
                            <flux:table.cell>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $this->formatJenisLayanan($row->jenis_layanan) }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-zinc-600 dark:text-zinc-400">
                                {{ $row->nomor_pelanggan }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->nominal_maksimal_gaji !== null)
                                    <span class="text-zinc-800 dark:text-zinc-200 font-medium">
                                        Rp {{ number_format($row->nominal_maksimal_gaji, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 font-light italic">Tidak Dibatasi</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->is_active)
                                    <flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm" inset="top bottom">Nonaktif</flux:badge>
                                @endif
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
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus pengaturan PPOB Rutin anggota ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center text-zinc-500 py-8">
                                Tidak ada data pengaturan PPOB Rutin ditemukan.
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
                <flux:heading size="lg">Detail Pengaturan PPOB Rutin</flux:heading>
                <flux:text size="sm" class="mt-1">Rincian pengaturan pemotongan payroll otomatis bulanan anggota.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedSetting->user->nama_anggota ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedSetting->user->nama_anggota ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedSetting->user->username ?? '-' }} • {{ $selectedSetting->user->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Jenis Layanan</flux:text>
                        <flux:text class="text-base font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $this->formatJenisLayanan($selectedSetting->jenis_layanan) }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nomor Pelanggan</flux:text>
                        <flux:text class="text-base font-mono font-semibold text-zinc-800 dark:text-zinc-200">
                            {{ $selectedSetting->nomor_pelanggan }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Plafon Potong Gaji</flux:text>
                        <flux:text class="text-base font-bold text-zinc-800 dark:text-zinc-200">
                            {{ $selectedSetting->nominal_maksimal_gaji !== null ? 'Rp ' . number_format($selectedSetting->nominal_maksimal_gaji, 0, ',', '.') : 'Tidak Dibatasi' }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Status Aktif</flux:text>
                        <div class="mt-1">
                            @if($selectedSetting->is_active)
                                <flux:badge color="green">Aktif</flux:badge>
                            @else
                                <flux:badge color="zinc">Nonaktif</flux:badge>
                            @endif
                        </div>
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
                <flux:heading size="lg">Edit Pengaturan PPOB Rutin</flux:heading>
                <flux:text size="sm" class="mt-1">Perbarui rincian pengaturan payroll PPOB rutin untuk anggota <strong>{{ $selectedSetting->user->nama_anggota }}</strong>.</flux:text>
            </div>

            <form wire:submit="update" class="mt-6 space-y-6">
                <flux:field>
                    <flux:label>Jenis Layanan</flux:label>
                    <flux:select wire:model="edit_jenis_layanan">
                        <flux:select.option value="listrik_pasca">Listrik Pascabayar</flux:select.option>
                        <flux:select.option value="internet">Internet / Wi-Fi</flux:select.option>
                        <flux:select.option value="pdam">PDAM (Air)</flux:select.option>
                        <flux:select.option value="bpjs">BPJS Kesehatan/Ketenagakerjaan</flux:select.option>
                        <flux:select.option value="lainnya">Lainnya</flux:select.option>
                    </flux:select>
                    <flux:error name="edit_jenis_layanan" />
                </flux:field>

                <flux:field>
                    <flux:label>Nomor Pelanggan</flux:label>
                    <flux:input wire:model="edit_nomor_pelanggan" placeholder="Masukkan nomor pelanggan..." required />
                    <flux:error name="edit_nomor_pelanggan" />
                </flux:field>

                <flux:field>
                    <flux:label>Plafon Potong Gaji (Rp - Opsional)</flux:label>
                    <flux:input type="number" wire:model="edit_nominal_maksimal_gaji" placeholder="Kosongkan jika tidak dibatasi..." min="0" />
                    <flux:error name="edit_nominal_maksimal_gaji" />
                </flux:field>

                <flux:field class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                    <div>
                        <flux:label>Status Pengaturan Aktif</flux:label>
                        <flux:description>Matikan sakelar untuk menonaktifkan pemotongan payroll rutin</flux:description>
                    </div>
                    <flux:switch wire:model="edit_is_active" />
                    <flux:error name="edit_is_active" />
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
            <flux:heading size="lg">Tambah Pengaturan PPOB Rutin</flux:heading>
            <flux:text size="sm" class="mt-1">Buat pengaturan potongan payroll bulanan baru untuk layanan utilitas anggota.</flux:text>
        </div>

        <form wire:submit="store" class="mt-6 space-y-6">
            <flux:field>
                <flux:label>Pilih Anggota</flux:label>
                <flux:select wire:model="add_user_id" placeholder="Pilih Anggota...">
                    @foreach($this->allUsers as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->nama_anggota }} ({{ $user->username }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="add_user_id" />
            </flux:field>

            <flux:field>
                <flux:label>Jenis Layanan</flux:label>
                <flux:select wire:model="add_jenis_layanan">
                    <flux:select.option value="listrik_pasca">Listrik Pascabayar</flux:select.option>
                    <flux:select.option value="internet">Internet / Wi-Fi</flux:select.option>
                    <flux:select.option value="pdam">PDAM (Air)</flux:select.option>
                    <flux:select.option value="bpjs">BPJS Kesehatan/Ketenagakerjaan</flux:select.option>
                    <flux:select.option value="lainnya">Lainnya</flux:select.option>
                </flux:select>
                <flux:error name="add_jenis_layanan" />
            </flux:field>

            <flux:field>
                <flux:label>Nomor Pelanggan</flux:label>
                <flux:input wire:model="add_nomor_pelanggan" placeholder="Masukkan nomor ID pelanggan..." required />
                <flux:error name="add_nomor_pelanggan" />
            </flux:field>

            <flux:field>
                <flux:label>Plafon Potong Gaji (Rp - Opsional)</flux:label>
                <flux:input type="number" wire:model="add_nominal_maksimal_gaji" placeholder="Kosongkan jika tidak dibatasi..." min="0" />
                <flux:error name="add_nominal_maksimal_gaji" />
            </flux:field>

            <flux:field class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div>
                    <flux:label>Status Pengaturan Aktif</flux:label>
                    <flux:description>Secara default pengaturan yang baru ditambahkan langsung aktif</flux:description>
                </div>
                <flux:switch wire:model="add_is_active" />
                <flux:error name="add_is_active" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button variant="subtle" x-on:click="$flux.modal('add-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
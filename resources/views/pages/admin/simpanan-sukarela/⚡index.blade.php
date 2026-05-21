<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\SimpananSukarelaPengaturan;
use App\Models\User;

new #[Layout('layouts::admin', ['title' => 'Pengaturan Simpanan Sukarela Rutin'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Properties for Add Form
    public $add_user_id;
    public $add_nominal_rutin_saat_ini = 0;
    public $add_nominal_baru_diajukan;
    public $add_status_persetujuan = 'none';

    // Properties for Edit Form
    public $edit_id;
    public $edit_nominal_rutin_saat_ini;
    public $edit_nominal_baru_diajukan;
    public $edit_status_persetujuan;

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
        return SimpananSukarelaPengaturan::with('user')
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
            ->whereDoesntHave('simpananSukarelaPengaturan')
            ->orderBy('nama_anggota')
            ->get();
    }

    // Detail Modal Trigger
    public function showDetail($id)
    {
        $this->selectedSetting = SimpananSukarelaPengaturan::with('user')->find($id);
        if ($this->selectedSetting) {
            $this->js("Flux.modal('detail-modal').show()");
        }
    }

    // Add Modal Trigger
    public function showAdd()
    {
        $this->resetValidation();
        $this->reset(['add_user_id', 'add_nominal_rutin_saat_ini', 'add_nominal_baru_diajukan', 'add_status_persetujuan']);
        $this->add_status_persetujuan = 'none';
        $this->add_nominal_rutin_saat_ini = 0;
        $this->js("Flux.modal('add-modal').show()");
    }

    // Save Add
    public function store()
    {
        $this->validate([
            'add_user_id' => 'required|exists:users,id|unique:simpanan_sukarela_pengaturan,user_id',
            'add_nominal_rutin_saat_ini' => 'required|numeric|min:0',
            'add_nominal_baru_diajukan' => 'nullable|numeric|min:0',
            'add_status_persetujuan' => 'required|in:none,pending_approval,approved,rejected',
        ], [
            'add_user_id.required' => 'Anggota wajib dipilih.',
            'add_user_id.exists' => 'Anggota tidak valid.',
            'add_user_id.unique' => 'Anggota ini sudah memiliki pengaturan simpanan sukarela.',
            'add_nominal_rutin_saat_ini.required' => 'Nominal rutin saat ini wajib diisi.',
            'add_nominal_rutin_saat_ini.numeric' => 'Nominal harus berupa angka.',
            'add_nominal_rutin_saat_ini.min' => 'Nominal tidak boleh kurang dari 0.',
            'add_nominal_baru_diajukan.numeric' => 'Nominal baru harus berupa angka.',
            'add_nominal_baru_diajukan.min' => 'Nominal baru tidak boleh kurang dari 0.',
            'add_status_persetujuan.required' => 'Status persetujuan wajib diisi.',
        ]);

        $nominalRutin = $this->add_nominal_rutin_saat_ini;
        if ($this->add_status_persetujuan === 'approved' && $this->add_nominal_baru_diajukan !== null) {
            $nominalRutin = $this->add_nominal_baru_diajukan;
        }

        SimpananSukarelaPengaturan::create([
            'user_id' => $this->add_user_id,
            'nominal_rutin_saat_ini' => $nominalRutin,
            'nominal_baru_diajukan' => $this->add_nominal_baru_diajukan,
            'status_persetujuan' => $this->add_status_persetujuan,
        ]);

        $this->js("Flux.modal('add-modal').close()");
        $this->js("Flux.toast({ text: 'Pengaturan simpanan sukarela berhasil ditambahkan', variant: 'success' })");
    }

    // Edit Modal Trigger
    public function showEdit($id)
    {
        $this->resetValidation();
        $setting = SimpananSukarelaPengaturan::with('user')->findOrFail($id);
        $this->edit_id = $setting->id;
        $this->edit_nominal_rutin_saat_ini = (int) $setting->nominal_rutin_saat_ini;
        $this->edit_nominal_baru_diajukan = $setting->nominal_baru_diajukan !== null ? (int) $setting->nominal_baru_diajukan : null;
        $this->edit_status_persetujuan = $setting->status_persetujuan;
        $this->selectedSetting = $setting;
        $this->js("Flux.modal('edit-modal').show()");
    }

    // Save Edit
    public function update()
    {
        $this->validate([
            'edit_nominal_rutin_saat_ini' => 'required|numeric|min:0',
            'edit_nominal_baru_diajukan' => 'nullable|numeric|min:0',
            'edit_status_persetujuan' => 'required|in:none,pending_approval,approved,rejected',
        ], [
            'edit_nominal_rutin_saat_ini.required' => 'Nominal rutin saat ini wajib diisi.',
            'edit_nominal_rutin_saat_ini.numeric' => 'Nominal harus berupa angka.',
            'edit_nominal_rutin_saat_ini.min' => 'Nominal tidak boleh kurang dari 0.',
            'edit_nominal_baru_diajukan.numeric' => 'Nominal baru harus berupa angka.',
            'edit_nominal_baru_diajukan.min' => 'Nominal baru tidak boleh kurang dari 0.',
            'edit_status_persetujuan.required' => 'Status persetujuan wajib diisi.',
        ]);

        $setting = SimpananSukarelaPengaturan::findOrFail($this->edit_id);
        
        $nominalRutin = $this->edit_nominal_rutin_saat_ini;
        if ($this->edit_status_persetujuan === 'approved' && $this->edit_nominal_baru_diajukan !== null) {
            $nominalRutin = $this->edit_nominal_baru_diajukan;
        }

        $setting->update([
            'nominal_rutin_saat_ini' => $nominalRutin,
            'nominal_baru_diajukan' => $this->edit_nominal_baru_diajukan,
            'status_persetujuan' => $this->edit_status_persetujuan,
        ]);

        $this->js("Flux.modal('edit-modal').close()");
        $this->js("Flux.toast({ text: 'Pengaturan simpanan sukarela berhasil diperbarui', variant: 'success' })");
    }

    // Delete configuration
    public function delete($id)
    {
        $setting = SimpananSukarelaPengaturan::find($id);
        if ($setting) {
            $setting->delete();
            $this->js("Flux.toast({ text: 'Pengaturan simpanan sukarela berhasil dihapus', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Pengaturan Simpanan Sukarela Rutin</flux:heading>
            <flux:text class="mt-2 text-base">Kelola nominal potongan rutin bulanan Simpanan Sukarela anggota.</flux:text>
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
                    <flux:table.column>Nominal Rutin Saat Ini</flux:table.column>
                    <flux:table.column>Nominal Baru Diajukan</flux:table.column>
                    <flux:table.column>Status Persetujuan</flux:table.column>
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
                            <flux:table.cell class="font-medium text-zinc-800 dark:text-zinc-200">
                                Rp {{ number_format($row->nominal_rutin_saat_ini, 0, ',', '.') }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->nominal_baru_diajukan !== null)
                                    <span class="font-semibold text-blue-600 dark:text-blue-400">
                                        Rp {{ number_format($row->nominal_baru_diajukan, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->status_persetujuan === 'approved')
                                    <flux:badge color="green" size="sm" inset="top bottom">Approved</flux:badge>
                                @elseif($row->status_persetujuan === 'pending_approval')
                                    <flux:badge color="yellow" size="sm" inset="top bottom">Pending</flux:badge>
                                @elseif($row->status_persetujuan === 'rejected')
                                    <flux:badge color="red" size="sm" inset="top bottom">Rejected</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm" inset="top bottom">None</flux:badge>
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
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus pengaturan simpanan sukarela rutin anggota ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">
                                Tidak ada data pengaturan simpanan sukarela ditemukan.
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
                <flux:heading size="lg">Detail Pengaturan Simpanan Sukarela</flux:heading>
                <flux:text size="sm" class="mt-1">Rincian pengaturan pemotongan rutin simpanan sukarela anggota.</flux:text>
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
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nominal Rutin Saat Ini</flux:text>
                        <flux:text class="text-lg font-bold text-green-600 dark:text-green-400">Rp {{ number_format($selectedSetting->nominal_rutin_saat_ini, 0, ',', '.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Nominal Baru Diajukan</flux:text>
                        <flux:text class="text-lg font-medium text-zinc-800 dark:text-zinc-200">
                            {{ $selectedSetting->nominal_baru_diajukan !== null ? 'Rp ' . number_format($selectedSetting->nominal_baru_diajukan, 0, ',', '.') : '-' }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Status Persetujuan</flux:text>
                        <div class="mt-1">
                            @if($selectedSetting->status_persetujuan === 'approved')
                                <flux:badge color="green">Approved</flux:badge>
                            @elseif($selectedSetting->status_persetujuan === 'pending_approval')
                                <flux:badge color="yellow">Pending Approval</flux:badge>
                            @elseif($selectedSetting->status_persetujuan === 'rejected')
                                <flux:badge color="red">Rejected</flux:badge>
                            @else
                                <flux:badge color="zinc">None</flux:badge>
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
                <flux:heading size="lg">Edit Pengaturan Simpanan Sukarela</flux:heading>
                <flux:text size="sm" class="mt-1">Perbarui nominal dan status persetujuan pengaturan rutin anggota <strong>{{ $selectedSetting->user->nama_anggota }}</strong>.</flux:text>
            </div>

            <form wire:submit="update" class="mt-6 space-y-6">
                <flux:field>
                    <flux:label>Nominal Rutin Saat Ini (Rp)</flux:label>
                    <flux:input type="number" wire:model="edit_nominal_rutin_saat_ini" placeholder="0" />
                    <flux:error name="edit_nominal_rutin_saat_ini" />
                </flux:field>

                <flux:field>
                    <flux:label>Nominal Baru Diajukan (Rp)</flux:label>
                    <flux:input type="number" wire:model="edit_nominal_baru_diajukan" placeholder="Masukkan nominal baru jika ada..." />
                    <flux:error name="edit_nominal_baru_diajukan" />
                </flux:field>

                <flux:field>
                    <flux:label>Status Persetujuan</flux:label>
                    <flux:select wire:model="edit_status_persetujuan">
                        <flux:select.option value="none">None</flux:select.option>
                        <flux:select.option value="pending_approval">Pending Approval</flux:select.option>
                        <flux:select.option value="approved">Approved</flux:select.option>
                        <flux:select.option value="rejected">Rejected</flux:select.option>
                    </flux:select>
                    <flux:error name="edit_status_persetujuan" />
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
            <flux:heading size="lg">Tambah Pengaturan Simpanan Sukarela</flux:heading>
            <flux:text size="sm" class="mt-1">Buat pengaturan simpanan sukarela bulanan baru untuk anggota yang belum terdaftar.</flux:text>
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
                <flux:label>Nominal Rutin Saat Ini (Rp)</flux:label>
                <flux:input type="number" wire:model="add_nominal_rutin_saat_ini" placeholder="0" />
                <flux:error name="add_nominal_rutin_saat_ini" />
            </flux:field>

            <flux:field>
                <flux:label>Nominal Baru Diajukan (Rp)</flux:label>
                <flux:input type="number" wire:model="add_nominal_baru_diajukan" placeholder="Masukkan nominal baru jika ada..." />
                <flux:error name="add_nominal_baru_diajukan" />
            </flux:field>

            <flux:field>
                <flux:label>Status Persetujuan</flux:label>
                <flux:select wire:model="add_status_persetujuan">
                    <flux:select.option value="none">None</flux:select.option>
                    <flux:select.option value="pending_approval">Pending Approval</flux:select.option>
                    <flux:select.option value="approved">Approved</flux:select.option>
                    <flux:select.option value="rejected">Rejected</flux:select.option>
                </flux:select>
                <flux:error name="add_status_persetujuan" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button variant="subtle" x-on:click="$flux.modal('add-modal').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Models\KoperasiManagement;
use App\Models\Employee;
use App\Models\User;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;

    // Create fields
    public $employee_id = '';
    public $employeeSearch = '';
    public $email = '';
    public $jabatan = '';
    public $start_date = '';
    public $end_date = '';
    public $status = 'active';

    // Edit fields
    public $editingManagementId;
    public $edit_email = '';
    public $edit_password = '';
    public $edit_jabatan = '';
    public $edit_start_date = '';
    public $edit_end_date = '';
    public $edit_status = 'active';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function boardMembers()
    {
        $query = KoperasiManagement::with(['employee', 'employee.user']);

        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('npk', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_lengkap', 'like', '%' . $this->search . '%');
            })->orWhere('jabatan', 'like', '%' . $this->search . '%');
        }

        return $query->orderBy('status', 'asc')
                     ->orderBy('start_date', 'desc')
                     ->paginate($this->perPage);
    }

    #[Computed]
    public function filteredEmployees()
    {
        $query = Employee::query();

        if ($this->employeeSearch) {
            $term = $this->employeeSearch;
            if (str_contains($term, ' - ')) {
                $parts = explode(' - ', $term);
                $term = $parts[0];
            }

            $query->where('npk', 'like', '%' . $term . '%')
                  ->orWhere('nama_lengkap', 'like', '%' . $term . '%');
        }

        return $query->orderBy('nama_lengkap', 'asc')->take(50)->get();
    }

    public function selectEmployee($id, $label)
    {
        $this->employee_id = $id;
        $this->employeeSearch = $label;

        // Autofill email if employee already has a user account
        $employee = Employee::find($id);
        if ($employee && $employee->user) {
            $this->email = $employee->user->email;
        }
    }

    public function openAddModal()
    {
        $this->reset(['employee_id', 'employeeSearch', 'email', 'jabatan', 'start_date', 'end_date', 'status']);
        $this->resetValidation();
        $this->js("Flux.modal('add-modal').show()");
    }

    public function save()
    {
        $employee = Employee::find($this->employee_id);
        $userId = $employee?->user?->id ?? '';

        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'email' => 'required|email|unique:users,email,' . $userId,
            'jabatan' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive',
        ]);

        // Prevent multiple active positions for the same employee
        if ($this->status === 'active') {
            $activeExists = KoperasiManagement::where('employee_id', $this->employee_id)
                ->where('status', 'active')
                ->exists();

            if ($activeExists) {
                $this->addError('employee_id', 'Karyawan ini sudah memiliki jabatan pengurus aktif.');
                return;
            }
        }

        \DB::transaction(function () use ($employee) {
            KoperasiManagement::create([
                'employee_id' => $this->employee_id,
                'jabatan' => trim($this->jabatan),
                'start_date' => $this->start_date ?: null,
                'end_date' => $this->end_date ?: null,
                'status' => $this->status,
            ]);

            // Sync User login credentials
            $user = $employee->user;
            if ($user) {
                $user->update(['email' => $this->email]);
            } else {
                $defaultPassword = bcrypt($employee->npk . '@1234');
                User::create([
                    'userable_id' => $employee->id,
                    'userable_type' => Employee::class,
                    'username' => $employee->npk,
                    'email' => $this->email,
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]);
            }
        });

        $this->js("Flux.modal('add-modal').close()");
        $this->js("Flux.toast({ text: 'Pengurus baru berhasil ditambahkan.', variant: 'success' })");
        $this->reset(['employee_id', 'employeeSearch', 'email', 'jabatan', 'start_date', 'end_date', 'status']);
    }

    public function edit($id)
    {
        $member = KoperasiManagement::with(['employee', 'employee.user'])->findOrFail($id);
        $this->editingManagementId = $member->id;
        $this->edit_jabatan = $member->jabatan;
        $this->edit_start_date = $member->start_date ? $member->start_date->format('Y-m-d') : '';
        $this->edit_end_date = $member->end_date ? $member->end_date->format('Y-m-d') : '';
        $this->edit_status = $member->status;

        // Load email from related employee user
        $this->edit_email = $member->employee->user?->email ?? '';
        $this->edit_password = '';

        $this->resetValidation();
        $this->js("Flux.modal('edit-modal').show()");
    }

    public function update()
    {
        $member = KoperasiManagement::findOrFail($this->editingManagementId);
        $userId = $member->employee->user?->id ?? '';

        $this->validate([
            'edit_email' => 'required|email|unique:users,email,' . $userId,
            'edit_jabatan' => 'required|string|max:255',
            'edit_start_date' => 'required|date',
            'edit_end_date' => 'nullable|date|after_or_equal:edit_start_date',
            'edit_status' => 'required|in:active,inactive',
            'edit_password' => 'nullable|min:6',
        ]);

        // Prevent multiple active positions for the same employee
        if ($this->edit_status === 'active') {
            $activeExists = KoperasiManagement::where('employee_id', $member->employee_id)
                ->where('status', 'active')
                ->where('id', '!=', $this->editingManagementId)
                ->exists();

            if ($activeExists) {
                $this->addError('edit_status', 'Karyawan ini sudah memiliki jabatan pengurus aktif.');
                return;
            }
        }

        \DB::transaction(function () use ($member) {
            $member->update([
                'jabatan' => trim($this->edit_jabatan),
                'start_date' => $this->edit_start_date ?: null,
                'end_date' => $this->edit_end_date ?: null,
                'status' => $this->edit_status,
            ]);

            // Sync User login credentials
            $user = $member->employee->user;
            $userData = [
                'email' => $this->edit_email,
                'username' => $member->employee->npk,
            ];
            if ($this->edit_password) {
                $userData['password'] = bcrypt($this->edit_password);
            }

            if ($user) {
                $user->update($userData);
            } else {
                $defaultPassword = $this->edit_password ? bcrypt($this->edit_password) : bcrypt($member->employee->npk . '@1234');
                User::create([
                    'userable_id' => $member->employee->id,
                    'userable_type' => Employee::class,
                    'username' => $member->employee->npk,
                    'email' => $this->edit_email,
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]);
            }
        });

        $this->js("Flux.modal('edit-modal').close()");
        $this->js("Flux.toast({ text: 'Data pengurus berhasil diperbarui.', variant: 'success' })");
    }

    public function delete($id)
    {
        $member = KoperasiManagement::find($id);

        if ($member) {
            $member->delete();
            $this->js("Flux.toast({ text: 'Data pengurus berhasil dihapus.', variant: 'success' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Pengurus Koperasi</flux:heading>
            <flux:text class="mt-2 text-base">Kelola daftar pengurus / manajemen Koperasi</flux:text>
        </div>
        <flux:button wire:click="openAddModal" variant="primary" icon="plus">Tambah Pengurus</flux:button>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-3">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="md">Daftar Pengurus</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-72" placeholder="Cari NPK, nama, jabatan..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-3 mb-1" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->boardMembers">
                <flux:table.columns>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Jabatan</flux:table.column>
                    <flux:table.column>Mulai Jabatan</flux:table.column>
                    <flux:table.column>Akhir Jabatan</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->boardMembers as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->employee->npk ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $row->employee->nama_lengkap ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $row->employee->user->email ?? '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $row->jabatan }}</flux:table.cell>
                            <flux:table.cell>{{ $row->start_date ? $row->start_date->format('d M Y') : '-' }}</flux:table.cell>
                            <flux:table.cell>{{ $row->end_date ? $row->end_date->format('d M Y') : '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'active')
                                    <flux:badge size="sm" color="green" inset="top bottom">Aktif</flux:badge>
                                @elseif($row->status === 'inactive')
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Tidak Aktif</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $row->status }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="edit({{ $row->id }})">Edit</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus pengurus ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-6 text-zinc-500">
                                Tidak ada data pengurus koperasi yang ditemukan.
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
            <flux:heading size="lg">Tambah Pengurus Koperasi</flux:heading>
            <flux:text class="text-sm">Cari karyawan perusahaan dan tentukan jabatannya sebagai pengurus koperasi.</flux:text>
        </div>

        <form wire:submit="save" class="space-y-6">
            <!-- Custom Searchable Dropdown for Employee -->
            <div x-data="{ open: false }" class="relative">
                <flux:field>
                    <flux:label>Pilih Karyawan</flux:label>
                    <flux:input 
                        type="text" 
                        placeholder="Ketik NPK atau Nama Karyawan..." 
                        wire:model.live="employeeSearch"
                        x-on:focus="open = true"
                        x-on:click="open = true"
                        x-on:keydown.enter.prevent=""
                        icon="magnifying-glass"
                    />
                    
                    <!-- Dropdown Options -->
                    <div 
                        x-show="open" 
                        x-on:click.outside="open = false"
                        class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700"
                        style="display: none;"
                        x-transition
                    >
                        @forelse($this->filteredEmployees as $emp)
                            <div 
                                x-on:click="open = false; $wire.selectEmployee({{ $emp->id }}, '{{ $emp->npk }} - {{ $emp->nama_lengkap }}')"
                                class="px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer text-sm text-zinc-900 dark:text-zinc-100 flex justify-between"
                            >
                                <span class="font-medium">{{ $emp->nama_lengkap }}</span>
                                <span class="font-mono text-zinc-400 text-xs">{{ $emp->npk }}</span>
                            </div>
                        @empty
                            <div class="px-4 py-2 text-sm text-zinc-500">Karyawan tidak ditemukan.</div>
                        @endforelse
                    </div>
                    
                    <flux:error name="employee_id" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Email Akun (Untuk Login)</flux:label>
                <flux:input type="email" wire:model="email" placeholder="pengurus@koperasi.test" required />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Jabatan Pengurus</flux:label>
                <flux:select wire:model="jabatan" placeholder="Pilih Jabatan">
                    <flux:select.option value="Ketua">Ketua</flux:select.option>
                    <flux:select.option value="Wakil Ketua">Wakil Ketua</flux:select.option>
                    <flux:select.option value="Bendahara">Bendahara</flux:select.option>
                    <flux:select.option value="Sekretaris">Sekretaris</flux:select.option>
                    <flux:select.option value="Pengawas">Pengawas</flux:select.option>
                </flux:select>
                <flux:error name="jabatan" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Masa Mulai Jabatan</flux:label>
                    <flux:input type="date" wire:model="start_date" required />
                    <flux:error name="start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Masa Akhir Jabatan</flux:label>
                    <flux:input type="date" wire:model="end_date" />
                    <flux:error name="end_date" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Status Pengurus</flux:label>
                <flux:select wire:model="status" placeholder="Pilih Status">
                    <flux:select.option value="active">Aktif</flux:select.option>
                    <flux:select.option value="inactive">Tidak Aktif</flux:select.option>
                </flux:select>
                <flux:error name="status" />
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
            <flux:heading size="lg">Edit Informasi Pengurus</flux:heading>
            <flux:text class="text-sm">Sesuaikan jabatan, masa jabatan, status, atau akun login pengurus koperasi.</flux:text>
        </div>

        <form wire:submit="update" class="space-y-6">
            <flux:field>
                <flux:label>Email Akun (Untuk Login)</flux:label>
                <flux:input type="email" wire:model="edit_email" required />
                <flux:error name="edit_email" />
            </flux:field>

            <flux:field>
                <flux:label>Ubah Password (Kosongkan jika tidak diubah)</flux:label>
                <flux:input type="password" wire:model="edit_password" placeholder="Masukkan password baru..." />
                <flux:error name="edit_password" />
            </flux:field>

            <flux:field>
                <flux:label>Jabatan Pengurus</flux:label>
                <flux:select wire:model="edit_jabatan" placeholder="Pilih Jabatan">
                    <flux:select.option value="Ketua">Ketua</flux:select.option>
                    <flux:select.option value="Wakil Ketua">Wakil Ketua</flux:select.option>
                    <flux:select.option value="Bendahara">Bendahara</flux:select.option>
                    <flux:select.option value="Sekretaris">Sekretaris</flux:select.option>
                    <flux:select.option value="Pengawas">Pengawas</flux:select.option>
                </flux:select>
                <flux:error name="edit_jabatan" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Masa Mulai Jabatan</flux:label>
                    <flux:input type="date" wire:model="edit_start_date" required />
                    <flux:error name="edit_start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Masa Akhir Jabatan</flux:label>
                    <flux:input type="date" wire:model="edit_end_date" />
                    <flux:error name="edit_end_date" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Status Pengurus</flux:label>
                <flux:select wire:model="edit_status" placeholder="Pilih Status">
                    <flux:select.option value="active">Aktif</flux:select.option>
                    <flux:select.option value="inactive">Tidak Aktif</flux:select.option>
                </flux:select>
                <flux:error name="edit_status" />
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

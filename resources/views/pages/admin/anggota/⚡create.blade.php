<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Computed;
use App\Models\Employee;
use App\Models\KoperasiMember;
use App\Models\User;
use App\Models\NamaBank;

new #[Layout('layouts::admin')] class extends Component
{
    // Search & Select Employee
    public $employee_id = '';
    public $employeeSearch = '';
    public $selectedEmployee = null;

    // Koperasi Member fields
    #[Validate('required|email|unique:users,email')]
    public $email = '';

    #[Validate('required|date')]
    public $join_date = '';

    #[Validate('nullable|date')]
    public $join_koperasi_astra = '';

    #[Validate('required')]
    public $jenis_bank = '';

    #[Validate('required|numeric')]
    public $no_rekening = '';

    #[Validate('required')]
    public $nama_pemilik_rekening = '';

    #[Validate('required')]
    public $nama_ahli_waris = '';

    #[Validate('required')]
    public $hubungan_ahli_waris = '';

    #[Validate('required_if:hubungan_ahli_waris,Lainnya')]
    public $hubungan_lainnya = '';

    public function mount()
    {
        $this->join_date = date('Y-m-d');
    }

    #[Computed]
    public function availableEmployees()
    {
        $query = Employee::whereDoesntHave('koperasiMember');

        if ($this->employeeSearch && !str_contains($this->employeeSearch, ' - ')) {
            $query->where(function ($q) {
                $q->where('npk', 'like', '%' . $this->employeeSearch . '%')
                  ->orWhere('nama_lengkap', 'like', '%' . $this->employeeSearch . '%');
            });
        }

        return $query->orderBy('nama_lengkap', 'asc')->take(50)->get();
    }

    #[Computed]
    public function bankList()
    {
        $banks = NamaBank::orderBy('nama_bank', 'asc')->pluck('nama_bank')->toArray();
        if (empty($banks)) {
            return ['BCA', 'BRI', 'BNI', 'BSI', 'BJB', 'BTN', 'Mandiri', 'Bank DKI', 'Bank Muamalat', 'Seabank', 'Permata'];
        }
        return $banks;
    }

    public function selectEmployee($id, $label)
    {
        $this->employee_id = $id;
        $this->employeeSearch = $label;
        $this->selectedEmployee = Employee::find($id);

        if ($this->selectedEmployee) {
            $this->nama_pemilik_rekening = $this->selectedEmployee->nama_pemilik_rekening ?: $this->selectedEmployee->nama_lengkap;
            $this->jenis_bank = $this->selectedEmployee->nama_bank ?? '';
            $this->no_rekening = $this->selectedEmployee->no_rekening ?? '';
        }
    }

    public function save()
    {
        $this->validate([
            'employee_id' => 'required|exists:employees,id|unique:koperasi_members,employee_id',
        ]);

        $this->validate();

        $employee = Employee::findOrFail($this->employee_id);

        \DB::transaction(function () use ($employee) {
            // 1. Update Employee with bank details
            $employee->update([
                'no_rekening' => $this->no_rekening,
                'nama_bank' => $this->jenis_bank,
                'nama_pemilik_rekening' => $this->nama_pemilik_rekening,
            ]);

            // 2. Create Koperasi Member
            KoperasiMember::create([
                'employee_id' => $employee->id,
                'member_number' => 'M' . $employee->npk,
                'join_koperasi_astra' => $this->join_koperasi_astra ?: null,
                'join_date' => $this->join_date,
                'status' => 'active',
                'is_approved' => true,
                'approved_at' => now(),
                'nama_ahli_waris' => $this->nama_ahli_waris,
                'hubungan_ahli_waris' => $this->hubungan_ahli_waris,
                'hubungan_lainnya' => $this->hubungan_lainnya,
            ]);

            // 2. Create User login polymorphically linked to the Employee
            $defaultPassword = bcrypt($employee->npk . '@1234'); 

            User::create([
                'userable_id' => $employee->id,
                'userable_type' => Employee::class,
                'username' => $employee->npk,
                'email' => $this->email,
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]);
        });

        $this->js("Flux.toast({ text: 'Anggota berhasil ditambahkan', variant: 'success' })");
        return $this->redirect('/flux-members', navigate: true); // Wait, redirect back to /admin/anggota
    }

    public function handleRedirect()
    {
        return $this->redirect('/admin/anggota', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/anggota" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Tambah Anggota Koperasi</flux:heading>
            <flux:text class="mt-2 text-base">Pilih karyawan aktif dan isi informasi rekening serta ahli waris untuk didaftarkan sebagai anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="mt-6">
        <form wire:submit="save" class="space-y-6">
            
            <!-- STEP 1: PILIH KARYAWAN -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">1. Data Karyawan</flux:heading>
                
                <div x-data="{ open: false }" class="relative max-w-xl">
                    <flux:field>
                        <flux:label>Cari Karyawan</flux:label>
                        <flux:input 
                            type="text" 
                            placeholder="Ketik NPK atau Nama Karyawan..." 
                            wire:model.live="employeeSearch"
                            x-on:focus="open = true"
                            x-on:click="open = true"
                            x-on:keydown.enter.prevent=""
                            icon="magnifying-glass"
                        />
                        
                        <div 
                            x-show="open" 
                            x-on:click.outside="open = false"
                            class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700"
                            style="display: none;"
                            x-transition
                        >
                            @forelse($this->availableEmployees as $emp)
                                <div 
                                    x-on:click="open = false; $wire.selectEmployee({{ $emp->id }}, '{{ $emp->npk }} - {{ $emp->nama_lengkap }}')"
                                    class="px-4 py-2.5 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer text-sm text-zinc-900 dark:text-zinc-100 flex justify-between"
                                >
                                    <span class="font-medium">{{ $emp->nama_lengkap }}</span>
                                    <span class="font-mono text-zinc-400 text-xs">{{ $emp->npk }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-2.5 text-sm text-zinc-500">Karyawan tidak ditemukan atau sudah terdaftar.</div>
                            @endforelse
                        </div>
                        
                        <flux:error name="employee_id" />
                    </flux:field>
                </div>

                @if($selectedEmployee)
                    <!-- Employee Preview Card -->
                    <div class="mt-4 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50/50 dark:bg-zinc-800/30 max-w-2xl grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-400 block text-xs">NPK</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->npk }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-xs">Nama Lengkap</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->nama_lengkap }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-xs">Jenis Kelamin</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->jk === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-xs">Seksi / Departemen</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->seksi ?? '-' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-zinc-400 block text-xs">Alamat</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->alamat ?? '-' }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- STEP 2: DETAILS KOPERASI -->
            <div>
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">2. Informasi Akun & Keanggotaan</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label>Email Akun (Untuk Login & Notifikasi)</flux:label>
                        <flux:input type="email" wire:model="email" placeholder="contoh@domain.com" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tanggal Mulai Gabung Koperasi</flux:label>
                        <flux:input type="date" wire:model="join_date" />
                        <flux:error name="join_date" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tanggal Mulai Gabung Koperasi Astra (Opsional)</flux:label>
                        <flux:input type="date" wire:model="join_koperasi_astra" />
                        <flux:error name="join_koperasi_astra" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nama Bank Penerima</flux:label>
                        <flux:select wire:model="jenis_bank" placeholder="Pilih Bank...">
                            @foreach($this->bankList as $bank)
                                <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="jenis_bank" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nomor Rekening</flux:label>
                        <flux:input wire:model="no_rekening" placeholder="Contoh: 1234567890" />
                        <flux:error name="no_rekening" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nama Pemilik Rekening</flux:label>
                        <flux:input wire:model="nama_pemilik_rekening" placeholder="Nama pemilik rekening sesuai bank" />
                        <flux:error name="nama_pemilik_rekening" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nama Ahli Waris</flux:label>
                        <flux:input wire:model="nama_ahli_waris" placeholder="Nama lengkap ahli waris" />
                        <flux:error name="nama_ahli_waris" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Hubungan Ahli Waris</flux:label>
                        <flux:select wire:model.live="hubungan_ahli_waris" placeholder="Pilih Hubungan...">
                            <flux:select.option value="suami_istri">Suami / Istri</flux:select.option>
                            <flux:select.option value="anak">Anak</flux:select.option>
                            <flux:select.option value="orang_tua">Orang Tua</flux:select.option>
                            <flux:select.option value="saudara">Saudara Kandung</flux:select.option>
                            <flux:select.option value="Lainnya">Lainnya</flux:select.option>
                        </flux:select>
                        <flux:error name="hubungan_ahli_waris" />
                    </flux:field>

                    @if($hubungan_ahli_waris === 'Lainnya')
                        <flux:field class="md:col-span-2">
                            <flux:label>Sebutkan Hubungan Lainnya</flux:label>
                            <flux:input wire:model="hubungan_lainnya" placeholder="Sebutkan hubungan spesifik" />
                            <flux:error name="hubungan_lainnya" />
                        </flux:field>
                    @endif
                </div>
            </div>

            <!-- Password Info -->
            <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700">
                <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    ℹ️ Password default login untuk anggota baru adalah <span class="font-mono bg-zinc-200 dark:bg-zinc-800 px-1.5 py-0.5 rounded text-zinc-900 dark:text-white">[NPK]@1234</span> (Contoh: <span class="font-mono">10021@1234</span>).
                </flux:text>
            </flux:card>

            <div class="mt-8 flex justify-end gap-3">
                <flux:button type="button" wire:click="handleRedirect" variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary" :disabled="!$employee_id">Simpan</flux:button>
            </div>

        </form>
    </flux:card>
</div>
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use App\Models\KoperasiStaff;
use App\Models\User;

new #[Layout('layouts::admin')] class extends Component
{
    #[Validate('required|string|unique:koperasi_staff,npk')]
    public $npk = '';

    #[Validate('required|string|max:255')]
    public $nama = '';

    #[Validate('required|in:L,P')]
    public $jk = 'L';

    #[Validate('nullable|string|max:255')]
    public $tempat_lahir = '';

    #[Validate('nullable|date')]
    public $tanggal_lahir = '';

    #[Validate('nullable|string')]
    public $alamat = '';

    #[Validate('nullable|string|max:50')]
    public $no_telp = '';

    #[Validate('nullable|string|max:100')]
    public $jabatan = '';

    #[Validate('nullable|date')]
    public $hire_date = '';

    #[Validate('required|in:active,inactive,resign')]
    public $employment_status = 'active';

    // User Login fields
    #[Validate('required|email|unique:users,email')]
    public $email = '';

    public function save()
    {
        $this->validate();

        \DB::transaction(function () {
            $staff = KoperasiStaff::create([
                'npk' => $this->npk,
                'nama' => $this->nama,
                'jk' => $this->jk,
                'tempat_lahir' => $this->tempat_lahir ?: null,
                'tanggal_lahir' => $this->tanggal_lahir ?: null,
                'alamat' => $this->alamat ?: null,
                'no_telp' => $this->no_telp ?: null,
                'jabatan' => $this->jabatan ?: null,
                'hire_date' => $this->hire_date ?: null,
                'employment_status' => $this->employment_status,
            ]);

            $defaultPassword = bcrypt($this->npk . '@1234'); 

            User::create([
                'userable_id' => $staff->id,
                'userable_type' => KoperasiStaff::class,
                'username' => $this->npk,
                'email' => $this->email,
                'password' => $defaultPassword,
                'email_verified_at' => now(),
            ]);
        });

        $this->js("Flux.toast({ text: 'Staff Koperasi baru berhasil ditambahkan.', variant: 'success' })");

        return $this->redirect('/admin/koperasi-staff', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/koperasi-staff" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Tambah Staff Koperasi</flux:heading>
            <flux:text class="mt-2 text-base">Masukkan data staff koperasi baru dan buat akun login pengelola.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="mt-6">
        <form wire:submit="save" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- NPK -->
                <flux:field>
                    <flux:label>NPK / Kode Staff</flux:label>
                    <flux:input wire:model="npk" placeholder="Contoh: K011" autofocus />
                    <flux:error name="npk" />
                </flux:field>

                <!-- Nama Lengkap -->
                <flux:field>
                    <flux:label>Nama Lengkap</flux:label>
                    <flux:input wire:model="nama" placeholder="Masukkan nama lengkap staff" />
                    <flux:error name="nama" />
                </flux:field>

                <!-- Email (Login) -->
                <flux:field>
                    <flux:label>Email (Untuk Login & Notifikasi)</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="staff@koperasi.test" />
                    <flux:error name="email" />
                </flux:field>

                <!-- No Telepon -->
                <flux:field>
                    <flux:label>No. Telepon / WhatsApp</flux:label>
                    <flux:input type="tel" wire:model="no_telp" placeholder="Contoh: 08XXXXXXXXXX" />
                    <flux:error name="no_telp" />
                </flux:field>

                <!-- Jenis Kelamin -->
                <flux:field>
                    <flux:radio.group wire:model="jk" label="Jenis Kelamin">
                        <flux:radio value="L" label="Laki-laki" />
                        <flux:radio value="P" label="Perempuan" />
                    </flux:radio.group>
                    <flux:error name="jk" />
                </flux:field>

                <!-- Jabatan -->
                <flux:field>
                    <flux:label>Jabatan</flux:label>
                    <flux:select wire:model="jabatan" placeholder="Pilih Jabatan">
                        <flux:select.option value="Admin">Admin</flux:select.option>
                        <flux:select.option value="Kasir">Kasir</flux:select.option>
                        <flux:select.option value="Accounting">Accounting</flux:select.option>
                        <flux:select.option value="Staff Operasional">Staff Operasional</flux:select.option>
                    </flux:select>
                    <flux:error name="jabatan" />
                </flux:field>
               
                <!-- Tempat Lahir -->
                <flux:field>
                    <flux:label>Tempat Lahir</flux:label>
                    <flux:input wire:model="tempat_lahir" placeholder="Kota/Kabupaten kelahiran" />
                    <flux:error name="tempat_lahir" />
                </flux:field>

                <!-- Tanggal Lahir -->
                <flux:field>
                    <flux:label>Tanggal Lahir</flux:label>
                    <flux:input type="date" wire:model="tanggal_lahir" />
                    <flux:error name="tanggal_lahir" />
                </flux:field>

                <!-- Tanggal Masuk (Hire Date) -->
                <flux:field>
                    <flux:label>Tanggal Masuk (Mulai Bekerja)</flux:label>
                    <flux:input type="date" wire:model="hire_date" />
                    <flux:error name="hire_date" />
                </flux:field>

                <!-- Status Kerja -->
                <flux:field>
                    <flux:label>Status Kepegawaian</flux:label>
                    <flux:select wire:model="employment_status" placeholder="Pilih Status">
                        <flux:select.option value="active">Aktif</flux:select.option>
                        <flux:select.option value="inactive">Tidak Aktif</flux:select.option>
                        <flux:select.option value="resign">Resign</flux:select.option>
                    </flux:select>
                    <flux:error name="employment_status" />
                </flux:field>

                <!-- Alamat Lengkap -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Alamat Lengkap</flux:label>
                        <flux:textarea wire:model="alamat" rows="3" placeholder="Masukkan alamat lengkap domisili" />
                        <flux:error name="alamat" />
                    </flux:field>
                </div>
            </div>

            <!-- Password Info -->
            <flux:card class="bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700">
                <flux:text class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    ℹ️ Password default login untuk staff baru adalah <span class="font-mono bg-zinc-200 dark:bg-zinc-800 px-1.5 py-0.5 rounded text-zinc-900 dark:text-white">[NPK]@1234</span> (Contoh: <span class="font-mono">K011@1234</span>).
                </flux:text>
            </flux:card>

            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="/admin/koperasi-staff" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>

        </form>
    </flux:card>
</div>

<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\KoperasiStaff;
use App\Models\User;

new #[Layout('layouts::admin')] class extends Component
{
    public KoperasiStaff $staff;

    public $npk = '';
    public $nama = '';
    public $jk = 'L';
    public $tempat_lahir = '';
    public $tanggal_lahir = '';
    public $alamat = '';
    public $no_telp = '';
    public $jabatan = '';
    public $hire_date = '';
    public $employment_status = 'active';

    // User Login fields
    public $email = '';

    public function mount($id)
    {
        $this->staff = KoperasiStaff::with('user')->findOrFail($id);

        $this->npk = $this->staff->npk;
        $this->nama = $this->staff->nama;
        $this->jk = $this->staff->jk ?? 'L';
        $this->tempat_lahir = $this->staff->tempat_lahir ?? '';
        $this->tanggal_lahir = $this->staff->tanggal_lahir ? $this->staff->tanggal_lahir->format('Y-m-d') : '';
        $this->alamat = $this->staff->alamat ?? '';
        $this->no_telp = $this->staff->no_telp ?? '';
        $this->jabatan = $this->staff->jabatan ?? '';
        $this->hire_date = $this->staff->hire_date ? $this->staff->hire_date->format('Y-m-d') : '';
        $this->employment_status = $this->staff->employment_status ?? 'active';

        // Load email from related User record
        $this->email = $this->staff->user?->email ?? '';
    }

    protected function rules()
    {
        $userId = $this->staff->user?->id ?? '';

        return [
            'npk' => 'required|string|unique:koperasi_staff,npk,' . $this->staff->id,
            'nama' => 'required|string|max:255',
            'jk' => 'required|in:L,P',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_telp' => 'nullable|string|max:50',
            'jabatan' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'employment_status' => 'required|in:active,inactive,resign',
            'email' => 'required|email|unique:users,email,' . $userId,
        ];
    }

    public function save()
    {
        $this->validate();

        \DB::transaction(function () {
            $this->staff->update([
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

            // Update polymorphic User record if it exists, or create one if missing
            if ($this->staff->user) {
                $this->staff->user->update([
                    'username' => $this->npk,
                    'email' => $this->email,
                ]);
            } else {
                $defaultPassword = bcrypt($this->npk . '@1234');
                User::create([
                    'userable_id' => $this->staff->id,
                    'userable_type' => KoperasiStaff::class,
                    'username' => $this->npk,
                    'email' => $this->email,
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]);
            }
        });

        $this->js("Flux.toast({ text: 'Data staff koperasi berhasil diperbarui.', variant: 'success' })");

        return $this->redirect('/admin/koperasi-staff', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/koperasi-staff" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Edit Staff Koperasi</flux:heading>
            <flux:text class="mt-2 text-base">Ubah informasi data staff dan akun login pengelola.</flux:text>
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

            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="/admin/koperasi-staff" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>

        </form>
    </flux:card>
</div>

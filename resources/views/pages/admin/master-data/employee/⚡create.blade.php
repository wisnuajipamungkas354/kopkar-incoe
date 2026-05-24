<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use App\Models\Employee;

new #[Layout('layouts::admin')] class extends Component
{
    #[Validate('required|string|unique:employees,npk')]
    public $npk = '';

    #[Validate('required|string|max:255')]
    public $nama_lengkap = '';

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

    #[Validate('nullable|in:SMP,SMA/K,D3,S1,S2,S3')]
    public $pendidikan_terakhir = '';

    #[Validate('nullable|in:Produksi,Warehouse,QC,HR,IT,Finance')]
    public $seksi = '';

    #[Validate('nullable|in:A,B,C')]
    public $grade_category = '';

    #[Validate('nullable|in:tetap,kontrak')]
    public $employment_status = '';

    public function save()
    {
        $this->validate();

        Employee::create([
            'npk' => $this->npk,
            'nama_lengkap' => $this->nama_lengkap,
            'jk' => $this->jk,
            'tempat_lahir' => $this->tempat_lahir ?: null,
            'tanggal_lahir' => $this->tanggal_lahir ?: null,
            'alamat' => $this->alamat ?: null,
            'no_telp' => $this->no_telp ?: null,
            'pendidikan_terakhir' => $this->pendidikan_terakhir ?: null,
            'seksi' => $this->seksi ?: null,
            'grade_category' => $this->grade_category ?: null,
            'employment_status' => $this->employment_status ?: null,
        ]);

        $this->js("Flux.toast({ text: 'Karyawan baru berhasil ditambahkan.', variant: 'success' })");

        return $this->redirect('/admin/employee', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/employee" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Tambah Karyawan</flux:heading>
            <flux:text class="mt-2 text-base">Masukkan data lengkap karyawan baru di bawah ini.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="mt-6">
        <form wire:submit="save" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- NPK -->
                <flux:field>
                    <flux:label>NPK (Nomor Pokok Karyawan)</flux:label>
                    <flux:input wire:model="npk" placeholder="Contoh: 12345" autofocus />
                    <flux:error name="npk" />
                </flux:field>

                <!-- Nama Lengkap -->
                <flux:field>
                    <flux:label>Nama Lengkap</flux:label>
                    <flux:input wire:model="nama_lengkap" placeholder="Masukkan nama lengkap sesuai KTP" />
                    <flux:error name="nama_lengkap" />
                </flux:field>

                <!-- Jenis Kelamin -->
                <flux:field>
                    <flux:radio.group wire:model="jk" label="Jenis Kelamin">
                        <flux:radio value="L" label="Laki-laki" />
                        <flux:radio value="P" label="Perempuan" />
                    </flux:radio.group>
                    <flux:error name="jk" />
                </flux:field>

                <!-- No Telepon -->
                <flux:field>
                    <flux:label>No. Telepon / WhatsApp</flux:label>
                    <flux:input type="tel" wire:model="no_telp" placeholder="Contoh: 0812XXXXXXXX" />
                    <flux:error name="no_telp" />
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

                <!-- Pendidikan Terakhir -->
                <flux:field>
                    <flux:label>Pendidikan Terakhir</flux:label>
                    <flux:select wire:model="pendidikan_terakhir" placeholder="Pilih Pendidikan">
                        <flux:select.option value="SMP">SMP</flux:select.option>
                        <flux:select.option value="SMA/K">SMA/K</flux:select.option>
                        <flux:select.option value="D3">D3</flux:select.option>
                        <flux:select.option value="S1">S1/D4</flux:select.option>
                        <flux:select.option value="S2">S2</flux:select.option>
                        <flux:select.option value="S3">S3</flux:select.option>
                    </flux:select>
                    <flux:error name="pendidikan_terakhir" />
                </flux:field>

                <!-- Seksi -->
                <flux:field>
                    <flux:label>Seksi / Departemen</flux:label>
                    <flux:select wire:model="seksi" placeholder="Pilih Seksi">
                        <flux:select.option value="Produksi">Produksi</flux:select.option>
                        <flux:select.option value="Warehouse">Warehouse</flux:select.option>
                        <flux:select.option value="QC">QC</flux:select.option>
                        <flux:select.option value="HR">HR</flux:select.option>
                        <flux:select.option value="IT">IT</flux:select.option>
                        <flux:select.option value="Finance">Finance</flux:select.option>
                    </flux:select>
                    <flux:error name="seksi" />
                </flux:field>

                <!-- Grade Category -->
                <flux:field>
                    <flux:label>Grade Category</flux:label>
                    <flux:select wire:model="grade_category" placeholder="Pilih Grade">
                        <flux:select.option value="A">Grade A</flux:select.option>
                        <flux:select.option value="B">Grade B</flux:select.option>
                        <flux:select.option value="C">Grade C</flux:select.option>
                    </flux:select>
                    <flux:error name="grade_category" />
                </flux:field>

                <!-- Status Kerja -->
                <flux:field>
                    <flux:label>Status Pekerjaan</flux:label>
                    <flux:select wire:model="employment_status" placeholder="Pilih Status">
                        <flux:select.option value="tetap">Karyawan Tetap</flux:select.option>
                        <flux:select.option value="kontrak">Karyawan Kontrak</flux:select.option>
                    </flux:select>
                    <flux:error name="employment_status" />
                </flux:field>

                <!-- Alamat Lengkap -->
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Alamat Lengkap</flux:label>
                        <flux:textarea wire:model="alamat" rows="3" placeholder="Masukkan alamat domisili lengkap" />
                        <flux:error name="alamat" />
                    </flux:field>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="/admin/employee" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>

        </form>
    </flux:card>
</div>

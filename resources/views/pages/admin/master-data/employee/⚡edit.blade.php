<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Employee;

new #[Layout('layouts::admin')] class extends Component
{
    public Employee $employee;

    public $npk = '';
    public $nama_lengkap = '';
    public $jk = 'L';
    public $tempat_lahir = '';
    public $tanggal_lahir = '';
    public $alamat = '';
    public $no_telp = '';
    public $pendidikan_terakhir = '';
    public $seksi = '';
    public $grade_category = '';
    public $employment_status = '';

    public function mount($id)
    {
        $this->employee = Employee::findOrFail($id);

        $this->npk = $this->employee->npk;
        $this->nama_lengkap = $this->employee->nama_lengkap;
        $this->jk = $this->employee->jk ?? 'L';
        $this->tempat_lahir = $this->employee->tempat_lahir ?? '';
        $this->tanggal_lahir = $this->employee->tanggal_lahir ? $this->employee->tanggal_lahir->format('Y-m-d') : '';
        $this->alamat = $this->employee->alamat ?? '';
        $this->no_telp = $this->employee->no_telp ?? '';
        $this->pendidikan_terakhir = $this->employee->pendidikan_terakhir ?? '';
        $this->seksi = $this->employee->seksi ?? '';
        $this->grade_category = $this->employee->grade_category ?? '';
        $this->employment_status = $this->employee->employment_status ?? '';
    }

    protected function rules()
    {
        return [
            'npk' => 'required|string|unique:employees,npk,' . $this->employee->id,
            'nama_lengkap' => 'required|string|max:255',
            'jk' => 'required|in:L,P',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'no_telp' => 'nullable|string|max:50',
            'pendidikan_terakhir' => 'nullable|in:SMP,SMA/K,D3,S1,S2,S3',
            'seksi' => 'nullable|in:Produksi,Warehouse,QC,HR,IT,Finance',
            'grade_category' => 'nullable|in:A,B,C',
            'employment_status' => 'nullable|in:tetap,kontrak',
        ];
    }

    public function save()
    {
        $this->validate();

        $this->employee->update([
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

        $this->js("Flux.toast({ text: 'Data karyawan berhasil diperbarui.', variant: 'success' })");

        return $this->redirect('/admin/employee', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/employee" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Edit Karyawan</flux:heading>
            <flux:text class="mt-2 text-base">Ubah informasi data karyawan di bawah ini.</flux:text>
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
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>

        </form>
    </flux:card>
</div>

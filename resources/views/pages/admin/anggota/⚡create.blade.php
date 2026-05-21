<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use App\Models\User;

new #[Layout('layouts::admin')] class extends Component
{
    public array $banks = ['BCA', 'BRI', 'BNI', 'BSI', 'BJB', 'BTN', 'Mandiri', 'Bank DKI', 'Bank Muamalat', 'Seabank', 'Permata'];

    #[Validate('required|min:3')]
    public $npk;

    #[Validate('required')]
    public $nama_lengkap;

    #[Validate('required')]
    public $jenis_kelamin = 'L';

    #[Validate('required')]
    public $tempat_lahir;

    #[Validate('required|date')]
    public $tanggal_lahir;

    #[Validate('required')]
    public $alamat;

    #[Validate('required|numeric|min:10')]
    public $no_whatsapp;

    #[Validate('required|email|unique:users,email')]
    public $email;

    #[Validate('required')]
    public $jenis_bank;

    #[Validate('required|numeric')]
    public $no_rekening;

    #[Validate('required')]
    public $nama_pemilik_rekening;

    #[Validate('required')]
    public $nama_ahli_waris;

    #[Validate('required')]
    public $hubungan_ahli_waris = '';

    #[Validate('required_if:hubungan_ahli_waris,Lainnya')]
    public $hubungan_lainnya;

    #[Validate('required')]
    public $pendidikan_terakhir = '';

    public function save()
    {
        $this->validate();

        $user = new User();

        $defaultPassword = bcrypt($this->npk . '@1234'); 

        $user->fill([
            'username' => $this->npk,
            'nama_anggota' => $this->nama_lengkap,
            'email' => $this->email,
            'password' => $defaultPassword,
            'gender' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir,
            'ext_tempat_lahir' => $this->tempat_lahir,
            'ext_alamat' => $this->alamat,
            'ext_pendidikan_terakhir' => $this->pendidikan_terakhir,
            'no_telp' => $this->no_whatsapp,
            'level_user' => 1,
            'status_user' => 1,
            'nama_bank' => $this->jenis_bank,
            'no_rekening' => $this->no_rekening,
            'pemilik_no_rekening' => $this->nama_pemilik_rekening,
            'ext_nama_ahli_waris' => $this->nama_ahli_waris,
            'ext_hubungan_ahli_waris' => $this->hubungan_ahli_waris,
            'ext_hubungan_lainnya' => $this->hubungan_lainnya,
            'ext_is_approved' => true,
            'email_verified_at' => now(), // Auto verify jika diinput admin
            'join_date' => now(), // Join date hari ini
        ]);

        $user->save();
        
        $this->js("Flux.toast({ text: 'Anggota berhasil ditambahkan', variant: 'success' })");
        return $this->redirect('/admin/anggota', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/anggota" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Tambah Anggota</flux:heading>
            <flux:text class="mt-2 text-base">Masukkan data anggota baru di bawah ini.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="mt-6">
        <form wire:submit="save" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>NPK</flux:label>
                    <flux:input wire:model="npk" placeholder="Nomor Pokok Karyawan" autofocus />
                    <flux:error name="npk" />
                </flux:field>

                <flux:field>
                    <flux:label>Nama Lengkap</flux:label>
                    <flux:input wire:model="nama_lengkap" placeholder="Masukkan nama lengkap sesuai KTP" />
                    <flux:error name="nama_lengkap" />
                </flux:field>

                <flux:field>
                    <flux:radio.group wire:model="jenis_kelamin" label="Jenis Kelamin">
                        <flux:radio value="L" label="Laki-laki" />
                        <flux:radio value="P" label="Perempuan" />
                    </flux:radio.group>
                    <flux:error name="jenis_kelamin" />
                </flux:field>
               
                <flux:field>
                    <flux:label>Tempat Lahir</flux:label>
                    <flux:input wire:model="tempat_lahir" placeholder="Kota/Kabupaten kelahiran" />
                    <flux:error name="tempat_lahir" />
                </flux:field>

                <flux:field>
                    <flux:label>Tanggal Lahir</flux:label>
                    <flux:input type="date" wire:model="tanggal_lahir" />
                    <flux:error name="tanggal_lahir" />
                </flux:field>

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

                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>Alamat Lengkap</flux:label>
                        <flux:textarea wire:model="alamat" rows="3" placeholder="Masukkan alamat lengkap" />
                        <flux:error name="alamat" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="email@contoh.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>No. WhatsApp / Telepon</flux:label>
                    <flux:input type="tel" wire:model="no_whatsapp" placeholder="08xxxxxxxxxx" />
                    <flux:error name="no_whatsapp" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Jenis Bank</flux:label>
                    <flux:select wire:model="jenis_bank" placeholder="Pilih Bank Tujuan">
                        @foreach($banks as $bank)
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
                    <flux:input type="tel" wire:model="nama_pemilik_rekening" placeholder="Masukan nama pemilik rekening" />
                    <flux:error name="nama_pemilik_rekening" />
                </flux:field>

                <flux:field>
                    <flux:label>Nama Ahli Waris</flux:label>
                    <flux:input wire:model="nama_ahli_waris" placeholder="Nama lengkap ahli waris" />
                    <flux:error name="nama_ahli_waris" />
                </flux:field>

                <flux:field>
                    <flux:label>Hubungan Ahli Waris</flux:label>
                    <flux:select wire:model.live="hubungan_ahli_waris" placeholder="Pilih hubungan">
                        <flux:select.option value="Istri/Suami">Istri / Suami</flux:select.option>
                        <flux:select.option value="Anak">Anak</flux:select.option>
                        <flux:select.option value="Orang Tua">Orang Tua</flux:select.option>
                        <flux:select.option value="Lainnya">Lainnya</flux:select.option>
                    </flux:select>
                    <flux:error name="hubungan_ahli_waris" />
                </flux:field>

                @if($hubungan_ahli_waris === 'Lainnya')
                    <flux:field class="md:col-span-2">
                        <flux:label>Sebutkan Hubungan Ahli Waris</flux:label>
                        <flux:input wire:model="hubungan_lainnya" placeholder="Sebutkan hubungan spesifik (contoh: Saudara Kandung)" />
                        <flux:error name="hubungan_lainnya" />
                    </flux:field>
                @endif
                
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="/admin/anggota" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>

        </form>
    </flux:card>
</div>
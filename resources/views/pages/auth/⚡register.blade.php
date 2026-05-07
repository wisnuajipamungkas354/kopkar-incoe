<?php

use Livewire\Component;
use Livewire\Layout;
use Livewire\Attributes\Validate;
use App\Models\User;

new #[Layout('layouts.app')] class extends Component
{
    public array $banks = ['BCA', 'Mandiri', 'BSI', 'Bank Jago', 'OVO', 'Gopay', 'ShopeePay'];

    #[Validate('required', message: 'NPK harus diisi')]
    #[Validate('min:3', message: 'NPK minimal 3 karakter')]
    #[Validate('unique:users,username', message: 'NPK sudah pernah didaftarkan!')]
    public $npk;

    #[Validate('required', message: 'Nama lengkap wajib diisi')]
    public $nama_lengkap;

    #[Validate('required', message: 'Pilih jenis kelamin')]
    public $jenis_kelamin;

    #[Validate('required', message: 'Tempat lahir wajib diisi')]
    public $tempat_lahir;

    #[Validate('required', message: 'Tanggal lahir wajib diisi')]
    #[Validate('date', message: 'Format tanggal tidak valid')]
    public $tanggal_lahir;

    #[Validate('required', message: 'Alamat domisili wajib diisi')]
    public $alamat;

    #[Validate('required', message: 'Nomor WhatsApp aktif wajib diisi')]
    #[Validate('numeric', message: 'Nomor WA harus berupa angka')]
    #[Validate('min:10', message: 'Nomor WA minimal 10 digit')]
    public $no_whatsapp;

    #[Validate('required', message: 'Email wajib diisi')]
    #[Validate('email', message: 'Format email tidak valid')]
    public $email;

    #[Validate('required', message: 'Pilih jenis bank atau e-wallet')]
    public $jenis_bank;

    #[Validate('required', message: 'Nomor rekening wajib diisi')]
    #[Validate('numeric', message: 'Nomor rekening harus berupa angka')]
    public $no_rekening;

    #[Validate('required', message: 'Nama ahli waris wajib diisi')]
    public $nama_ahli_waris;

    #[Validate('required', message: 'Pilih hubungan ahli waris')]
    public $hubungan_ahli_waris = '';

    // Validasi kondisional: Hanya wajib jika hubungan adalah 'Lainnya'
    #[Validate('required_if:hubungan_ahli_waris,Lainnya', message: 'Sebutkan hubungan ahli waris lainnya')]
    public $hubungan_lainnya;

    #[Validate('required', message: 'Pilih pendidikan terakhir')]
    public $pendidikan_terakhir = '';

    // Validasi persetujuan (wajib dicentang)
    #[Validate('required', message: 'Anda harus menyetujui persyaratan untuk melanjutkan')]
    #[Validate('min:1', message: 'Anda harus menyetujui persyaratan untuk melanjutkan')]
    public $persetujuan = [];

    public function register()
    {
        // Melakukan validasi semua properti yang memiliki atribut #[Validate]
        $validated = $this->validate();

        // Tambahan logika bisnis: memastikan 'setuju' ada dalam array persetujuan
        if (!in_array('setuju', $this->persetujuan)) {
            $this->addError('persetujuan', 'Konfirmasi kesediaan wajib dicentang.');
            return;
        }
        unset($validated['persetujuan']);

        // $defaultPassword = bcrypt($this->npk . '@' . rand(1000, 9999)); 
        $defaultPassword = bcrypt($this->npk . '@1234'); 

        $userData = [
            // Data akun
            'username' => $this->npk,
            'email' => $this->email,
            'password' => $defaultPassword,

            // Data pribadi
            'nama_anggota' => $this->nama_lengkap,
            'gender' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir,
            'ext_tempat_lahir' => $this->tempat_lahir,
            'ext_alamat' => $this->alamat,
            'ext_pendidikan_terakhir' => $this->pendidikan_terakhir,
            'no_telp' => $this->no_whatsapp,

            // Data keanggotaan
            'join_astra' => null,
            'join_date' => null,
            'employement_status' => null,
            'grade_category' => null,
            'seksi' => null,
            'status_user' => 2,
            'level_user' => 1,

            // Data rekening
            'nama_bank' => $this->jenis_bank,
            'no_rekening' => $this->no_rekening,
            'pemilik_no_rekening' => null,

            // Data ahli waris
            'ext_nama_ahli_waris' => $this->nama_ahli_waris,
            'ext_hubungan_ahli_waris' => $this->hubungan_ahli_waris,
            'ext_hubungan_lainnya' => $this->hubungan_lainnya,

            // Status aplikasi tambahan
            'ext_is_approved' => false,

            // Sistem Laravel
            'email_verified_at' => null,
            // Timestamp
            'created_at' => now(),
            'updated_at' => now(),
        ];
        User::create($userData);

        session()->flash('status', 'Pendaftaran berhasil dikirim!');
        
        return $this->redirect('success', navigate: true);
    }
};
?>

<div class="relative w-full max-w-3xl mx-auto mt-10 mb-20">
    <!-- Tombol Toggle Dark/Light Mode -->
    <div class="absolute -top-12 right-0">
        <flux:button variant="subtle" size="sm" x-data x-on:click="$flux.dark = ! $flux.dark" class="rounded-full !px-2">
            <flux:icon.sun x-show="$flux.appearance === 'light'" variant="mini" class="text-zinc-500 dark:text-white" />
            <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="mini" class="text-zinc-500 dark:text-white" />
        </flux:button>
    </div>

    <!-- Area Logo Dinamis -->
    <div class="flex justify-center mb-6 mt-4">
        <img x-show="$flux.appearance === 'light'" src="{{ asset('img/kki-icon-2-light.png') }}" alt="Logo KKI" class="h-20 w-auto">
        <img x-show="$flux.appearance === 'dark'" src="{{ asset('img/kki-icon-2-dark.png') }}" alt="Logo KKI Dark" class="h-20 w-auto">
    </div>

    <flux:card>
        <form wire:submit="register" class="space-y-6">
            
            <!-- Heading -->
            <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700 pb-4">
                <flux:heading size="xl">Pendaftaran Anggota Baru</flux:heading>
                <flux:subheading>Silakan lengkapi formulir di bawah ini untuk bergabung dengan KKI.</flux:subheading>
            </div>

            <!-- Grid 2 Kolom untuk Input Data -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>NPK</flux:label>
                    <flux:input wire:model.live.blur="npk" placeholder="Nomor Pokok Karyawan" autofocus />
                    <flux:error name="npk" />
                </flux:field>

                <flux:field>
                    <flux:label>Nama Lengkap</flux:label>
                    <flux:input wire:model="nama_lengkap" placeholder="Masukkan nama lengkap sesuai KTP" />
                    <flux:error name="nama_lengkap" />
                </flux:field>

                <flux:field>
                    <flux:radio.group wire:model="jenis_kelamin" label="Jenis Kelamin">
                        <flux:radio value="L" label="Laki-laki" checked />
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
                    <flux:select wire:model.live="pendidikan_terakhir" placeholder="Pilih Pendidikan">
                        <flux:select.option value="SMP">SMP</flux:select.option>
                        <flux:select.option value="SMA/K">SMA/K</flux:select.option>
                        <flux:select.option value="S1">S1/D4</flux:select.option>
                        <flux:select.option value="S2">S2</flux:select.option>
                        <flux:select.option value="S3">S3</flux:select.option>
                    </flux:select>
                    <flux:error name="pendidikan_terakhir" />
                </flux:field>

                <!-- Field Alamat memakan 2 kolom penuh di layar besar -->
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
                    <flux:label>No. WhatsApp</flux:label>
                    <flux:input type="tel" wire:model="no_whatsapp" placeholder="08xxxxxxxxxx" />
                    <flux:error name="no_whatsapp" />
                </flux:field>
                
                <flux:field>
                    <livewire:searchable-select 
                        wire:model="jenis_bank" 
                        :options="$banks" 
                        label="Pilih Bank Tujuan" 
                        placeholder="Cari bank (misal: BCA)..." 
                    />
                    <flux:error name="jenis_bank" />
                </flux:field>
                <flux:field>
                    <flux:label>Nomor Rekening</flux:label>
                    <flux:input wire:model="no_rekening" placeholder="Contoh: 1234567890" />
                    <flux:error name="no_rekening" />
                </flux:field>

                <flux:field>
                    <flux:label>Nama Ahli Waris</flux:label>
                    <flux:input wire:model="nama_ahli_waris" placeholder="Nama lengkap ahli waris" />
                    <flux:error name="nama_ahli_waris" />
                </flux:field>

                <!-- Menggunakan wire:model.live agar reaktif saat memilih 'Lainnya' -->
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

                <!-- Input tambahan jika memilih "Lainnya" -->
                @if($hubungan_ahli_waris === 'Lainnya')
                    <flux:field class="md:col-span-2">
                        <flux:label>Sebutkan Hubungan Ahli Waris</flux:label>
                        <flux:input wire:model="hubungan_lainnya" placeholder="Sebutkan hubungan spesifik (contoh: Saudara Kandung)" />
                        <flux:error name="hubungan_lainnya" />
                    </flux:field>
                @endif
                
            </div>

            <!-- Bagian Persetujuan -->
            <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <flux:field>
                    <flux:checkbox.group wire:model.live="persetujuan" label="Persetujuan" variant="cards" class="flex-col">
                        <flux:checkbox checked value="setuju">
                            <flux:checkbox.indicator />
                            <div class="flex-1">
                                <flux:heading class="leading-4">Bersedia</flux:heading>
                                <flux:text size="sm" class="mt-2">Bersedia membayar Simpanan Pokok (SIMPOK) sebesar <b>Rp50.000,-/bulan</b> dan Simpanan Wajib (SIWA) sebesar <b>Rp150.000,-/bulan</b> serta bersedia memenuhi segala peraturan yang berlaku di Koperasi Konsumen Incoe (KKI).</flux:text>
                            </div>
                        </flux:checkbox>
                    </flux:checkbox.group>
                </flux:field>
            </div>

            <!-- Catatan Informasi Tambahan -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mt-4">
                <flux:heading size="sm" class="text-blue-800 dark:text-blue-400 mb-2">Catatan Penting:</flux:heading>
                <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>Simpanan Pokok dapat disetor langsung ke Koperasi Karyawan Incoe (KKI).</li>
                    <li>Semua data wajib diisi dengan lengkap dan benar untuk memudahkan akses transaksi.</li>
                </ul>
            </div>

            <!-- Tombol Submit -->
            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="{{ url('login') }}" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary" :disabled="!in_array('setuju', $persetujuan)">Kirim Pendaftaran</flux:button>
            </div>

        </form>
    </flux:card>
</div>
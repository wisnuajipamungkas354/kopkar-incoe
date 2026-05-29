<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\KoperasiMember;
use App\Models\User;
use App\Models\NamaBank;

new #[Layout('layouts::admin')] class extends Component
{
    public KoperasiMember $member;

    public $npk = '';
    public $nama_lengkap = '';
    public $email = '';
    public $join_date = '';
    public $join_koperasi_astra = '';
    public $jenis_bank = '';
    public $no_rekening = '';
    public $nama_pemilik_rekening = '';
    public $nama_ahli_waris = '';
    public $hubungan_ahli_waris = '';
    public $hubungan_lainnya = '';
    public $status = 'active';
    public $password = '';

    public function mount($id)
    {
        $this->member = KoperasiMember::with(['employee', 'employee.user'])->findOrFail($id);

        $this->npk = $this->member->employee->npk ?? '';
        $this->nama_lengkap = $this->member->employee->nama_lengkap ?? '';
        $this->email = $this->member->employee->user?->email ?? '';
        $this->join_date = $this->member->join_date ? $this->member->join_date->format('Y-m-d') : '';
        $this->join_koperasi_astra = $this->member->join_koperasi_astra ? $this->member->join_koperasi_astra->format('Y-m-d') : '';
        $this->jenis_bank = $this->member->employee->nama_bank ?? '';
        $this->no_rekening = $this->member->employee->no_rekening ?? '';
        $this->nama_pemilik_rekening = $this->member->employee->nama_pemilik_rekening ?? '';
        $this->nama_ahli_waris = $this->member->nama_ahli_waris;
        $this->hubungan_ahli_waris = $this->member->hubungan_ahli_waris;
        $this->hubungan_lainnya = $this->member->hubungan_lainnya;
        $this->status = $this->member->status;
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

    protected function rules()
    {
        $userId = $this->member->employee->user?->id ?? '';

        return [
            'email' => 'required|email|unique:users,email,' . $userId,
            'join_date' => 'required|date',
            'join_koperasi_astra' => 'nullable|date',
            'jenis_bank' => 'required',
            'no_rekening' => 'required|numeric',
            'nama_pemilik_rekening' => 'required',
            'nama_ahli_waris' => 'required',
            'hubungan_ahli_waris' => 'required',
            'hubungan_lainnya' => 'required_if:hubungan_ahli_waris,Lainnya',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|min:6',
        ];
    }

    public function save()
    {
        $this->validate();

        \DB::transaction(function () {
            // 1. Update Employee record with bank details
            $this->member->employee->update([
                'no_rekening' => $this->no_rekening,
                'nama_bank' => $this->jenis_bank,
                'nama_pemilik_rekening' => $this->nama_pemilik_rekening,
            ]);

            // 2. Update Koperasi Member record
            $this->member->update([
                'join_koperasi_astra' => $this->join_koperasi_astra ?: null,
                'join_date' => $this->join_date,
                'status' => $this->status,
                'nama_ahli_waris' => $this->nama_ahli_waris,
                'hubungan_ahli_waris' => $this->hubungan_ahli_waris,
                'hubungan_lainnya' => $this->hubungan_lainnya,
            ]);

            // 2. Update polymorphic User record if exists, or create if missing
            $user = $this->member->employee->user;
            $updateData = [
                'email' => $this->email,
            ];

            if ($this->password) {
                $updateData['password'] = bcrypt($this->password);
            }

            if ($user) {
                $user->update($updateData);
            } else {
                $defaultPassword = $this->password ? bcrypt($this->password) : bcrypt($this->npk . '@1234');
                User::create([
                    'userable_id' => $this->member->employee->id,
                    'userable_type' => \App\Models\Employee::class,
                    'username' => $this->npk,
                    'email' => $this->email,
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                ]);
            }
        });

        $this->js("Flux.toast({ text: 'Data anggota berhasil diperbarui', variant: 'success' })");
        return $this->redirect('/admin/anggota', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/anggota" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Edit Anggota Koperasi</flux:heading>
            <flux:text class="mt-2 text-base">Ubah informasi rekening, keanggotaan, atau reset password login anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="mt-6">
        <form wire:submit="save" class="space-y-6">
            
            <!-- SECTION 1: PROFIL KARYAWAN (READ ONLY) -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">Data Karyawan</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-2xl text-sm">
                    <div>
                        <span class="text-zinc-400 block text-xs">NPK</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $npk }}</span>
                    </div>
                    <div>
                        <span class="text-zinc-400 block text-xs">Nama Lengkap</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $nama_lengkap }}</span>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: KEANGGOTAAN & REKENING -->
            <div>
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">Informasi Akun & Keanggotaan</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label>Email Akun (Untuk Login & Notifikasi)</flux:label>
                        <flux:input type="email" wire:model="email" required />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Status Keanggotaan</flux:label>
                        <flux:select wire:model="status" placeholder="Pilih Status...">
                            <flux:select.option value="active">Aktif</flux:select.option>
                            <flux:select.option value="inactive">Nonaktif</flux:select.option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Tanggal Mulai Gabung Koperasi</flux:label>
                        <flux:input type="date" wire:model="join_date" required />
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
                        <flux:input wire:model="no_rekening" required />
                        <flux:error name="no_rekening" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nama Pemilik Rekening</flux:label>
                        <flux:input wire:model="nama_pemilik_rekening" required />
                        <flux:error name="nama_pemilik_rekening" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Nama Ahli Waris</flux:label>
                        <flux:input wire:model="nama_ahli_waris" required />
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

            <!-- SECTION 3: RESET PASSWORD -->
            <div class="mt-8 border-t border-zinc-200 dark:border-zinc-700 pt-6">
                <flux:heading size="lg" class="mb-2">Reset Password</flux:heading>
                <flux:text class="text-sm text-zinc-500 mb-4">Isi bidang di bawah ini jika Anda ingin mengubah password login anggota. Biarkan kosong jika tidak ingin mengubahnya.</flux:text>
                
                <flux:field class="max-w-md">
                    <flux:label>Password Baru</flux:label>
                    <flux:input type="password" wire:model="password" placeholder="Masukkan password baru..." />
                    <flux:error name="password" />
                </flux:field>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="/admin/anggota" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>

        </form>
    </flux:card>
</div>
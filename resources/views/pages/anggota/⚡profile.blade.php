<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new #[Layout('layouts::anggota')] class extends Component
{
    public $email;
    public $seksi;
    public $no_telp;
    public $alamat;
    public $no_rekening;
    public $nama_bank;
    public $nama_pemilik_rekening;
    public $nama_ahli_waris;
    public $hubungan_ahli_waris;
    public $hubungan_lainnya;
    public $password;
    public $password_confirmation;

    public function mount()
    {
        $user = auth('web')->user();
        $this->email = $user->email;
        
        $employee = $user->userable;
        if ($employee) {
            $this->seksi = $employee->seksi;
            $this->no_telp = $employee->no_telp;
            $this->alamat = $employee->alamat;
            
            $member = $employee->koperasiMember;
            if ($member) {
                $this->no_rekening = $member->no_rekening;
                $this->nama_bank = $member->nama_bank;
                $this->nama_pemilik_rekening = $member->nama_pemilik_rekening;
                $this->nama_ahli_waris = $member->nama_ahli_waris;
                $this->hubungan_ahli_waris = $member->hubungan_ahli_waris;
                $this->hubungan_lainnya = $member->hubungan_lainnya;
            }
        }
    }

    public function save()
    {
        $user = auth('web')->user();

        $validated = $this->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'seksi' => ['required', 'string', 'in:Produksi,Warehouse,QC,HR,IT,Finance'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'nama_bank' => ['nullable', 'string', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:50'],
            'nama_pemilik_rekening' => ['nullable', 'string', 'max:150'],
            'nama_ahli_waris' => ['nullable', 'string', 'max:150'],
            'hubungan_ahli_waris' => ['nullable', 'string', 'in:suami,istri,anak,orang_tua,saudara,lainnya'],
            'hubungan_lainnya' => ['required_if:hubungan_ahli_waris,lainnya', 'nullable', 'string', 'max:100'],
            'password' => ['nullable', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'seksi.required' => 'Seksi wajib diisi.',
            'seksi.in' => 'Seksi yang dipilih tidak valid.',
            'no_telp.max' => 'Nomor telepon maksimal 20 karakter.',
            'alamat.max' => 'Alamat maksimal 500 karakter.',
            'nama_bank.max' => 'Nama bank maksimal 100 karakter.',
            'no_rekening.max' => 'Nomor rekening maksimal 50 karakter.',
            'nama_pemilik_rekening.max' => 'Nama pemilik rekening maksimal 150 karakter.',
            'nama_ahli_waris.max' => 'Nama ahli waris maksimal 150 karakter.',
            'hubungan_ahli_waris.in' => 'Hubungan ahli waris tidak valid.',
            'hubungan_lainnya.required_if' => 'Hubungan lainnya wajib diisi jika memilih Lainnya.',
            'hubungan_lainnya.max' => 'Detail hubungan lainnya maksimal 100 karakter.',
            'password.min' => 'Password minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        // Update user
        $user->email = $this->email;
        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }
        $user->save();

        // Update employee
        $employee = $user->userable;
        if ($employee) {
            $employee->seksi = $this->seksi;
            $employee->no_telp = $this->no_telp;
            $employee->alamat = $this->alamat;
            $employee->save();

            // Update member
            $member = $employee->koperasiMember;
            if ($member) {
                $member->no_rekening = $this->no_rekening;
                $member->nama_bank = $this->nama_bank;
                $member->nama_pemilik_rekening = $this->nama_pemilik_rekening;
                $member->nama_ahli_waris = $this->nama_ahli_waris;
                $member->hubungan_ahli_waris = $this->hubungan_ahli_waris;
                $member->hubungan_lainnya = ($this->hubungan_ahli_waris === 'lainnya') ? $this->hubungan_lainnya : null;
                $member->save();
            }
        }

        $this->refresh();
        $this->reset(['password', 'password_confirmation']);

        session()->flash('status', 'Profil Anda berhasil diperbarui.');
    }
};
?>

<div x-data="{ activeTab: 'detail' }" class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl" level="1">Profil Saya</flux:heading>
            <flux:subheading class="mt-1">Kelola informasi pribadi dan keamanan akun Anda.</flux:subheading>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    @if (session('status'))
        <div class="bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 p-4 rounded-lg text-sm flex items-center gap-2">
            <flux:icon name="check-circle" variant="solid" class="w-5 h-5" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Ringkasan Pengguna -->
        <div class="lg:col-span-1">
            <flux:card class="flex flex-col items-center text-center p-6 space-y-4">
                <div class="relative">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center text-white text-3xl font-bold shadow-md">
                        {{ substr(auth()->user()->userable->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div class="absolute bottom-0 right-0 w-6 h-6 bg-green-500 border-2 border-white dark:border-zinc-800 rounded-full"></div>
                </div>
                <div>
                    <flux:heading size="lg">{{ auth()->user()->userable->nama_lengkap ?? 'Anggota Koperasi' }}</flux:heading>
                    <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ auth()->user()->username }}</flux:text>
                </div>
                
                <flux:separator variant="subtle" />

                <div class="w-full text-left space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">NPK</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->userable->npk ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">No. Anggota</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->userable->koperasiMember->member_number ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Seksi</span>
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->userable->seksi ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Status</span>
                        @php
                            $status = auth()->user()->userable->koperasiMember->status ?? 'pending';
                            $color = match($status) {
                                'active' => 'emerald',
                                'pending' => 'amber',
                                default => 'red'
                            };
                        @endphp
                        <flux:badge color="{{ $color }}" size="sm">{{ ucfirst($status) }}</flux:badge>
                    </div>
                    <div>
                        <flux:button size="sm" variant="primary" href="{{ url('/logout') }}" icon="arrow-right-start-on-rectangle" class="w-full">Logout</flux:button>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Kolom Kanan: Detail & Form Edit -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Header Tab -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-700">
                <button @click="activeTab = 'detail'" 
                        :class="activeTab === 'detail' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-semibold' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                        class="py-3 px-6 border-b-2 text-sm focus:outline-none transition-all flex items-center gap-2">
                    <flux:icon name="user" variant="outline" class="w-4 h-4" />
                    <span>Informasi Pribadi</span>
                </button>
                <button @click="activeTab = 'edit'" 
                        :class="activeTab === 'edit' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400 font-semibold' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                        class="py-3 px-6 border-b-2 text-sm focus:outline-none transition-all flex items-center gap-2">
                    <flux:icon name="pencil-square" variant="outline" class="w-4 h-4" />
                    <span>Ubah Profil</span>
                </button>
            </div>

            <!-- Tab Content: Detail -->
            <div x-show="activeTab === 'detail'" x-transition class="space-y-6">
                <!-- Seksi 1: Data Karyawan -->
                <flux:card class="space-y-4">
                    <flux:heading size="md">Data Karyawan</flux:heading>
                    <flux:separator variant="subtle" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">NPK</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->npk ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nama Lengkap</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->nama_lengkap ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Seksi</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->seksi ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Jenis Kelamin</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ (auth()->user()->userable->jk ?? '') === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Tempat, Tanggal Lahir</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->tempat_lahir ?? '-' }}, {{ auth()->user()->userable->tanggal_lahir?->format('d M Y') ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">No. Telepon</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->no_telp ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Pendidikan Terakhir</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->pendidikan_terakhir ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Grade Karyawan</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->grade_category ?? '-' }}</span>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Alamat Tinggal</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 whitespace-pre-line">{{ auth()->user()->userable->alamat ?? '-' }}</span>
                        </div>
                    </div>
                </flux:card>

                <!-- Seksi 2: Data Keanggotaan Koperasi -->
                @if (auth()->user()->userable->koperasiMember)
                    <flux:card class="space-y-4">
                        <flux:heading size="md">Keanggotaan Koperasi</flux:heading>
                        <flux:separator variant="subtle" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nomor Anggota</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->koperasiMember->member_number ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Tanggal Bergabung</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->koperasiMember->join_date?->format('d M Y') ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nama Bank</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->koperasiMember->nama_bank ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nomor Rekening</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->koperasiMember->no_rekening ?? '-' }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nama Pemilik Rekening</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->koperasiMember->nama_pemilik_rekening ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nama Ahli Waris</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">{{ auth()->user()->userable->koperasiMember->nama_ahli_waris ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Hubungan Ahli Waris</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    {{ ucfirst(str_replace('_', ' ', auth()->user()->userable->koperasiMember->hubungan_ahli_waris ?? '-')) }}
                                    @if ((auth()->user()->userable->koperasiMember->hubungan_ahli_waris ?? '') === 'lainnya')
                                        ({{ auth()->user()->userable->koperasiMember->hubungan_lainnya }})
                                    @endif
                                </span>
                            </div>
                        </div>
                    </flux:card>
                @endif
            </div>

            <!-- Tab Content: Edit Form -->
            <div x-show="activeTab === 'edit'" x-transition>
                <flux:card>
                    <form wire:submit="save" class="space-y-6">
                        <flux:heading size="md">Ubah Informasi Akun</flux:heading>
                        <flux:subheading>Perbarui informasi profil dan rekening koperasi Anda di bawah ini.</flux:subheading>
                        <flux:separator variant="subtle" />

                        <!-- Group 1: Keamanan & Akun -->
                        <div class="space-y-4">
                            <flux:heading size="sm" level="2" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs">Informasi Akun</flux:heading>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Alamat Email</flux:label>
                                    <flux:input type="email" wire:model="email" icon="envelope" required />
                                    <flux:error name="email" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Seksi</flux:label>
                                    <flux:select wire:model="seksi" placeholder="Pilih seksi...">
                                        <flux:select.option value="Produksi">Produksi</flux:select.option>
                                        <flux:select.option value="Warehouse">Warehouse</flux:select.option>
                                        <flux:select.option value="QC">QC</flux:select.option>
                                        <flux:select.option value="HR">HR</flux:select.option>
                                        <flux:select.option value="IT">IT</flux:select.option>
                                        <flux:select.option value="Finance">Finance</flux:select.option>
                                    </flux:select>
                                    <flux:error name="seksi" />
                                </flux:field>
                            </div>
                        </div>

                        <flux:separator variant="subtle" />

                        <!-- Group 2: Kontak & Alamat -->
                        <div class="space-y-4">
                            <flux:heading size="sm" level="2" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs">Kontak & Alamat</flux:heading>
                            <flux:field>
                                <flux:label>No. Telepon</flux:label>
                                <flux:input type="text" wire:model="no_telp" icon="phone" />
                                <flux:error name="no_telp" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Alamat Tinggal</flux:label>
                                <flux:textarea wire:model="alamat" rows="3" placeholder="Masukkan alamat lengkap Anda..." />
                                <flux:error name="alamat" />
                            </flux:field>
                        </div>

                        <flux:separator variant="subtle" />

                        <!-- Group 3: Rekening Bank -->
                        @if (auth()->user()->userable->koperasiMember)
                            <div class="space-y-4">
                                <flux:heading size="sm" level="2" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs">Rekening Bank Koperasi</flux:heading>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <flux:field>
                                        <flux:label>Nama Bank</flux:label>
                                        <flux:input type="text" wire:model="nama_bank" placeholder="Contoh: BCA, Mandiri" />
                                        <flux:error name="nama_bank" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Nomor Rekening</flux:label>
                                        <flux:input type="text" wire:model="no_rekening" placeholder="Masukkan nomor rekening" />
                                        <flux:error name="no_rekening" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Nama Pemilik Rekening</flux:label>
                                        <flux:input type="text" wire:model="nama_pemilik_rekening" placeholder="Sesuai buku tabungan" />
                                        <flux:error name="nama_pemilik_rekening" />
                                    </flux:field>
                                </div>
                            </div>

                            <flux:separator variant="subtle" />

                            <!-- Group 4: Ahli Waris -->
                            <div class="space-y-4">
                                <flux:heading size="sm" level="2" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs">Informasi Ahli Waris</flux:heading>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Nama Ahli Waris</flux:label>
                                        <flux:input type="text" wire:model="nama_ahli_waris" placeholder="Nama lengkap ahli waris" />
                                        <flux:error name="nama_ahli_waris" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Hubungan Ahli Waris</flux:label>
                                        <flux:select wire:model.live="hubungan_ahli_waris" placeholder="Pilih hubungan...">
                                            <flux:select.option value="suami_istri">Suami/Istri</flux:select.option>
                                            <flux:select.option value="anak">Anak</flux:select.option>
                                            <flux:select.option value="orang_tua">Orang Tua</flux:select.option>
                                            <flux:select.option value="lainnya">Lainnya</flux:select.option>
                                        </flux:select>
                                        <flux:error name="hubungan_ahli_waris" />
                                    </flux:field>
                                </div>

                                <div x-show="$wire.hubungan_ahli_waris === 'lainnya'" x-transition class="mt-4">
                                    <flux:field>
                                        <flux:label>Detail Hubungan Lainnya</flux:label>
                                        <flux:input type="text" wire:model="hubungan_lainnya" placeholder="Sebutkan detail hubungan (misal: paman, keponakan)..." />
                                        <flux:error name="hubungan_lainnya" />
                                    </flux:field>
                                </div>
                            </div>

                            <flux:separator variant="subtle" />
                        @endif

                        <!-- Group 5: Ubah Password -->
                        <div class="space-y-4">
                            <div>
                                <flux:heading size="sm" level="2" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs">Ubah Password (Opsional)</flux:heading>
                                <flux:text class="text-xs text-zinc-400 mt-1 block">Biarkan kosong jika Anda tidak ingin mengubah password.</flux:text>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Password Baru</flux:label>
                                    <flux:input type="password" wire:model="password" placeholder="Minimal 8 karakter" icon="key" />
                                    <flux:error name="password" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Konfirmasi Password Baru</flux:label>
                                    <flux:input type="password" wire:model="password_confirmation" placeholder="Ulangi password baru" icon="key" />
                                </flux:field>
                            </div>
                        </div>

                        <flux:separator variant="subtle" class="mt-8" />

                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3 mt-4">
                            <flux:button type="submit" variant="primary" class="px-6">
                                Simpan Perubahan
                            </flux:button>
                        </div>
                    </form>
                </flux:card>
            </div>
        </div>
    </div>
</div>
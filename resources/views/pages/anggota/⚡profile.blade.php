<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
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

    public $activeTab = 'info';

    public function mount()
    {
        $user     = auth('web')->user();
        $employee = $user->userable;
        $member   = $employee?->koperasiMember;

        $this->email = $user->email;

        if ($employee) {
            $this->seksi   = $employee->seksi;
            $this->no_telp = $employee->no_telp;
            $this->alamat  = $employee->alamat;
        }

        if ($member) {
            $this->no_rekening           = $member->no_rekening;
            $this->nama_bank             = $member->nama_bank;
            $this->nama_pemilik_rekening = $member->nama_pemilik_rekening;
            $this->nama_ahli_waris       = $member->nama_ahli_waris;
            $this->hubungan_ahli_waris   = $member->hubungan_ahli_waris;
            $this->hubungan_lainnya      = $member->hubungan_lainnya;
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function save()
    {
        $user = auth('web')->user();

        $this->validate([
            'email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'seksi'                 => ['required', 'string', 'in:Produksi,Warehouse,QC,HR,IT,Finance'],
            'no_telp'               => ['nullable', 'string', 'max:20'],
            'alamat'                => ['nullable', 'string', 'max:500'],
            'nama_bank'             => ['nullable', 'string', 'max:100'],
            'no_rekening'           => ['nullable', 'string', 'max:50'],
            'nama_pemilik_rekening' => ['nullable', 'string', 'max:150'],
            'nama_ahli_waris'       => ['nullable', 'string', 'max:150'],
            'hubungan_ahli_waris'   => ['nullable', 'string', 'in:suami_istri,anak,orang_tua,saudara,lainnya'],
            'hubungan_lainnya'      => ['required_if:hubungan_ahli_waris,lainnya', 'nullable', 'string', 'max:100'],
            'password'              => ['nullable', 'min:8', 'confirmed'],
        ], [
            'email.required'               => 'Email wajib diisi.',
            'email.email'                  => 'Format email tidak valid.',
            'email.unique'                 => 'Email sudah digunakan.',
            'seksi.required'               => 'Seksi wajib diisi.',
            'hubungan_ahli_waris.in'       => 'Hubungan ahli waris tidak valid.',
            'hubungan_lainnya.required_if' => 'Sebutkan detail hubungan.',
            'password.min'                 => 'Password minimal 8 karakter.',
            'password.confirmed'           => 'Konfirmasi password tidak cocok.',
        ]);

        $user->email = $this->email;
        if (!empty($this->password)) {
            $user->password = Hash::make($this->password);
        }
        $user->save();

        $employee = $user->userable;
        if ($employee) {
            $employee->seksi   = $this->seksi;
            $employee->no_telp = $this->no_telp;
            $employee->alamat  = $this->alamat;
            $employee->save();

            $member = $employee->koperasiMember;
            if ($member) {
                $member->no_rekening           = $this->no_rekening;
                $member->nama_bank             = $this->nama_bank;
                $member->nama_pemilik_rekening = $this->nama_pemilik_rekening;
                $member->nama_ahli_waris       = $this->nama_ahli_waris;
                $member->hubungan_ahli_waris   = $this->hubungan_ahli_waris;
                $member->hubungan_lainnya      = ($this->hubungan_ahli_waris === 'lainnya') ? $this->hubungan_lainnya : null;
                $member->save();
            }
        }

        $this->refresh();
        $this->reset(['password', 'password_confirmation']);
        session()->flash('status', 'Profil berhasil diperbarui.');
    }
};
?>

@php
    $user     = auth()->user();
    $employee = $user->userable;
    $member   = $employee?->koperasiMember;

    $hubLabels = [
        'suami_istri' => 'Suami/Istri',
        'anak'        => 'Anak',
        'orang_tua'   => 'Orang Tua',
        'saudara'     => 'Saudara',
        'lainnya'     => 'Lainnya',
    ];
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl" level="1">Profil Saya</flux:heading>
            <flux:subheading class="mt-1">Kelola informasi pribadi dan keamanan akun Anda.</flux:subheading>
        </div>
    </div>

    <flux:separator variant="subtle" />

    {{-- Status Alert --}}
    @if (session('status'))
        <div class="bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-400 p-4 rounded-lg text-sm flex items-center gap-2">
            <flux:icon name="check-circle" variant="solid" class="w-5 h-5" />
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="flex border-b border-zinc-200 dark:border-zinc-700 gap-1">
        @php
            $tabs = [
                'info' => ['label' => 'Informasi Pribadi', 'icon' => 'user'],
                'edit' => ['label' => 'Ubah Profil',       'icon' => 'pencil-square'],
            ];
        @endphp
        @foreach($tabs as $key => $tab)
            <button wire:click="switchTab('{{ $key }}')"
                    class="pb-3 px-1 mr-4 text-sm font-semibold border-b-2 transition-all flex items-center gap-2
                           {{ $activeTab === $key
                              ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                              : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}">
                <flux:icon name="{{ $tab['icon'] }}" variant="outline" class="w-4 h-4" />
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════
         TAB: INFORMASI PRIBADI
    ═══════════════════════════════════ --}}
    @if($activeTab === 'info')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Kolom Kiri: Ringkasan --}}
            <div class="lg:col-span-1">
                <flux:card class="flex flex-col items-center text-center p-6 space-y-4">
                    <div class="relative">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-bold shadow-md">
                            {{ strtoupper(substr($employee?->nama_lengkap ?? 'A', 0, 1)) }}
                        </div>
                        @if($member?->status === 'active')
                            <span class="absolute bottom-0 right-0 w-5 h-5 bg-green-500 border-2 border-white dark:border-zinc-800 rounded-full"></span>
                        @endif
                    </div>
                    <div>
                        <flux:heading size="lg">{{ $employee?->nama_lengkap ?? 'Anggota Koperasi' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->username }}</flux:text>
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="w-full text-left space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">NPK</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $employee?->npk ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">No. Anggota</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200 font-mono">{{ $member?->member_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Seksi</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $employee?->seksi ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-zinc-500 dark:text-zinc-400">Status</span>
                            @php
                                $statusColor = match($member?->status ?? 'pending') {
                                    'active'  => 'emerald',
                                    'pending' => 'amber',
                                    default   => 'red'
                                };
                            @endphp
                            <flux:badge color="{{ $statusColor }}" size="sm">{{ ucfirst($member?->status ?? 'pending') }}</flux:badge>
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <flux:button size="sm" variant="ghost" href="{{ url('/logout') }}" icon="arrow-right-start-on-rectangle" class="w-full text-zinc-500">
                        Logout
                    </flux:button>
                </flux:card>
            </div>

            {{-- Kolom Kanan: Detail --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Data Karyawan --}}
                <flux:card class="space-y-4">
                    <flux:heading size="md">Data Karyawan</flux:heading>
                    <flux:separator variant="subtle" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        @foreach([
                            ['NPK',                $employee?->npk],
                            ['Nama Lengkap',        $employee?->nama_lengkap],
                            ['Jenis Kelamin',       ($employee?->jk ?? '') === 'L' ? 'Laki-laki' : (($employee?->jk ?? '') === 'P' ? 'Perempuan' : '-')],
                            ['Pendidikan Terakhir', $employee?->pendidikan_terakhir],
                            ['Grade Karyawan',      $employee?->grade_category],
                            ['Tempat, Tgl Lahir',   ($employee?->tempat_lahir ?? '-') . ', ' . ($employee?->tanggal_lahir?->format('d M Y') ?? '-')],
                        ] as [$label, $value])
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">{{ $label }}</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    {{ $value ?: '-' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </flux:card>

                {{-- Kontak & Alamat --}}
                <flux:card class="space-y-4">
                    <flux:heading size="md">Kontak & Alamat</flux:heading>
                    <flux:separator variant="subtle" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Email</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                {{ $user->email ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">No. Telepon</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                {{ $employee?->no_telp ?: '-' }}
                            </span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Alamat Tinggal</span>
                            <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 whitespace-pre-line min-h-[48px]">
                                {{ $employee?->alamat ?: '-' }}
                            </span>
                        </div>
                    </div>
                </flux:card>

                {{-- Keanggotaan Koperasi --}}
                @if($member)
                    <flux:card class="space-y-4">
                        <flux:heading size="md">Keanggotaan Koperasi</flux:heading>
                        <flux:separator variant="subtle" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nomor Anggota</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 font-mono">
                                    {{ $member->member_number ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Tanggal Bergabung</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    {{ $member->join_date?->format('d M Y') ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nama Bank</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    {{ $member->nama_bank ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nomor Rekening</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700 font-mono">
                                    {{ $member->no_rekening ?? '-' }}
                                </span>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nama Pemilik Rekening</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    {{ $member->nama_pemilik_rekening ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Nama Ahli Waris</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    {{ $member->nama_ahli_waris ?? '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-500 dark:text-zinc-400 block mb-1">Hubungan Ahli Waris</span>
                                <span class="font-medium text-zinc-800 dark:text-zinc-200 block bg-zinc-50 dark:bg-zinc-900 px-3 py-2 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                    {{ $hubLabels[$member->hubungan_ahli_waris ?? ''] ?? ucfirst(str_replace('_', ' ', $member->hubungan_ahli_waris ?? '-')) }}
                                    @if($member->hubungan_ahli_waris === 'lainnya' && $member->hubungan_lainnya)
                                        ({{ $member->hubungan_lainnya }})
                                    @endif
                                </span>
                            </div>
                        </div>
                    </flux:card>
                @endif

            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════
         TAB: UBAH PROFIL
    ═══════════════════════════════════ --}}
    @if($activeTab === 'edit')
        <flux:card>
            <form wire:submit="save" class="space-y-6">
                {{-- Informasi Akun --}}
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
                                @foreach(['Produksi','Warehouse','QC','HR','IT','Finance'] as $s)
                                    <flux:select.option value="{{ $s }}">{{ $s }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="seksi" />
                        </flux:field>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                {{-- Kontak & Alamat --}}
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

                @if($member)
                    <flux:separator variant="subtle" />

                    {{-- Rekening Bank --}}
                    <div class="space-y-4">
                        <flux:heading size="sm" level="2" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs">Rekening Bank Koperasi</flux:heading>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:field>
                                <flux:label>Nama Bank</flux:label>
                                <flux:input type="text" wire:model="nama_bank" placeholder="BCA, Mandiri..." />
                                <flux:error name="nama_bank" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Nomor Rekening</flux:label>
                                <flux:input type="text" wire:model="no_rekening" placeholder="Nomor rekening" />
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

                    {{-- Ahli Waris --}}
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
                                    <flux:select.option value="saudara">Saudara</flux:select.option>
                                    <flux:select.option value="lainnya">Lainnya</flux:select.option>
                                </flux:select>
                                <flux:error name="hubungan_ahli_waris" />
                            </flux:field>
                        </div>
                        @if($hubungan_ahli_waris === 'lainnya')
                            <flux:field>
                                <flux:label>Detail Hubungan Lainnya</flux:label>
                                <flux:input type="text" wire:model="hubungan_lainnya" placeholder="Contoh: paman, keponakan..." />
                                <flux:error name="hubungan_lainnya" />
                            </flux:field>
                        @endif
                    </div>
                @endif

                <flux:separator variant="subtle" />

                {{-- Ubah Password --}}
                <div class="space-y-4">
                    <div>
                        <flux:heading size="sm" level="2" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wider text-xs">Ubah Password (Opsional)</flux:heading>
                        <flux:text class="text-xs text-zinc-400 mt-1 block">Biarkan kosong jika tidak ingin mengubah password.</flux:text>
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

                <flux:separator variant="subtle" />

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="switchTab('info')">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
                </div>
            </form>
        </flux:card>
    @endif
</div>
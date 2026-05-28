<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\KoperasiMember;
use App\Models\MutasiSaldoMember;
use App\Models\PengajuanUtama;

new #[Layout('layouts::admin')] class extends Component
{
    public KoperasiMember $member;
    public $simpananPokok = 0;
    public $simpananWajib = 0;
    public $simpananSukarela = 0;
    public $totalSimpanan = 0;
    public $pinjamanAktif = [];

    public function mount($id)
    {
        $this->member = KoperasiMember::with(['employee', 'employee.user'])->findOrFail($id);
        
        $this->calculateSimpanan();
        $this->fetchPinjaman();
    }
    
    private function calculateSimpanan()
    {
        $employeeId = $this->member->employee_id;

        if (!$employeeId) {
            return;
        }

        // Hitung Saldo Pokok
        $latestPokok = MutasiSaldoMember::where('employee_id', $employeeId)
            ->where('jenis_saldo', 'simpanan_pokok')
            ->latest('id')
            ->first();
        $this->simpananPokok = $latestPokok ? $latestPokok->saldo_sesudah : 0;

        // Hitung Saldo Wajib
        $latestWajib = MutasiSaldoMember::where('employee_id', $employeeId)
            ->where('jenis_saldo', 'simpanan_wajib')
            ->latest('id')
            ->first();
        $this->simpananWajib = $latestWajib ? $latestWajib->saldo_sesudah : 0;
                                       
        // Hitung Saldo Sukarela
        $latestSukarela = MutasiSaldoMember::where('employee_id', $employeeId)
            ->where('jenis_saldo', 'simpanan_sukarela')
            ->latest('id')
            ->first();
        $this->simpananSukarela = $latestSukarela ? $latestSukarela->saldo_sesudah : 0;

        $this->totalSimpanan = $this->simpananPokok + $this->simpananWajib + $this->simpananSukarela;
    }
    
    private function fetchPinjaman()
    {
        $userId = $this->member->employee->user?->id;

        if (!$userId) {
            return;
        }

        $this->pinjamanAktif = PengajuanUtama::with('items')
            ->where('user_id', $userId)
            ->where('status_approval', 'diterima')
            ->get();
    }
};
?>

<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <flux:button href="/admin/anggota" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
            <div>
                <flux:heading size="xl" level="1">Detail Anggota Koperasi</flux:heading>
                <flux:text class="mt-2 text-base">Informasi profil dan riwayat keuangan anggota.</flux:text>
            </div>
        </div>
        <div class="flex gap-2">
            <flux:button href="/admin/anggota/{{ $member->id }}/edit" wire:navigate variant="ghost" icon="pencil-square">Edit Profil</flux:button>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        
        <!-- Kolom Kiri: Profil Singkat & Info Finansial -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Profil Card -->
            <flux:card>
                <div class="flex flex-col items-center text-center">
                    <div class="w-24 h-24 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center text-3xl font-bold text-blue-600 dark:text-blue-400 mb-4">
                        {{ strtoupper(substr($member->employee->nama_lengkap ?? 'A', 0, 1)) }}
                    </div>
                    <flux:heading size="lg">{{ $member->employee->nama_lengkap ?? '-' }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ $member->employee->npk ?? '-' }} (NPK) • {{ $member->member_number }}</flux:text>
                    
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @if($member->status === 'active')
                            <flux:badge color="green">Aktif</flux:badge>
                        @else
                            <flux:badge color="red">Nonaktif</flux:badge>
                        @endif
                        
                        @if($member->join_date)
                            <flux:badge color="zinc" icon="calendar">Bergabung: {{ $member->join_date->format('d M Y') }}</flux:badge>
                        @endif
                    </div>
                </div>
            </flux:card>

            <!-- Ringkasan Simpanan -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Ringkasan Simpanan</flux:heading>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-zinc-500">Simpanan Pokok</span>
                        <span class="font-medium">Rp {{ number_format($simpananPokok, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-zinc-500">Simpanan Wajib</span>
                        <span class="font-medium">Rp {{ number_format($simpananWajib, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-zinc-500">Simpanan Sukarela</span>
                        <span class="font-medium">Rp {{ number_format($simpananSukarela, 0, ',', '.') }}</span>
                    </div>
                    
                    <flux:separator variant="subtle" />
                    
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">Total Simpanan</span>
                        <span class="font-bold text-green-600">Rp {{ number_format($totalSimpanan, 0, ',', '.') }}</span>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Kolom Kanan: Detail Lengkap & Cicilan -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Detail Informasi Diri -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Informasi Pribadi & Kontak</flux:heading>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Tempat, Tanggal Lahir</flux:text>
                        <flux:text class="font-medium">{{ $member->employee->tempat_lahir ?: '-' }}, {{ $member->employee->tanggal_lahir ? $member->employee->tanggal_lahir->format('d M Y') : '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Jenis Kelamin</flux:text>
                        <flux:text class="font-medium">{{ ($member->employee->jk ?? '') === 'L' ? 'Laki-Laki' : 'Perempuan' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Pendidikan Terakhir</flux:text>
                        <flux:text class="font-medium">{{ $member->employee->pendidikan_terakhir ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">No. WhatsApp</flux:text>
                        <flux:text class="font-medium">{{ $member->employee->no_telp ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Email Akun</flux:text>
                        <flux:text class="font-medium">{{ $member->employee->user->email ?? '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Tanggal Gabung Astra</flux:text>
                        <flux:text class="font-medium">{{ $member->join_koperasi_astra ? $member->join_koperasi_astra->format('d M Y') : '-' }}</flux:text>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Alamat Lengkap</flux:text>
                        <flux:text class="font-medium">{{ $member->employee->alamat ?: '-' }}</flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" class="mb-4" />

                <flux:heading size="lg" class="mb-4">Data Rekening & Ahli Waris</flux:heading>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Bank</flux:text>
                        <flux:text class="font-medium">{{ $member->nama_bank ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Nomor Rekening</flux:text>
                        <flux:text class="font-medium">{{ $member->no_rekening ?: '-' }}</flux:text>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Nama Pemilik Rekening</flux:text>
                        <flux:text class="font-medium">{{ $member->nama_pemilik_rekening ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Nama Ahli Waris</flux:text>
                        <flux:text class="font-medium">{{ $member->nama_ahli_waris ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Hubungan Ahli Waris</flux:text>
                        <flux:text class="font-medium">
                            @if($member->hubungan_ahli_waris === 'Lainnya')
                                {{ $member->hubungan_lainnya }}
                            @else
                                {{ ucfirst(str_replace('_', ' ', $member->hubungan_ahli_waris)) }}
                            @endif
                        </flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Cicilan Berjalan -->
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="lg">Cicilan / Pinjaman Berjalan</flux:heading>
                </div>
                
                @if(count($pinjamanAktif) > 0)
                    <div class="space-y-4">
                        @foreach($pinjamanAktif as $pinjaman)
                            <div class="p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50/50 dark:bg-zinc-800/30">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <flux:heading size="sm" class="font-semibold">{{ $pinjaman->nomor_pengajuan }}</flux:heading>
                                        <flux:text class="text-xs text-zinc-500">{{ date('d M Y', strtotime($pinjaman->tanggal_pengajuan)) }}</flux:text>
                                    </div>
                                    <flux:badge color="blue" size="sm">Diterima</flux:badge>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-zinc-600 dark:text-zinc-400">Total Pembiayaan:</span>
                                        <span class="font-medium">Rp {{ number_format($pinjaman->total_pembiayaan_syariah, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-zinc-600 dark:text-zinc-400">Total Nilai Bersih:</span>
                                        <span class="font-medium">Rp {{ number_format($pinjaman->total_estimasi_nilai, 0, ',', '.') }}</span>
                                    </div>
                                </div>

                                @if($pinjaman->items->count() > 0)
                                    <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                        <flux:text class="text-xs font-semibold mb-2">Detail Item:</flux:text>
                                        <ul class="space-y-1">
                                            @foreach($pinjaman->items as $item)
                                                <li class="flex justify-between text-xs">
                                                    <span class="text-zinc-600 dark:text-zinc-400 capitalize">{{ str_replace('_', ' ', $item->kategori_utama) }} ({{ $item->sub_jenis }})</span>
                                                    <span>Rp {{ number_format($item->nominal_per_item, 0, ',', '.') }} @if($item->tenor_bulan) • {{ $item->tenor_bulan }} bln @endif</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-zinc-500">
                        <flux:icon name="banknotes" class="w-10 h-10 mx-auto mb-2 opacity-50" />
                        <flux:text>Tidak ada cicilan atau pinjaman yang sedang berjalan saat ini.</flux:text>
                    </div>
                @endif
            </flux:card>

        </div>
    </div>
</div>

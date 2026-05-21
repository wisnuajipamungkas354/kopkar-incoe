<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\TransaksiMutasi;
use App\Models\PengajuanUtama;

new #[Layout('layouts::admin')] class extends Component
{
    public User $user;
    public $simpananPokok = 0;
    public $simpananWajib = 0;
    public $simpananSukarela = 0;
    public $totalSimpanan = 0;
    public $pinjamanAktif = [];

    public function mount($id)
    {
        $this->user = User::findOrFail($id);
        
        $this->calculateSimpanan();
        $this->fetchPinjaman();
    }
    
    private function calculateSimpanan()
    {
        // Ambil transaksi sukses
        $mutasi = TransaksiMutasi::where('user_id', $this->user->id)
            ->where('status_pembayaran', 'success')
            ->get();
            
        // Hitung Saldo Pokok
        $this->simpananPokok = $mutasi->where('kategori_transaksi', 'pokok')
            ->whereIn('jenis_transaksi', ['setoran_awal', 'setoran_tambahan', 'payroll_rutin', 'angsuran_bulanan'])
            ->sum('nominal') - 
            $mutasi->where('kategori_transaksi', 'pokok')
            ->where('jenis_transaksi', 'pencairan_dana')
            ->sum('nominal');

        // Hitung Saldo Wajib
        $this->simpananWajib = $mutasi->where('kategori_transaksi', 'wajib')
            ->whereIn('jenis_transaksi', ['setoran_awal', 'setoran_tambahan', 'payroll_rutin', 'angsuran_bulanan'])
            ->sum('nominal') - 
            $mutasi->where('kategori_transaksi', 'wajib')
            ->where('jenis_transaksi', 'pencairan_dana')
            ->sum('nominal');
                                      
        // Hitung Saldo Sukarela
        $this->simpananSukarela = $mutasi->where('kategori_transaksi', 'sukarela')
            ->whereIn('jenis_transaksi', ['setoran_awal', 'setoran_tambahan', 'payroll_rutin', 'angsuran_bulanan'])
            ->sum('nominal') - 
            $mutasi->where('kategori_transaksi', 'sukarela')
            ->where('jenis_transaksi', 'pencairan_dana')
            ->sum('nominal');

        $this->totalSimpanan = $this->simpananPokok + $this->simpananWajib + $this->simpananSukarela;
    }
    
    private function fetchPinjaman()
    {
        $this->pinjamanAktif = PengajuanUtama::with('items')
            ->where('user_id', $this->user->id)
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
                <flux:heading size="xl" level="1">Detail Anggota</flux:heading>
                <flux:text class="mt-2 text-base">Informasi profil dan riwayat keuangan anggota.</flux:text>
            </div>
        </div>
        <div class="flex gap-2">
            <flux:button href="/admin/anggota/{{ $user->id }}/edit" wire:navigate variant="ghost" icon="pencil-square">Edit Profil</flux:button>
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
                        {{ strtoupper(substr($user->nama_anggota, 0, 1)) }}
                    </div>
                    <flux:heading size="lg">{{ $user->nama_anggota }}</flux:heading>
                    <flux:text class="text-zinc-500">{{ $user->username }} (NPK)</flux:text>
                    
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @if($user->status_user == 1)
                            <flux:badge color="green">Aktif</flux:badge>
                        @else
                            <flux:badge color="red">Nonaktif</flux:badge>
                        @endif
                        
                        @if($user->join_date)
                            <flux:badge color="zinc" icon="calendar">Bergabung: {{ date('d M Y', strtotime($user->join_date)) }}</flux:badge>
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
                        <flux:text class="font-medium">{{ $user->ext_tempat_lahir ?: '-' }}, {{ $user->tanggal_lahir ? date('d M Y', strtotime($user->tanggal_lahir)) : '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Jenis Kelamin</flux:text>
                        <flux:text class="font-medium">{{ $user->gender == 'L' ? 'Laki-Laki' : 'Perempuan' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Pendidikan Terakhir</flux:text>
                        <flux:text class="font-medium">{{ $user->ext_pendidikan_terakhir ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">No. WhatsApp</flux:text>
                        <flux:text class="font-medium">{{ $user->no_telp ?: '-' }}</flux:text>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Alamat Lengkap</flux:text>
                        <flux:text class="font-medium">{{ $user->ext_alamat ?: '-' }}</flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" class="mb-4" />

                <flux:heading size="lg" class="mb-4">Data Rekening & Ahli Waris</flux:heading>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Bank</flux:text>
                        <flux:text class="font-medium">{{ $user->nama_bank ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Nomor Rekening</flux:text>
                        <flux:text class="font-medium">{{ $user->no_rekening ?: '-' }}</flux:text>
                    </div>
                    <div class="sm:col-span-2">
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Nama Pemilik Rekening</flux:text>
                        <flux:text class="font-medium">{{ $user->pemilik_no_rekening ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Nama Ahli Waris</flux:text>
                        <flux:text class="font-medium">{{ $user->ext_nama_ahli_waris ?: '-' }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs text-zinc-500 uppercase tracking-wider">Hubungan Ahli Waris</flux:text>
                        <flux:text class="font-medium">
                            {{ $user->ext_hubungan_ahli_waris === 'Lainnya' ? $user->ext_hubungan_lainnya : ($user->ext_hubungan_ahli_waris ?: '-') }}
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

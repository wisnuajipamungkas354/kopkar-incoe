<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pembiayaan;
use App\Models\TagihanPayrollEmployee;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Pembiayaan'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPembiayaan = null;
    public $alasanPenolakan = '';

    #[Computed]
    public function pengajuan()
    {
        $data = Pembiayaan::with(['employee', 'employee.user'])
                ->whereIn('status', ['diajukan', 'diproses'])
                ->orderBy('updated_at', 'DESC')
                ->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap ?? '', $this->search) !== false ||
                       stripos($item->employee->npk ?? '', $this->search) !== false ||
                       stripos($item->nomor_pengajuan ?? '', $this->search) !== false ||
                       stripos($item->kategori_pembiayaan ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPengajuan($id)
    {
        $this->selectedPembiayaan = Pembiayaan::with(['employee', 'employee.user'])->find($id);
        $this->alasanPenolakan = '';
        Flux::modal('detail-pengajuan')->show();
    }

    /**
     * Proses & Aktifkan Angsuran sekaligus (diajukan → berjalan)
     */
    public function prosesDanAktifkan($id)
    {
        $pembiayaan = Pembiayaan::find($id);

        if (!$pembiayaan || $pembiayaan->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($pembiayaan) {
            $nominal        = (float) $pembiayaan->nominal_pengajuan;
            $tenor          = (int) $pembiayaan->tenor_bulan;
            $marginPersen   = (float) ($pembiayaan->margin_persen ?? 8.5);
            $totalMargin    = $nominal * ($marginPersen / 100) * ($tenor / 12);
            $totalPembiayaan = $nominal + $totalMargin;
            $nomAngsuran    = $totalPembiayaan / $tenor;

            $pembiayaan->update([
                'status'            => 'berjalan',
                'nominal_disetujui' => $nominal,
                'total_margin'      => $totalMargin,
                'total_pembiayaan'  => $totalPembiayaan,
                'nominal_angsuran'  => $nomAngsuran,
                'tanggal_pencairan' => now()->toDateString(),
                'diproses_oleh'     => auth('web')->user()->id,
                'diproses_pada'     => now(),
            ]);

            TagihanPayrollEmployee::create([
                'employee_id'           => $pembiayaan->employee_id,
                'jenis_tagihan'         => 'pembiayaan',
                'tagihanable_type'      => Pembiayaan::class,
                'tagihanable_id'        => $pembiayaan->id,
                'periode_bulan'         => now()->format('m'),
                'periode_tahun'         => now()->format('Y'),
                'periode_payroll_bulan' => now()->addMonth()->format('m'),
                'periode_payroll_tahun' => now()->addMonth()->format('Y'),
                'nominal'               => $nomAngsuran,
                'status'                => 'pending',
                'keterangan'            => 'Angsuran Pembiayaan ' . $pembiayaan->nomor_pengajuan,
            ]);
        });

        Flux::toast(
            heading: 'Pembiayaan Disetujui & Berjalan',
            text: 'Pembiayaan telah diproses dan angsuran telah diaktifkan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
        unset($this->pengajuan);
    }

    /**
     * Proses saja (diajukan → diproses) — review dulu sebelum cairkan
     */
    public function prosesPengajuan($id)
    {
        $pembiayaan = Pembiayaan::find($id);

        if (!$pembiayaan || $pembiayaan->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($pembiayaan) {
            $pembiayaan->update([
                'status'        => 'diproses',
                'diproses_oleh' => auth('web')->user()->id,
                'diproses_pada' => now(),
            ]);
        });

        Flux::toast(
            heading: 'Sedang Diproses',
            text: 'Pengajuan pembiayaan sedang diproses. Aktifkan angsuran bila dana sudah dicairkan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
        unset($this->pengajuan);
    }

    /**
     * Aktifkan Angsuran (diproses → berjalan)
     * Kalkulasi margin, set tanggal_pencairan, generate tagihan
     */
    public function aktifkanAngsuran($id)
    {
        $pembiayaan = Pembiayaan::find($id);

        if (!$pembiayaan || $pembiayaan->status !== 'diproses') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($pembiayaan) {
            $nominal        = (float) $pembiayaan->nominal_pengajuan;
            $tenor          = (int) $pembiayaan->tenor_bulan;
            $marginPersen   = (float) ($pembiayaan->margin_persen ?? 8.5);
            $totalMargin    = $nominal * ($marginPersen / 100) * ($tenor / 12);
            $totalPembiayaan = $nominal + $totalMargin;
            $nomAngsuran    = $totalPembiayaan / $tenor;

            $pembiayaan->update([
                'status'            => 'berjalan',
                'nominal_disetujui' => $nominal,
                'total_margin'      => $totalMargin,
                'total_pembiayaan'  => $totalPembiayaan,
                'nominal_angsuran'  => $nomAngsuran,
                'tanggal_pencairan' => now()->toDateString(),
            ]);

            TagihanPayrollEmployee::create([
                'employee_id'           => $pembiayaan->employee_id,
                'jenis_tagihan'         => 'pembiayaan',
                'tagihanable_type'      => Pembiayaan::class,
                'tagihanable_id'        => $pembiayaan->id,
                'periode_bulan'         => now()->format('m'),
                'periode_tahun'         => now()->format('Y'),
                'periode_payroll_bulan' => now()->addMonth()->format('m'),
                'periode_payroll_tahun' => now()->addMonth()->format('Y'),
                'nominal'               => $nomAngsuran,
                'status'                => 'pending',
                'keterangan'            => 'Angsuran Pembiayaan ' . $pembiayaan->nomor_pengajuan,
            ]);
        });

        Flux::toast(
            heading: 'Angsuran Aktif',
            text: 'Pembiayaan aktif dan tagihan angsuran pertama telah dibuat.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
        unset($this->pengajuan);
    }

    public function tolak($id)
    {
        $pembiayaan = Pembiayaan::find($id);

        if (!$pembiayaan) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->validate([
            'alasanPenolakan' => 'required|string|max:500'
        ], [
            'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.'
        ]);

        DB::transaction(function () use ($pembiayaan) {
            $pembiayaan->update([
                'status'           => 'ditolak',
                'alasan_penolakan' => $this->alasanPenolakan,
                'ditolak_oleh'     => auth('web')->user()->id,
                'ditolak_pada'     => now(),
            ]);
        });

        Flux::toast(
            heading: 'Pengajuan Ditolak',
            text: 'Pengajuan pembiayaan berhasil ditolak.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPembiayaan = null;
        $this->alasanPenolakan = '';
        unset($this->pengajuan);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Pembiayaan</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan pembiayaan syariah anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan Pembiayaan</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK / nomor..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Diajukan</flux:table.column>
                    <flux:table.column>No. Pengajuan</flux:table.column>
                    <flux:table.column>NPK & Nama Anggota</flux:table.column>
                    <flux:table.column>Kategori</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Tenor</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ $row->diajukan_pada ? \Carbon\Carbon::parse($row->diajukan_pada)->format('d/m/Y') : ($row->created_at ? $row->created_at->format('d/m/Y') : '-') }}</flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-800 dark:text-white text-xs">{{ $row->nomor_pengajuan }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->employee->nama_lengkap ?? 'A', 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-medium block">{{ $row->employee->nama_lengkap ?? 'Unknown' }}</span>
                                        <span class="text-xs text-zinc-400 block">NPK: {{ $row->employee->npk ?? '-' }}</span>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->kategori_pembiayaan === 'barang')
                                    <flux:badge color="blue" size="sm">Pembelian Barang</flux:badge>
                                @elseif($row->kategori_pembiayaan === 'pendidikan')
                                    <flux:badge color="purple" size="sm">Pendidikan</flux:badge>
                                @elseif($row->kategori_pembiayaan === 'kesehatan')
                                    <flux:badge color="teal" size="sm">Kesehatan</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ ucfirst($row->kategori_pembiayaan) }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($row->nominal_pengajuan, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>{{ $row->tenor_bulan }} Bulan</flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'diajukan')
                                    <flux:badge color="orange" size="sm" icon="clock">Menunggu</flux:badge>
                                @elseif($row->status === 'diproses')
                                    <flux:badge color="sky" size="sm" icon="clock">Diproses</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPengajuan({{ $row->id }})">Detail</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center text-gray-500 py-6">Tidak ada pengajuan pembiayaan yang perlu disetujui.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail Pengajuan -->
    <flux:modal name="detail-pengajuan" class="md:w-2xl max-h-[90vh] overflow-y-auto">
        @if($selectedPembiayaan)
            <div>
                <flux:heading size="lg">Detail Pengajuan Pembiayaan</flux:heading>
                <flux:text size="sm" class="mt-1">Tinjau informasi pengajuan pembiayaan anggota koperasi.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Info Anggota -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ substr($selectedPembiayaan->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPembiayaan->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">NPK: {{ $selectedPembiayaan->employee->npk ?? '-' }} • Seksi: {{ $selectedPembiayaan->employee->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <!-- Status Stepper -->
                <div class="flex items-center text-xs font-semibold">
                    @php
                        $steps = ['diajukan' => 'Diajukan', 'diproses' => 'Diproses', 'berjalan' => 'Berjalan', 'lunas' => 'Lunas'];
                        $colors = ['diajukan' => 'bg-orange-500', 'diproses' => 'bg-sky-500', 'berjalan' => 'bg-emerald-500', 'lunas' => 'bg-green-600'];
                        $statusOrder = array_keys($steps);
                        $currentIdx = array_search($selectedPembiayaan->status, $statusOrder);
                    @endphp
                    @foreach($steps as $key => $label)
                        @php $idx = array_search($key, $statusOrder); @endphp
                        <div class="flex flex-col items-center gap-1 flex-1">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold {{ $currentIdx !== false && $idx <= $currentIdx ? ($colors[$key] ?? 'bg-zinc-400') : 'bg-zinc-300 dark:bg-zinc-700' }}">
                                {{ $idx + 1 }}
                            </div>
                            <span class="text-[10px] text-center {{ $currentIdx !== false && $idx <= $currentIdx ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400' }}">{{ $label }}</span>
                        </div>
                        @if(!$loop->last)
                            <div class="flex-1 h-0.5 {{ $currentIdx !== false && $idx < $currentIdx ? 'bg-emerald-400' : 'bg-zinc-200 dark:bg-zinc-700' }} mb-3"></div>
                        @endif
                    @endforeach
                </div>

                <!-- Detail -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Nomor Pengajuan</flux:text>
                        <flux:text class="font-semibold text-zinc-900 dark:text-white">{{ $selectedPembiayaan->nomor_pengajuan }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Kategori Pembiayaan</flux:text>
                        <flux:text class="font-semibold text-zinc-900 dark:text-white">{{ ucfirst($selectedPembiayaan->kategori_pembiayaan) }}</flux:text>
                    </div>
                    <div class="col-span-2">
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Tujuan Pembiayaan</flux:text>
                        <flux:text class="font-medium text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap">{{ $selectedPembiayaan->tujuan_pembiayaan }}</flux:text>
                    </div>
                </div>

                <!-- Rincian Barang -->
                @if($selectedPembiayaan->kategori_pembiayaan === 'barang' && !empty($selectedPembiayaan->rincian_barang))
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rincian Barang</flux:heading>
                        <div class="space-y-2">
                            @foreach($selectedPembiayaan->rincian_barang as $index => $item)
                                <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $item['rincian'] ?? '-' }}</span>
                                    <span class="text-sm font-bold">Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Simulasi -->
                <div class="p-4 bg-gradient-to-tr from-emerald-500/5 to-teal-500/5 dark:from-emerald-950/10 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                    <flux:heading size="sm" class="mb-3 text-emerald-800 dark:text-emerald-300">Simulasi Perhitungan</flux:heading>
                    <flux:separator />
                    @php
                        $nominal        = (float) $selectedPembiayaan->nominal_pengajuan;
                        $tenor          = (int) $selectedPembiayaan->tenor_bulan;
                        $marginPersen   = (float) ($selectedPembiayaan->margin_persen ?? 8.5);
                        $angsuranPokok  = $nominal / max(1, $tenor);
                        $marginBulanan  = ($nominal * ($marginPersen / 100)) / 12;
                        $angsuranTotal  = $angsuranPokok + $marginBulanan;
                        $totalPembiayaan = $angsuranTotal * $tenor;
                    @endphp
                    <div class="space-y-2 text-sm mt-3">
                        <div class="flex justify-between"><span class="text-zinc-500">Nominal Pokok</span><span class="font-semibold">Rp {{ number_format($nominal, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-500">Tenor</span><span class="font-semibold">{{ $tenor }} Bulan</span></div>
                        <div class="flex justify-between border-t border-dashed border-zinc-200 dark:border-zinc-700 pt-2">
                            <span class="text-zinc-500">Margin ({{ $marginPersen }}%)</span>
                            <span class="font-semibold">Rp {{ number_format($marginBulanan, 0, ',', '.') }}/bln</span>
                        </div>
                        <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-2 text-emerald-700 dark:text-emerald-400 font-bold">
                            <span>Total Angsuran / Bulan</span>
                            <span class="text-base">Rp {{ number_format($angsuranTotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-t-2 border-double border-zinc-300 dark:border-zinc-700 pt-2 font-bold">
                            <span>Total Pembiayaan</span>
                            <span class="text-emerald-700 dark:text-emerald-300 text-lg">Rp {{ number_format($totalPembiayaan, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Rekening -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rekening Pencairan</flux:heading>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-zinc-400 block">Nama Bank</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPembiayaan->nama_bank }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-400 block">No. Rekening</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPembiayaan->no_rekening }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-400 block">Atas Nama</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPembiayaan->nama_pemilik_rekening }}</span>
                        </div>
                    </div>
                </div>

                <!-- Referensi Pihak Ketiga -->
                @if(!empty($selectedPembiayaan->nama_pihak_ketiga))
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Referensi Pihak Ketiga</flux:heading>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-xs text-zinc-400 block">Nama</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPembiayaan->nama_pihak_ketiga }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-zinc-400 block">No. Telepon</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPembiayaan->no_telp_pihak_ketiga }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <flux:separator variant="subtle" />

                <!-- Actions -->
                <div class="space-y-4">
                    @if(in_array($selectedPembiayaan->status, ['diajukan', 'diproses']))
                        <flux:field>
                            <flux:label>Alasan Penolakan <span class="text-zinc-400 text-xs">(wajib diisi jika menolak)</span></flux:label>
                            <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan di sini..." rows="2" />
                            <flux:error name="alasanPenolakan" />
                        </flux:field>
                    @endif

                    <div class="flex justify-end gap-3 pt-2">
                        @if(in_array($selectedPembiayaan->status, ['diajukan', 'diproses']))
                            <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedPembiayaan->id }})">Tolak Pengajuan</flux:button>
                        @endif

                        @if($selectedPembiayaan->status === 'diajukan')
                            <flux:button variant="primary" color="sky" icon="check" wire:click="prosesPengajuan({{ $selectedPembiayaan->id }})">Proses Pengajuan</flux:button>
                            <flux:button variant="primary" color="emerald" icon="check-circle" wire:click="prosesDanAktifkan({{ $selectedPembiayaan->id }})">Setujui & Aktifkan</flux:button>
                        @elseif($selectedPembiayaan->status === 'diproses')
                            <flux:button variant="primary" color="emerald" icon="banknotes" wire:click="aktifkanAngsuran({{ $selectedPembiayaan->id }})">Dana Cair → Aktifkan Angsuran</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">Memuat data pengajuan...</div>
        @endif
    </flux:modal>
</div>
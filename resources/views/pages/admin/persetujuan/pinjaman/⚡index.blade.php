<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Pinjaman;
use App\Models\TagihanPayrollEmployee;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Pinjaman'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPinjaman = null;
    public $alasanPenolakan = '';

    #[Computed]
    public function pengajuan()
    {
        $data = Pinjaman::with(['employee', 'employee.user'])
                ->whereIn('status', ['diajukan', 'diproses'])
                ->orderBy('updated_at', 'DESC')
                ->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap ?? '', $this->search) !== false ||
                       stripos($item->employee->npk ?? '', $this->search) !== false ||
                       stripos($item->nomor_pengajuan ?? '', $this->search) !== false ||
                       stripos($item->jenis_pinjaman ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPengajuan($id)
    {
        $this->selectedPinjaman = Pinjaman::with(['employee', 'employee.user'])->find($id);
        $this->alasanPenolakan = '';
        Flux::modal('detail-pengajuan')->show();
    }

    /**
     * Proses & Cairkan Pinjaman sekaligus (diajukan → diproses → berjalan)
     * Hitung angsuran, set tanggal_pencairan, generate tagihan payroll
     */
    public function prosesDanAktifkan($id)
    {
        $pinjaman = Pinjaman::find($id);

        if (!$pinjaman || $pinjaman->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($pinjaman) {
            $nominal     = (float) $pinjaman->nominal_pengajuan;
            $tenor       = (int) $pinjaman->tenor_bulan;
            $nomAngsuran = $nominal / $tenor;

            // Langsung set berjalan: diproses → berjalan dalam satu aksi
            $pinjaman->update([
                'status'            => 'berjalan',
                'nominal_disetujui' => $nominal,
                'nominal_angsuran'  => $nomAngsuran,
                'tanggal_pencairan' => now()->toDateString(),
                'diproses_oleh'     => auth('web')->user()->id,
                'diproses_pada'     => now(),
            ]);

            // Generate tagihan payroll pertama
            TagihanPayrollEmployee::create([
                'employee_id'           => $pinjaman->employee_id,
                'jenis_tagihan'         => 'pinjaman',
                'tagihanable_type'      => Pinjaman::class,
                'tagihanable_id'        => $pinjaman->id,
                'periode_bulan'         => now()->format('m'),
                'periode_tahun'         => now()->format('Y'),
                'periode_payroll_bulan' => now()->addMonth()->format('m'),
                'periode_payroll_tahun' => now()->addMonth()->format('Y'),
                'nominal'               => $nomAngsuran,
                'status'                => 'pending',
                'keterangan'            => 'Angsuran Pinjaman ' . $pinjaman->nomor_pengajuan,
            ]);
        });

        Flux::toast(
            heading: 'Pinjaman Disetujui & Berjalan',
            text: 'Pinjaman telah diproses, dicairkan, dan cicilan telah diaktifkan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
        unset($this->pengajuan);
    }

    /**
     * Proses saja (diajukan → diproses) — untuk review sebelum cairkan
     */
    public function prosesPengajuan($id)
    {
        $pinjaman = Pinjaman::find($id);

        if (!$pinjaman || $pinjaman->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($pinjaman) {
            $pinjaman->update([
                'status'        => 'diproses',
                'diproses_oleh' => auth('web')->user()->id,
                'diproses_pada' => now(),
            ]);
        });

        Flux::toast(
            heading: 'Sedang Diproses',
            text: 'Pengajuan pinjaman sedang diproses. Silakan aktifkan cicilan bila dana sudah dicairkan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
        unset($this->pengajuan);
    }

    /**
     * Aktifkan cicilan (diproses → berjalan)
     * Set tanggal_pencairan & generate tagihan payroll
     */
    public function aktifkanCicilan($id)
    {
        $pinjaman = Pinjaman::find($id);

        if (!$pinjaman || $pinjaman->status !== 'diproses') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($pinjaman) {
            $nominal     = (float) $pinjaman->nominal_pengajuan;
            $tenor       = (int) $pinjaman->tenor_bulan;
            $nomAngsuran = $nominal / $tenor;

            $pinjaman->update([
                'status'            => 'berjalan',
                'nominal_disetujui' => $nominal,
                'nominal_angsuran'  => $nomAngsuran,
                'tanggal_pencairan' => now()->toDateString(),
            ]);

            TagihanPayrollEmployee::create([
                'employee_id'           => $pinjaman->employee_id,
                'jenis_tagihan'         => 'pinjaman',
                'tagihanable_type'      => Pinjaman::class,
                'tagihanable_id'        => $pinjaman->id,
                'periode_bulan'         => now()->format('m'),
                'periode_tahun'         => now()->format('Y'),
                'periode_payroll_bulan' => now()->addMonth()->format('m'),
                'periode_payroll_tahun' => now()->addMonth()->format('Y'),
                'nominal'               => $nomAngsuran,
                'status'                => 'pending',
                'keterangan'            => 'Angsuran Pinjaman ' . $pinjaman->nomor_pengajuan,
            ]);
        });

        Flux::toast(
            heading: 'Cicilan Aktif',
            text: 'Pinjaman telah aktif dan tagihan cicilan pertama telah dibuat.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
        unset($this->pengajuan);
    }

    public function tolak($id)
    {
        $pinjaman = Pinjaman::find($id);

        if (!$pinjaman) {
            Flux::toast(heading: 'Error', text: 'Data pengajuan tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->validate([
            'alasanPenolakan' => 'required|string|max:500'
        ], [
            'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.'
        ]);

        DB::transaction(function () use ($pinjaman) {
            $pinjaman->update([
                'status'           => 'ditolak',
                'alasan_penolakan' => $this->alasanPenolakan,
                'ditolak_oleh'     => auth('web')->user()->id,
                'ditolak_pada'     => now(),
            ]);
        });

        Flux::toast(
            heading: 'Pengajuan Ditolak',
            text: 'Pengajuan pinjaman berhasil ditolak.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPinjaman = null;
        $this->alasanPenolakan = '';
        unset($this->pengajuan);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Pinjaman</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan pinjaman qard hasan dan bon sementara anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan Pinjaman</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK / nomor..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Diajukan</flux:table.column>
                    <flux:table.column>No. Pengajuan</flux:table.column>
                    <flux:table.column>NPK & Nama Anggota</flux:table.column>
                    <flux:table.column>Program Pinjaman</flux:table.column>
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
                                @if($row->jenis_pinjaman === 'qard')
                                    <flux:badge color="emerald" size="sm">Qard Hasan</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">Bon Sementara</flux:badge>
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
                            <flux:table.cell colspan="8" class="text-center text-gray-500 py-6">Tidak ada pengajuan pinjaman yang perlu disetujui.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail Pengajuan -->
    <flux:modal name="detail-pengajuan" class="md:w-xl max-h-[90vh] overflow-y-auto">
        @if($selectedPinjaman)
            <div>
                <flux:heading size="lg">Detail Pengajuan Pinjaman</flux:heading>
                <flux:text size="sm" class="mt-1">Tinjau informasi pengajuan pinjaman anggota.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Info Anggota -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ substr($selectedPinjaman->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPinjaman->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">NPK: {{ $selectedPinjaman->employee->npk ?? '-' }} • Seksi: {{ $selectedPinjaman->employee->seksi ?? '-' }}</flux:text>
                    </div>
                </div>

                <!-- Status Stepper -->
                <div class="flex items-center text-xs font-semibold">
                    @php
                        $steps = ['diajukan' => 'Diajukan', 'diproses' => 'Diproses', 'berjalan' => 'Berjalan', 'lunas' => 'Lunas'];
                        $colors = ['diajukan' => 'bg-orange-500', 'diproses' => 'bg-sky-500', 'berjalan' => 'bg-emerald-500', 'lunas' => 'bg-green-600'];
                        $statusOrder = array_keys($steps);
                        $currentIdx = array_search($selectedPinjaman->status, $statusOrder);
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
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Nomor Pengajuan</flux:text>
                        <flux:text class="font-semibold text-zinc-900 dark:text-white text-sm">{{ $selectedPinjaman->nomor_pengajuan }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Kategori Pinjaman</flux:text>
                        @if($selectedPinjaman->jenis_pinjaman === 'qard')
                            <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">Qard Hasan</span>
                        @else
                            <span class="inline-flex px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">Bon Sementara</span>
                        @endif
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Nominal Diajukan</flux:text>
                        <flux:text class="text-lg font-bold text-zinc-900 dark:text-white">Rp {{ number_format($selectedPinjaman->nominal_pengajuan, 0, ',', '.') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-400 mb-1">Tenor Angsuran</flux:text>
                        <flux:text class="text-lg font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedPinjaman->tenor_bulan }} Bulan</flux:text>
                    </div>
                    <div class="col-span-2 p-3 rounded-lg border text-xs
                        {{ $selectedPinjaman->jenis_pinjaman === 'bon'
                            ? 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/20 dark:border-amber-900/40 dark:text-amber-300'
                            : 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-900/40 dark:text-emerald-300' }}">
                        @if($selectedPinjaman->jenis_pinjaman === 'bon')
                            <strong>Penting:</strong> Pinjaman Bon Sementara dipotong penuh pada siklus penggajian berikutnya.
                        @else
                            <strong>Estimasi Angsuran per Bulan:</strong>
                            Rp {{ number_format($selectedPinjaman->nominal_pengajuan / max(1, $selectedPinjaman->tenor_bulan), 0, ',', '.') }} /bulan
                        @endif
                    </div>
                </div>

                <!-- Rekening -->
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <flux:heading size="sm" class="mb-3 text-zinc-700 dark:text-zinc-300">Rekening Pencairan</flux:heading>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-zinc-400 block">Nama Bank</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPinjaman->nama_bank }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-400 block">No. Rekening</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPinjaman->no_rekening }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-400 block">Atas Nama</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPinjaman->nama_pemilik_rekening }}</span>
                        </div>
                    </div>
                </div>

                @if($selectedPinjaman->catatan)
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-100 text-sm text-blue-700 dark:text-blue-300">
                        <strong>Catatan:</strong> {{ $selectedPinjaman->catatan }}
                    </div>
                @endif

                <flux:separator variant="subtle" />

                <!-- Actions -->
                <div class="space-y-4">
                    @if(in_array($selectedPinjaman->status, ['diajukan', 'diproses']))
                        <flux:field>
                            <flux:label>Alasan Penolakan <span class="text-zinc-400 text-xs">(wajib diisi jika menolak)</span></flux:label>
                            <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan di sini..." rows="2" />
                            <flux:error name="alasanPenolakan" />
                        </flux:field>
                    @endif

                    <div class="flex justify-end gap-3 pt-2">
                        @if(in_array($selectedPinjaman->status, ['diajukan', 'diproses']))
                            <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedPinjaman->id }})">Tolak Pengajuan</flux:button>
                        @endif

                        @if($selectedPinjaman->status === 'diajukan')
                            <flux:button variant="primary" color="sky" icon="check" wire:click="prosesPengajuan({{ $selectedPinjaman->id }})">Proses Pengajuan</flux:button>
                            <flux:button variant="primary" color="emerald" icon="check-circle" wire:click="prosesDanAktifkan({{ $selectedPinjaman->id }})">Setujui & Aktifkan</flux:button>
                        @elseif($selectedPinjaman->status === 'diproses')
                            <flux:button variant="primary" color="emerald" icon="banknotes" wire:click="aktifkanCicilan({{ $selectedPinjaman->id }})">Dana Cair → Aktifkan Cicilan</flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">Memuat data pengajuan...</div>
        @endif
    </flux:modal>
</div>
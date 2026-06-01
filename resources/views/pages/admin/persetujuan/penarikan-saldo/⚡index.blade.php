<?php

use App\Models\KoperasiMember;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\PenarikanSaldo;
use App\Models\MutasiSaldoMember;
use Illuminate\Support\Facades\DB;
use Flux\Flux;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Penarikan Saldo'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedPengajuan = null;
    public $alasanPenolakan = '';

    #[Computed]
    public function pengajuan()
    {
        $data = PenarikanSaldo::with(['employee', 'employee.user', 'detailPenarikanSaldo'])
                ->whereIn('status', ['diajukan', 'diproses'])
                ->orderBy('created_at', 'DESC')
                ->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->employee->nama_lengkap ?? '', $this->search) !== false ||
                       stripos($item->employee->npk ?? '', $this->search) !== false ||
                       stripos($item->nomor_pengajuan ?? '', $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPengajuan($id)
    {
        $this->selectedPengajuan = PenarikanSaldo::with(['employee', 'employee.user', 'detailPenarikanSaldo'])->find($id);
        $this->alasanPenolakan = '';
        Flux::modal('detail-pengajuan')->show();
    }

    /**
     * Proses penarikan (diajukan → diproses)
     */
    public function prosesPenarikan($id)
    {
        $penarikan = PenarikanSaldo::find($id);

        if (!$penarikan || $penarikan->status !== 'diajukan') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($penarikan) {
            $penarikan->update([
                'status'        => 'diproses',
                'diproses_oleh' => auth('web')->user()->userable->npk,
                'diproses_pada' => now(),
            ]);
        });

        Flux::toast(
            heading: 'Sedang Diproses',
            text: 'Penarikan sedang diproses. Lakukan transfer dana ke rekening anggota, lalu selesaikan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
        unset($this->pengajuan);
    }

    /**
     * Selesaikan penarikan (diproses → selesai)
     * Kurangi saldo anggota via MutasiSaldoMember
     */
    public function selesaikanPenarikan($id)
    {
        $penarikan = PenarikanSaldo::with(['detailPenarikanSaldo'])->find($id);
        $member = KoperasiMember::where('employee_id', $penarikan->employee_id)->first();

        if (!$penarikan || $penarikan->status !== 'diproses') {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan atau status tidak valid.', variant: 'danger');
            return;
        }

        DB::transaction(function () use ($penarikan, $member) {
            foreach ($penarikan->detailPenarikanSaldo as $detail) {
                $jenisSaldo = $detail->sumber_saldo;
                $namaJenisSaldo = 'saldo_' . $jenisSaldo;

                $saldoTerakhir = MutasiSaldoMember::where('employee_id', $penarikan->employee_id)
                    ->where('jenis_saldo', $jenisSaldo)
                    ->latest('id')
                    ->value('saldo_sesudah') ?? $member[$namaJenisSaldo];

                MutasiSaldoMember::create([
                    'employee_id'      => $penarikan->employee_id,
                    'jenis_saldo'      => $jenisSaldo,
                    'jenis_mutasi'     => 'debit',
                    'nominal'          => $detail->nominal,
                    'saldo_sebelum'    => $saldoTerakhir,
                    'saldo_sesudah'    => max(0, $saldoTerakhir - $detail->nominal),
                    'sumber_transaksi' => 'penarikan_saldo',
                    'referensi_id'     => $penarikan->id,
                    'keterangan'       => 'Penarikan Saldo — ' . $penarikan->nomor_pengajuan,
                    'diproses_oleh'    => auth('web')->user()->id,
                ]);

                $member->update([
                    $namaJenisSaldo => $saldoTerakhir - $detail->nominal,
                ]);
            }

            $penarikan->update([
                'status'            => 'selesai',
                'tanggal_pencairan' => now()->toDateString(),
            ]);
        });

        Flux::toast(
            heading: 'Penarikan Selesai',
            text: 'Dana berhasil ditransfer dan saldo anggota telah disesuaikan.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
        unset($this->pengajuan);
    }

    public function tolak($id)
    {
        $penarikan = PenarikanSaldo::find($id);

        if (!$penarikan) {
            Flux::toast(heading: 'Error', text: 'Data tidak ditemukan.', variant: 'danger');
            return;
        }

        $this->validate([
            'alasanPenolakan' => 'required|string|max:500'
        ], [
            'alasanPenolakan.required' => 'Alasan penolakan wajib diisi.'
        ]);

        DB::transaction(function () use ($penarikan) {
            $penarikan->update([
                'status'           => 'ditolak',
                'alasan_penolakan' => $this->alasanPenolakan,
                'ditolak_oleh'     => auth('web')->user()->userable->npk,
                'ditolak_pada'     => now(),
            ]);
        });

        Flux::toast(
            heading: 'Pengajuan Ditolak',
            text: 'Pengajuan penarikan saldo berhasil ditolak.',
            variant: 'success',
        );

        Flux::modal('detail-pengajuan')->close();
        $this->selectedPengajuan = null;
        $this->alasanPenolakan = '';
        unset($this->pengajuan);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Penarikan Saldo</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pengajuan penarikan saldo simpanan anggota.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pengajuan Penarikan</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK / nomor..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>No. Pengajuan</flux:table.column>
                    <flux:table.column>NPK & Nama Anggota</flux:table.column>
                    <flux:table.column>Total Nominal</flux:table.column>
                    <flux:table.column>Rekening Tujuan</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pengajuan as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ $row->created_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs text-zinc-500">{{ $row->nomor_pengajuan }}</flux:table.cell>
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
                            <flux:table.cell class="font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($row->total_penarikan, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <span class="font-medium block text-sm">{{ $row->nama_bank }}</span>
                                <span class="text-xs text-zinc-400 font-mono">{{ $row->no_rekening }}</span>
                            </flux:table.cell>
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
                            <flux:table.cell colspan="7" class="text-center text-gray-500 py-6">Tidak ada pengajuan penarikan saldo yang tertunda.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail -->
    <flux:modal name="detail-pengajuan" class="md:w-xl max-h-[90vh] overflow-y-auto">
        @if($selectedPengajuan)
            <div>
                <flux:heading size="lg">Detail Penarikan Saldo</flux:heading>
                <flux:text size="sm" class="mt-1">Periksa rincian penarikan sebelum melakukan transfer.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <!-- Info Anggota -->
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedPengajuan->employee->nama_lengkap ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedPengajuan->employee->nama_lengkap ?? 'Unknown' }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">
                            NPK: {{ $selectedPengajuan->employee->npk ?? '-' }} • {{ $selectedPengajuan->employee->seksi ?? '-' }}
                        </flux:text>
                        <div class="mt-1">
                            @if($selectedPengajuan->status === 'diajukan')
                                <flux:badge color="orange" size="sm">Menunggu Proses</flux:badge>
                            @elseif($selectedPengajuan->status === 'diproses')
                                <flux:badge color="sky" size="sm">Sedang Diproses</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Rincian Saldo -->
                <div>
                    <flux:text class="text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-3">Rincian Saldo Ditarik</flux:text>
                    <div class="space-y-2">
                        @foreach($selectedPengajuan->detailPenarikanSaldo as $detail)
                            <div class="flex justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ ucwords(str_replace('_', ' ', $detail->sumber_saldo)) }}
                                </span>
                                <span class="font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($detail->nominal, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-between items-center mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40 rounded-lg">
                        <span class="font-bold text-blue-800 dark:text-blue-200">Total Penarikan</span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($selectedPengajuan->total_penarikan, 0, ',', '.') }}</span>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <!-- Rekening -->
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Bank Tujuan</flux:text>
                        <flux:text class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->nama_bank }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Nomor Rekening</flux:text>
                        <flux:text class="font-semibold font-mono text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->no_rekening }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Atas Nama</flux:text>
                        <flux:text class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedPengajuan->nama_pemilik_rekening }}</flux:text>
                    </div>
                </div>

                @if($selectedPengajuan->catatan)
                    <div>
                        <flux:text class="text-xs font-medium text-zinc-500 mb-1">Keterangan</flux:text>
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                            <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">{{ $selectedPengajuan->catatan }}</flux:text>
                        </div>
                    </div>
                @endif

                <!-- Alasan Penolakan -->
                @if(in_array($selectedPengajuan->status, ['diajukan', 'diproses']))
                    <flux:field>
                        <flux:label>Alasan Penolakan <span class="text-zinc-400 text-xs">(wajib diisi jika menolak)</span></flux:label>
                        <flux:textarea wire:model="alasanPenolakan" placeholder="Tulis alasan penolakan..." rows="2" />
                        <flux:error name="alasanPenolakan" />
                    </flux:field>
                @endif

                <div class="flex justify-end gap-3 pt-2">
                    @if(in_array($selectedPengajuan->status, ['diajukan', 'diproses']))
                        <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedPengajuan->id }})">Tolak</flux:button>
                    @endif

                    @if($selectedPengajuan->status === 'diajukan')
                        <flux:button variant="primary" color="sky" icon="check" wire:click="prosesPenarikan({{ $selectedPengajuan->id }})">Proses Penarikan</flux:button>
                    @elseif($selectedPengajuan->status === 'diproses')
                        <flux:button variant="primary" color="emerald" icon="banknotes" wire:click="selesaikanPenarikan({{ $selectedPengajuan->id }})">Transfer & Selesaikan</flux:button>
                    @endif
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">Memuat data pengajuan...</div>
        @endif
    </flux:modal>
</div>
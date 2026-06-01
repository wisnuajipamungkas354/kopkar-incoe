<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Employee;
use App\Models\Pembiayaan;
use App\Models\NamaBank;

new #[Layout('layouts::admin', ['title' => 'Edit Pembiayaan'])] class extends Component
{
    public Pembiayaan $pembiayaan;

    // Karyawan
    public $employee_id      = '';
    public $employeeSearch   = '';
    public $selectedEmployee = null;

    // Form fields
    public $kategori_pembiayaan      = 'barang';
    public $tujuan_pembiayaan        = '';
    public $items_barang             = [['rincian' => '', 'harga' => '']];
    public $nominal_pembiayaan       = '';
    public $tenor_bulan_pb           = '';
    public $margin_persen            = 8.5;
    public $pencairan_dana_ke        = 'anggota';
    public $nama_pihak_ketiga        = '';
    public $no_telp_pihak_ketiga     = '';
    public $alamat_pihak_ketiga      = '';
    public $nominal_disetujui        = '';
    public $nama_bank_pb             = '';
    public $no_rekening_pb           = '';
    public $nama_pemilik_rekening_pb = '';
    public $status                   = 'diajukan';
    public $alasan_penolakan         = '';
    public $catatan                  = '';

    public function mount($id)
    {
        $this->pembiayaan = Pembiayaan::with('employee')->findOrFail($id);

        $this->employee_id          = $this->pembiayaan->employee_id;
        $this->kategori_pembiayaan  = $this->pembiayaan->kategori_pembiayaan;
        $this->tujuan_pembiayaan    = $this->pembiayaan->tujuan_pembiayaan;
        $this->nominal_disetujui    = $this->pembiayaan->nominal_disetujui ?? '';
        $this->tenor_bulan_pb       = $this->pembiayaan->tenor_bulan;
        $this->margin_persen        = $this->pembiayaan->margin_persen;
        $this->pencairan_dana_ke    = $this->pembiayaan->pencairan_dana_ke ?? 'anggota';
        $this->nama_pihak_ketiga    = $this->pembiayaan->nama_pihak_ketiga ?? '';
        $this->no_telp_pihak_ketiga = $this->pembiayaan->no_telp_pihak_ketiga ?? '';
        $this->alamat_pihak_ketiga  = $this->pembiayaan->alamat_pihak_ketiga ?? '';
        $this->nama_bank_pb             = $this->pembiayaan->nama_bank;
        $this->no_rekening_pb           = $this->pembiayaan->no_rekening;
        $this->nama_pemilik_rekening_pb = $this->pembiayaan->nama_pemilik_rekening;
        $this->status               = $this->pembiayaan->status;
        $this->alasan_penolakan     = $this->pembiayaan->alasan_penolakan ?? '';
        $this->catatan              = $this->pembiayaan->catatan ?? '';

        if ($this->pembiayaan->kategori_pembiayaan === 'barang' && !empty($this->pembiayaan->rincian_barang)) {
            $this->items_barang = $this->pembiayaan->rincian_barang;
        } else {
            $this->nominal_pembiayaan = $this->pembiayaan->nominal_pengajuan;
        }

        if ($this->pembiayaan->employee) {
            $this->employeeSearch   = $this->pembiayaan->employee->npk . ' - ' . $this->pembiayaan->employee->nama_lengkap;
            $this->selectedEmployee = $this->pembiayaan->employee;
        }
    }

    public function addItemBarang()
    {
        $this->items_barang[] = ['rincian' => '', 'harga' => ''];
    }

    public function removeItemBarang($index)
    {
        unset($this->items_barang[$index]);
        $this->items_barang = array_values($this->items_barang);
    }

    public function getTotalBarang(): float
    {
        return collect($this->items_barang)->sum(fn($i) => (float)($i['harga'] ?? 0));
    }

    #[Computed]
    public function simulasiPembiayaan(): array
    {
        $nominal = $this->kategori_pembiayaan === 'barang'
            ? $this->getTotalBarang()
            : (float) $this->nominal_pembiayaan;
        $tenor = (int) $this->tenor_bulan_pb;
        $margin = (float) $this->margin_persen;

        if ($nominal <= 0 || $tenor <= 0) return [];

        $totalMargin     = $nominal * ($margin / 100) * ($tenor / 12);
        $totalPembiayaan = $nominal + $totalMargin;
        $angsuran        = $totalPembiayaan / $tenor;

        return [
            'nominal'          => $nominal,
            'total_margin'     => $totalMargin,
            'total_pembiayaan' => $totalPembiayaan,
            'angsuran'         => $angsuran,
        ];
    }

    #[Computed]
    public function availableEmployees()
    {
        $query = Employee::query();
        if ($this->employeeSearch && !str_contains($this->employeeSearch, ' - ')) {
            $query->where(function ($q) {
                $q->where('npk', 'like', '%' . $this->employeeSearch . '%')
                  ->orWhere('nama_lengkap', 'like', '%' . $this->employeeSearch . '%');
            });
        }
        return $query->orderBy('nama_lengkap', 'asc')->take(50)->get();
    }

    #[Computed]
    public function bankList()
    {
        $banks = NamaBank::orderBy('nama_bank', 'asc')->pluck('nama_bank')->toArray();
        return empty($banks)
            ? ['BCA', 'BRI', 'BNI', 'BSI', 'BJB', 'BTN', 'Mandiri', 'Bank DKI', 'Bank Muamalat', 'Seabank', 'Permata']
            : $banks;
    }

    public function selectEmployee($id, $label)
    {
        $this->employee_id      = $id;
        $this->employeeSearch   = $label;
        $this->selectedEmployee = Employee::with('koperasiMember')->find($id);

        if ($this->selectedEmployee && $this->selectedEmployee->nama_bank) {
            $this->nama_bank_pb             = $this->selectedEmployee->nama_bank;
            $this->no_rekening_pb           = $this->selectedEmployee->no_rekening;
            $this->nama_pemilik_rekening_pb = $this->selectedEmployee->nama_pemilik_rekening;
            $this->js("Flux.toast({ text: 'Data rekening otomatis terisi dari profil anggota.', variant: 'success' })");
        }
    }

    public function save()
    {
        $isBarang = $this->kategori_pembiayaan === 'barang';

        $this->validate([
            'employee_id'             => 'required|exists:employees,id',
            'kategori_pembiayaan'     => 'required|string',
            'tujuan_pembiayaan'       => 'required|string|max:500',
            'nominal_pembiayaan'      => $isBarang ? 'nullable' : 'required|numeric|min:1',
            'tenor_bulan_pb'          => 'required|integer|min:1|max:120',
            'margin_persen'           => 'required|numeric|min:0|max:100',
            'pencairan_dana_ke'       => 'required|in:pihak_ketiga,anggota',
            'nama_bank_pb'            => 'required|string|max:100',
            'no_rekening_pb'          => 'required|string|max:50',
            'nama_pemilik_rekening_pb'=> 'required|string|max:150',
            'status'                  => 'required|in:diajukan,diproses,ditolak,dibatalkan,berjalan,lunas',
            'alasan_penolakan'        => 'required_if:status,ditolak',
            'nama_pihak_ketiga'       => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:200',
            'no_telp_pihak_ketiga'    => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:20',
            'alamat_pihak_ketiga'     => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:500',
        ], [
            'employee_id.required'       => 'Pilih karyawan terlebih dahulu.',
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi.',
        ]);

        $nominal = $isBarang ? $this->getTotalBarang() : (float) $this->nominal_pembiayaan;
        $tenor   = (int) $this->tenor_bulan_pb;
        $margin  = (float) $this->margin_persen;

        $totalMargin     = $nominal * ($margin / 100) * ($tenor / 12);
        $totalPembiayaan = $nominal + $totalMargin;
        $nomAngsuran     = $totalPembiayaan / $tenor;

        $userId          = auth('web')->user()->id;
        $now             = now();
        $originalStatus  = $this->pembiayaan->status;

        $updateData = [
            'employee_id'            => $this->employee_id,
            'kategori_pembiayaan'    => $this->kategori_pembiayaan,
            'tujuan_pembiayaan'      => $this->tujuan_pembiayaan,
            'rincian_barang'         => $isBarang ? $this->items_barang : null,
            'nominal_pengajuan'      => $nominal,
            'nominal_disetujui'      => $this->nominal_disetujui !== '' ? (float)$this->nominal_disetujui : $nominal,
            'tenor_bulan'            => $tenor,
            'margin_persen'          => $margin,
            'total_margin'           => $totalMargin,
            'total_pembiayaan'       => $totalPembiayaan,
            'nominal_angsuran'       => $nomAngsuran,
            'pencairan_dana_ke'      => $this->pencairan_dana_ke,
            'nama_pihak_ketiga'      => $this->pencairan_dana_ke === 'pihak_ketiga' ? $this->nama_pihak_ketiga : null,
            'no_telp_pihak_ketiga'   => $this->pencairan_dana_ke === 'pihak_ketiga' ? $this->no_telp_pihak_ketiga : null,
            'alamat_pihak_ketiga'    => $this->pencairan_dana_ke === 'pihak_ketiga' ? $this->alamat_pihak_ketiga : null,
            'no_rekening'            => $this->no_rekening_pb,
            'nama_bank'              => $this->nama_bank_pb,
            'nama_pemilik_rekening'  => $this->nama_pemilik_rekening_pb,
            'status'                 => $this->status,
            'catatan'                => $this->catatan ?: null,
        ];

        // Audit logs on status change
        if ($this->status !== $originalStatus) {
            if ($this->status !== 'diajukan' && $this->pembiayaan->diajukan_oleh === null) {
                $updateData['diajukan_oleh'] = $userId;
                $updateData['diajukan_pada'] = $now;
            }
            if (in_array($this->status, ['diproses', 'berjalan', 'lunas']) && $this->pembiayaan->diproses_oleh === null) {
                $updateData['diproses_oleh'] = $userId;
                $updateData['diproses_pada'] = $now;
            }
            if (in_array($this->status, ['berjalan', 'lunas']) && $this->pembiayaan->tanggal_pencairan === null) {
                $updateData['tanggal_pencairan'] = now()->toDateString();
            }
            if ($this->status === 'ditolak') {
                $updateData['ditolak_oleh']     = $userId;
                $updateData['ditolak_pada']     = $now;
                $updateData['alasan_penolakan'] = $this->alasan_penolakan ?: null;
            }
            if ($this->status === 'dibatalkan' && $this->pembiayaan->dibatalkan_oleh === null) {
                $updateData['dibatalkan_oleh'] = $userId;
                $updateData['dibatalkan_pada'] = $now;
            }
        } else {
            if ($this->status === 'ditolak') {
                $updateData['alasan_penolakan'] = $this->alasan_penolakan ?: null;
            }
        }

        $this->pembiayaan->update($updateData);
        $this->js("Flux.toast({ text: 'Data pembiayaan berhasil diubah.', variant: 'success' })");
        return $this->redirect('/admin/pinjaman', navigate: true);
    }
};
?>

<div>
    {{-- PAGE HEADER --}}
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/pinjaman" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Edit Pembiayaan — {{ $pembiayaan->nomor_pengajuan }}</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">Perbarui detail pengajuan pembiayaan syariah anggota.</flux:text>
        </div>
    </div>

    <flux:separator variant="subtle" />

    {{-- AUDIT LOG --}}
    @if($pembiayaan->status !== 'diajukan')
        <div class="mt-5 p-4 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-900/30 text-sm space-y-2">
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-200">Log Riwayat Pembiayaan:</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-xs">
                @if($pembiayaan->diajukan_pada)
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Diajukan Pada:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($pembiayaan->diajukan_pada)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
                @if($pembiayaan->diproses_pada)
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Diproses Pada:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($pembiayaan->diproses_pada)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
                @if($pembiayaan->tanggal_pencairan)
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Tanggal Pencairan:</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ \Carbon\Carbon::parse($pembiayaan->tanggal_pencairan)->format('d/m/Y') }}</span>
                    </div>
                @endif
                @if($pembiayaan->ditolak_pada)
                    <div class="flex justify-between">
                        <span class="text-red-500">Ditolak Pada:</span>
                        <span class="font-medium text-red-600 dark:text-red-400">{{ \Carbon\Carbon::parse($pembiayaan->ditolak_pada)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <flux:card class="mt-5">
        <form wire:submit="save" class="space-y-6">

            {{-- SECTION 1: KARYAWAN --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">1. Karyawan Terkait</flux:heading>

                <div x-data="{ open: false }" class="relative max-w-xl">
                    <flux:field>
                        <flux:label>Cari Karyawan (Ketik NPK atau Nama)</flux:label>
                        <flux:input
                            type="text"
                            placeholder="Ketik NPK atau Nama Karyawan..."
                            wire:model.live="employeeSearch"
                            x-on:focus="open = true"
                            x-on:click="open = true"
                            x-on:keydown.enter.prevent=""
                            icon="magnifying-glass"
                        />
                        <div
                            x-show="open"
                            x-on:click.outside="open = false"
                            class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700"
                            style="display: none;"
                            x-transition
                        >
                            @forelse($this->availableEmployees as $emp)
                                <div
                                    x-on:click="open = false; $wire.selectEmployee({{ $emp->id }}, '{{ $emp->npk }} - {{ $emp->nama_lengkap }}')"
                                    class="px-4 py-2.5 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer text-sm text-zinc-900 dark:text-zinc-100 flex justify-between"
                                >
                                    <span class="font-medium">{{ $emp->nama_lengkap }}</span>
                                    <span class="font-mono text-zinc-400 text-xs">{{ $emp->npk }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-2.5 text-sm text-zinc-500">Karyawan tidak ditemukan.</div>
                            @endforelse
                        </div>
                        <flux:error name="employee_id" />
                    </flux:field>
                </div>

                @if($selectedEmployee)
                    <div class="mt-4 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50/50 dark:bg-zinc-800/30 max-w-2xl grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-zinc-400 block text-xs">NPK</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->npk }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-xs">Nama Lengkap</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->nama_lengkap }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-xs">Seksi / Departemen</span>
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $selectedEmployee->seksi ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-zinc-400 block text-xs">Status Koperasi</span>
                            @if($selectedEmployee->koperasiMember)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Anggota Koperasi
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>Bukan Anggota
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- SECTION 2: KATEGORI & TUJUAN --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">2. Kategori & Tujuan Pembiayaan</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label>Kategori Pembiayaan</flux:label>
                        <flux:select wire:model.live="kategori_pembiayaan">
                            <flux:select.option value="barang">Pembelian Barang / Elektronik</flux:select.option>
                            <flux:select.option value="kendaraan">Kendaraan</flux:select.option>
                            <flux:select.option value="renovasi">Renovasi Rumah</flux:select.option>
                            <flux:select.option value="pendidikan">Pendidikan</flux:select.option>
                            <flux:select.option value="kesehatan">Kesehatan</flux:select.option>
                            <flux:select.option value="lainnya">Lainnya</flux:select.option>
                        </flux:select>
                        <flux:error name="kategori_pembiayaan" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Tujuan Pembiayaan</flux:label>
                        <flux:textarea wire:model="tujuan_pembiayaan" rows="2" placeholder="Jelaskan tujuan penggunaan pembiayaan..." />
                        <flux:error name="tujuan_pembiayaan" />
                    </flux:field>
                </div>

                @if($kategori_pembiayaan === 'barang')
                    <div class="mt-5">
                        <div class="flex items-center justify-between mb-3">
                            <flux:label>Rincian Barang yang Dibeli</flux:label>
                            <flux:button type="button" wire:click="addItemBarang" size="sm" variant="outline" icon="plus">Tambah Item</flux:button>
                        </div>
                        <div class="space-y-2">
                            @foreach($items_barang as $index => $item)
                                <div class="flex gap-3 items-start">
                                    <div class="flex-1">
                                        <flux:input type="text" wire:model.live="items_barang.{{ $index }}.rincian" placeholder="Nama / deskripsi barang" />
                                        <flux:error name="items_barang.{{ $index }}.rincian" />
                                    </div>
                                    <div class="w-44">
                                        <flux:input type="number" wire:model.live="items_barang.{{ $index }}.harga" placeholder="Harga (Rp)" />
                                        <flux:error name="items_barang.{{ $index }}.harga" />
                                    </div>
                                    @if(count($items_barang) > 1)
                                        <flux:button type="button" wire:click="removeItemBarang({{ $index }})" variant="ghost" size="sm" icon="trash" class="text-red-500 mt-1" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 text-right">
                            <span class="text-sm text-zinc-500">Total Harga: </span>
                            <span class="font-bold text-zinc-900 dark:text-white">Rp {{ number_format($this->getTotalBarang(), 0, ',', '.') }}</span>
                        </div>
                    </div>
                @else
                    <div class="mt-5 max-w-xs">
                        <flux:field>
                            <flux:label>Nominal Pembiayaan (Rp)</flux:label>
                            <flux:input type="number" wire:model.live="nominal_pembiayaan" placeholder="Contoh: 15000000" />
                            <flux:error name="nominal_pembiayaan" />
                        </flux:field>
                    </div>
                @endif
            </div>

            {{-- SECTION 3: PARAMETER --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">3. Parameter Pembiayaan</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <flux:field>
                        <flux:label>Tenor (Bulan)</flux:label>
                        <flux:input type="number" wire:model.live="tenor_bulan_pb" placeholder="Contoh: 24" min="1" max="120" />
                        <flux:error name="tenor_bulan_pb" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Margin (% per Tahun)</flux:label>
                        <flux:input type="number" wire:model.live="margin_persen" step="0.1" placeholder="Contoh: 8.5" />
                        <flux:error name="margin_persen" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Nominal Disetujui (Rp) <span class="text-xs text-zinc-400 font-normal">(opsional)</span></flux:label>
                        <flux:input type="number" wire:model="nominal_disetujui" placeholder="Jika berbeda dari nominal pengajuan" />
                    </flux:field>
                </div>

                @if(!empty($this->simulasiPembiayaan))
                    @php $sim = $this->simulasiPembiayaan; @endphp
                    <div class="mt-5 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
                        <flux:heading size="sm" class="mb-3 text-emerald-800 dark:text-emerald-300">Simulasi Angsuran</flux:heading>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-zinc-500 block text-xs">Nominal</span>
                                <span class="font-bold text-zinc-900 dark:text-white">Rp {{ number_format($sim['nominal'], 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 block text-xs">Total Margin</span>
                                <span class="font-bold text-zinc-900 dark:text-white">Rp {{ number_format($sim['total_margin'], 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 block text-xs">Total Pembiayaan</span>
                                <span class="font-bold text-zinc-900 dark:text-white">Rp {{ number_format($sim['total_pembiayaan'], 0, ',', '.') }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-500 block text-xs">Angsuran / Bulan</span>
                                <span class="font-bold text-emerald-700 dark:text-emerald-400 text-lg">Rp {{ number_format($sim['angsuran'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- SECTION 4: PENCAIRAN --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">4. Informasi Pencairan Dana</flux:heading>

                <flux:field class="mb-5">
                    <flux:label>Pencairan Dana Ke</flux:label>
                    <flux:radio.group wire:model.live="pencairan_dana_ke" class="flex gap-6">
                        <flux:radio value="anggota" label="Rekening Anggota" />
                        <flux:radio value="pihak_ketiga" label="Pihak Ketiga (Vendor / Toko)" />
                    </flux:radio.group>
                    <flux:error name="pencairan_dana_ke" />
                </flux:field>

                @if($pencairan_dana_ke === 'pihak_ketiga')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-5 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-700">
                        <flux:field>
                            <flux:label>Nama Pihak Ketiga / Toko</flux:label>
                            <flux:input type="text" wire:model="nama_pihak_ketiga" placeholder="Nama vendor / toko" />
                            <flux:error name="nama_pihak_ketiga" />
                        </flux:field>
                        <flux:field>
                            <flux:label>No. Telp Pihak Ketiga</flux:label>
                            <flux:input type="text" wire:model="no_telp_pihak_ketiga" placeholder="0812..." />
                            <flux:error name="no_telp_pihak_ketiga" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Alamat Pihak Ketiga</flux:label>
                            <flux:input type="text" wire:model="alamat_pihak_ketiga" placeholder="Alamat vendor / toko" />
                            <flux:error name="alamat_pihak_ketiga" />
                        </flux:field>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <flux:field>
                        <flux:label>Bank Tujuan</flux:label>
                        <flux:select wire:model="nama_bank_pb" placeholder="Pilih Bank...">
                            @foreach($this->bankList as $bank)
                                <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="nama_bank_pb" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Nomor Rekening</flux:label>
                        <flux:input type="text" wire:model="no_rekening_pb" placeholder="Contoh: 7012398412" />
                        <flux:error name="no_rekening_pb" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Nama Pemilik Rekening</flux:label>
                        <flux:input type="text" wire:model="nama_pemilik_rekening_pb" placeholder="Sesuai buku tabungan" />
                        <flux:error name="nama_pemilik_rekening_pb" />
                    </flux:field>
                </div>
            </div>

            {{-- SECTION 5: STATUS --}}
            <div>
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">5. Status & Catatan</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:field>
                        <flux:label>Status Pembiayaan</flux:label>
                        <flux:select wire:model.live="status">
                            <flux:select.option value="diajukan">Diajukan</flux:select.option>
                            <flux:select.option value="diproses">Diproses</flux:select.option>
                            <flux:select.option value="ditolak">Ditolak</flux:select.option>
                            <flux:select.option value="dibatalkan">Dibatalkan</flux:select.option>
                            <flux:select.option value="berjalan">Berjalan</flux:select.option>
                            <flux:select.option value="lunas">Lunas</flux:select.option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>

                    @if($status === 'ditolak')
                        <flux:field>
                            <flux:label>Alasan Penolakan <span class="text-red-500">*</span></flux:label>
                            <flux:input type="text" wire:model="alasan_penolakan" placeholder="Sebutkan alasan penolakan..." />
                            <flux:error name="alasan_penolakan" />
                        </flux:field>
                    @endif

                    <div class="md:col-span-2">
                        <flux:field>
                            <flux:label>Catatan Internal</flux:label>
                            <flux:textarea wire:model="catatan" rows="3" placeholder="Catatan tambahan jika diperlukan..." />
                        </flux:field>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button href="/admin/pinjaman" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary" icon="check">Simpan Perubahan</flux:button>
            </div>

        </form>
    </flux:card>
</div>

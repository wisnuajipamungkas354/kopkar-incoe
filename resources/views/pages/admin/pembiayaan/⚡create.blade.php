<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Employee;
use App\Models\Pembiayaan;
use App\Models\NamaBank;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::admin', ['title' => 'Tambah Pembiayaan'])] class extends Component
{
    // ── Pilih Karyawan ──
    public $employee_id      = '';
    public $employeeSearch   = '';
    public $selectedEmployee = null;

    // ── PEMBIAYAAN FIELDS ──
    public $kategori_pembiayaan      = 'barang';
    public $tujuan_pembiayaan        = '';
    public $items_barang             = [['rincian' => '', 'harga' => '']];
    public $nominal_pembiayaan       = '';   // untuk kategori non-barang
    public $tenor_bulan_pb           = '';
    public $margin_persen            = 8.5;
    public $pencairan_dana_ke        = 'pihak_ketiga'; // Defaulted
    public $nama_pihak_ketiga        = '';
    public $no_telp_pihak_ketiga     = '';
    public $alamat_pihak_ketiga      = '';
    public $nama_bank_pb             = '';
    public $no_rekening_pb           = '';
    public $nama_pemilik_rekening_pb = '';
    public $status_pb                = 'diajukan';
    public $alasan_penolakan_pb      = '';
    public $catatan_pb               = '';

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

        $totalMargin    = $nominal * ($margin / 100) * ($tenor / 12);
        $totalPembiayaan = $nominal + $totalMargin;
        $angsuran        = $totalPembiayaan / $tenor;

        return [
            'nominal'         => $nominal,
            'total_margin'    => $totalMargin,
            'total_pembiayaan'=> $totalPembiayaan,
            'angsuran'        => $angsuran,
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

        if ($this->selectedEmployee) {
            if ($this->selectedEmployee->nama_bank) {
                $this->nama_bank_pb             = $this->selectedEmployee->nama_bank;
                $this->no_rekening_pb           = $this->selectedEmployee->no_rekening;
                $this->nama_pemilik_rekening_pb = $this->selectedEmployee->nama_pemilik_rekening;
                $this->js("Flux.toast({ text: 'Data rekening terisi otomatis dari profil.', variant: 'success' })");
            } else {
                $this->nama_pemilik_rekening_pb = $this->selectedEmployee->nama_lengkap;
            }
        }
    }

    public function simpanPembiayaan()
    {
        $isBarang = $this->kategori_pembiayaan === 'barang';

        $this->validate([
            'employee_id'             => 'required|exists:employees,id',
            'kategori_pembiayaan'     => 'required|string',
            'tujuan_pembiayaan'       => 'required|string|max:500',
            'nominal_pembiayaan'      => $isBarang ? 'nullable' : 'required|numeric|min:1',
            'items_barang'            => $isBarang ? 'required|array|min:1' : 'nullable',
            'items_barang.*.rincian'  => $isBarang ? 'required|string|max:255' : 'nullable',
            'items_barang.*.harga'    => $isBarang ? 'required|numeric|min:1' : 'nullable',
            'tenor_bulan_pb'          => 'required|integer|min:1|max:120',
            'margin_persen'           => 'required|numeric|min:0|max:100',
            'pencairan_dana_ke'       => 'required|in:pihak_ketiga,anggota',
            'nama_bank_pb'            => 'required|string|max:100',
            'no_rekening_pb'          => 'required|string|max:50',
            'nama_pemilik_rekening_pb'=> 'required|string|max:150',
            'status_pb'               => 'required|in:diajukan,diproses,ditolak,dibatalkan,berjalan,lunas',
            'alasan_penolakan_pb'     => 'required_if:status_pb,ditolak',
            'nama_pihak_ketiga'       => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:200',
            'no_telp_pihak_ketiga'    => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:20',
            'alamat_pihak_ketiga'     => 'required_if:pencairan_dana_ke,pihak_ketiga|nullable|string|max:500',
        ], [
            'employee_id.required'             => 'Pilih karyawan terlebih dahulu.',
            'alasan_penolakan_pb.required_if'  => 'Alasan penolakan wajib diisi jika status Ditolak.',
        ]);

        $nominal = $isBarang ? $this->getTotalBarang() : (float)$this->nominal_pembiayaan;
        if ($nominal <= 0) {
            if ($isBarang) {
                $this->addError('items_barang', 'Total rincian barang tidak boleh 0.');
            } else {
                $this->addError('nominal_pembiayaan', 'Nominal tidak boleh 0.');
            }
            return;
        }

        $tenor = (int)$this->tenor_bulan_pb;
        $marginPersen = (float)$this->margin_persen;

        $totalMargin = $nominal * ($marginPersen / 100) * ($tenor / 12);
        $totalPembiayaan = $nominal + $totalMargin;
        $nomAngsuran = $totalPembiayaan / $tenor;

        DB::transaction(function () use ($nominal, $totalMargin, $totalPembiayaan, $nomAngsuran, $tenor, $marginPersen, $isBarang) {
            $pembiayaan = Pembiayaan::create([
                'nomor_pengajuan'        => 'PB-'.date('YmdHis').'-'.rand(1000,9999),
                'employee_id'            => $this->employee_id,
                'kategori_pembiayaan'    => $this->kategori_pembiayaan,
                'tujuan_pembiayaan'      => $this->tujuan_pembiayaan,
                'rincian_barang'         => $isBarang ? $this->items_barang : null,
                'nominal_pengajuan'      => $nominal,
                'nominal_disetujui'      => $nominal,
                'tenor_bulan'            => $tenor,
                'margin_persen'          => $marginPersen,
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
                'status'                 => $this->status_pb,
                'alasan_penolakan'       => $this->status_pb === 'ditolak' ? $this->alasan_penolakan_pb : null,
                'catatan'                => $this->catatan_pb ?: null,
                'diajukan_oleh'          => auth('web')->user()->id,
                'diajukan_pada'          => now(),
            ]);

            if ($this->status_pb === 'berjalan') {
                $pembiayaan->update([
                    'diproses_oleh'     => auth('web')->user()->id,
                    'diproses_pada'     => now(),
                    'tanggal_pencairan' => now()->toDateString(),
                ]);
            } elseif ($this->status_pb === 'diproses') {
                $pembiayaan->update([
                    'diproses_oleh' => auth('web')->user()->id,
                    'diproses_pada' => now(),
                ]);
            } elseif ($this->status_pb === 'ditolak') {
                $pembiayaan->update([
                    'ditolak_oleh' => auth('web')->user()->id,
                    'ditolak_pada' => now(),
                ]);
            }
        });

        $this->js("Flux.toast({ heading: 'Berhasil', text: 'Data pembiayaan baru ditambahkan.', variant: 'success' })");
        return redirect()->to('/admin/pembiayaan');
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button variant="subtle" icon="arrow-left" href="/admin/pembiayaan" wire:navigate>Kembali</flux:button>
        <div>
            <flux:heading size="xl" level="1">Tambah Pembiayaan</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">Isi form di bawah untuk membuat data pembiayaan syariah anggota.</flux:text>
        </div>
    </div>

    <flux:separator variant="subtle" class="mb-6" />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Kiri: Cari Karyawan --}}
        <div class="md:col-span-1">
            <flux:card>
                <flux:heading size="lg" class="mb-4">Pilih Karyawan</flux:heading>
                <div x-data="{ open: false }" class="relative">
                    <flux:field>
                        <flux:label>Cari NPK atau Nama</flux:label>
                        <flux:input 
                            type="text" 
                            placeholder="Ketik NPK atau Nama..." 
                            wire:model.live.debounce.300ms="employeeSearch"
                            x-on:focus="open = true"
                            x-on:click="open = true"
                            x-on:keydown.escape="open = false"
                            icon="magnifying-glass"
                        />
                        
                        <div x-show="open" x-on:click.outside="open = false" 
                             class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
                             style="display:none;">
                            @forelse($this->availableEmployees as $emp)
                                <div x-on:click="open = false; $wire.selectEmployee({{ $emp->id }}, '{{ $emp->npk }} - {{ $emp->nama_lengkap }}')"
                                     class="px-4 py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer text-sm border-b border-zinc-50 dark:border-zinc-700/50 last:border-0 flex justify-between items-center">
                                    <div>
                                        <span class="font-medium block">{{ $emp->nama_lengkap }}</span>
                                        <span class="text-xs text-zinc-400">{{ $emp->npk }} • {{ $emp->seksi ?? '-' }}</span>
                                    </div>
                                    @if($emp->koperasiMember)
                                        <span class="w-2 h-2 rounded-full bg-green-500" title="Anggota Koperasi"></span>
                                    @endif
                                </div>
                            @empty
                                <div class="px-4 py-3 text-sm text-zinc-500 text-center">Karyawan tidak ditemukan.</div>
                            @endforelse
                        </div>
                        <flux:error name="employee_id" />
                    </flux:field>
                </div>

                @if($selectedEmployee)
                    <div class="mt-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-800 flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-200 dark:bg-emerald-800 flex items-center justify-center text-xl font-bold text-emerald-700 dark:text-emerald-300 shrink-0">
                            {{ substr($selectedEmployee->nama_lengkap, 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-zinc-900 dark:text-zinc-100">{{ $selectedEmployee->nama_lengkap }}</div>
                            <div class="text-xs text-zinc-500 mt-1">NPK: <span class="font-mono">{{ $selectedEmployee->npk }}</span></div>
                            <div class="text-xs text-zinc-500">Seksi: {{ $selectedEmployee->seksi ?? '-' }}</div>
                            @if($selectedEmployee->koperasiMember)
                                <div class="mt-2 inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">Terdaftar Koperasi</div>
                            @else
                                <div class="mt-2 inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">Bukan Anggota</div>
                            @endif

                            <div class="mt-4 pt-3 border-t border-emerald-200 dark:border-emerald-800 flex justify-between gap-4">
                                <div class="flex-1">
                                    <span class="text-[10px] uppercase font-bold text-zinc-500 tracking-wider">Sisa Tagihan</span>
                                    <div class="text-sm font-bold text-red-600 dark:text-red-400 mt-0.5">Rp {{ number_format($selectedEmployee->sisa_tagihan, 0, ',', '.') }}</div>
                                </div>
                                <div class="flex-1 text-right">
                                    <span class="text-[10px] uppercase font-bold text-zinc-500 tracking-wider">Sisa Plafon</span>
                                    <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">Rp {{ number_format($selectedEmployee->sisa_plafon, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Kanan: Form Pembiayaan --}}
        <div class="md:col-span-2">
            <flux:card>
                <form wire:submit="simpanPembiayaan" class="space-y-6">
                    <flux:heading size="lg" class="mb-4">Informasi Pembiayaan</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:field>
                            <flux:label>Kategori</flux:label>
                            <flux:select wire:model.live="kategori_pembiayaan">
                                <flux:select.option value="barang">Pembelian Barang</flux:select.option>
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
                            <flux:input type="text" wire:model="tujuan_pembiayaan" placeholder="Sebutkan tujuan..." />
                            <flux:error name="tujuan_pembiayaan" />
                        </flux:field>
                    </div>

                    @if($kategori_pembiayaan === 'barang')
                        <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between mb-3">
                                <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Rincian Barang</flux:heading>
                                <flux:button size="sm" variant="outline" icon="plus" wire:click="addItemBarang">Tambah</flux:button>
                            </div>
                            
                            @foreach($items_barang as $index => $item)
                                <div class="mb-3">
                                    <div class="flex gap-2">
                                        <flux:input type="text" wire:model.live="items_barang.{{ $index }}.rincian" placeholder="Nama Barang" class="flex-1" />
                                        <flux:input type="number" wire:model.live="items_barang.{{ $index }}.harga" placeholder="Harga (Rp)" class="w-40" />
                                        @if(count($items_barang) > 1)
                                            <flux:button variant="subtle" icon="trash" class="text-red-500" wire:click="removeItemBarang({{ $index }})" />
                                        @endif
                                    </div>
                                    <div class="flex gap-2 mt-1">
                                        <div class="flex-1"><flux:error name="items_barang.{{ $index }}.rincian" /></div>
                                        <div class="w-40"><flux:error name="items_barang.{{ $index }}.harga" /></div>
                                        @if(count($items_barang) > 1)<div class="w-10"></div>@endif
                                    </div>
                                </div>
                            @endforeach
                            <div class="mt-3 text-right">
                                <span class="text-sm text-zinc-500">Total Harga: </span>
                                <span class="font-bold text-lg text-zinc-900 dark:text-zinc-100">Rp {{ number_format($this->getTotalBarang(), 0, ',', '.') }}</span>
                            </div>
                            <flux:error name="items_barang" />
                        </div>
                    @else
                        <flux:field>
                            <flux:label>Nominal Pembiayaan (Rp)</flux:label>
                            <flux:input type="number" wire:model.live="nominal_pembiayaan" placeholder="Misal: 15000000" />
                            <flux:error name="nominal_pembiayaan" />
                        </flux:field>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:field>
                            <flux:label>Tenor (Bulan)</flux:label>
                            <flux:input type="number" wire:model.live="tenor_bulan_pb" placeholder="Maks: 120" />
                            <flux:error name="tenor_bulan_pb" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Margin (% / Tahun)</flux:label>
                            <flux:input type="number" step="0.1" wire:model.live="margin_persen" placeholder="8.5" />
                            <flux:error name="margin_persen" />
                        </flux:field>
                    </div>

                    @php $sim = $this->simulasiPembiayaan; @endphp
                    @if(!empty($sim))
                        <div class="bg-gradient-to-tr from-emerald-500/5 to-teal-500/5 border border-emerald-100 dark:border-emerald-900/30 rounded-xl p-4">
                            <flux:heading size="sm" class="text-emerald-800 dark:text-emerald-300 mb-3">Simulasi Pembiayaan</flux:heading>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between"><span class="text-zinc-500">Nominal Pokok</span><span class="font-medium">Rp {{ number_format($sim['nominal'], 0, ',', '.') }}</span></div>
                                <div class="flex justify-between"><span class="text-zinc-500">Total Margin ({{ $margin_persen }}% p.a)</span><span class="font-medium">Rp {{ number_format($sim['total_margin'], 0, ',', '.') }}</span></div>
                                <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-2"><span class="text-zinc-500">Total Pembiayaan</span><span class="font-bold text-emerald-700 dark:text-emerald-400">Rp {{ number_format($sim['total_pembiayaan'], 0, ',', '.') }}</span></div>
                                <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-2"><span class="text-zinc-500">Estimasi Angsuran / Bulan</span><span class="font-bold text-emerald-700 dark:text-emerald-400 text-lg">Rp {{ number_format($sim['angsuran'], 0, ',', '.') }}</span></div>
                            </div>
                        </div>
                    @endif

                    <flux:separator variant="subtle" />
                    <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Pencairan Dana ke Pihak Ketiga</flux:heading>
                    
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-200 dark:border-zinc-700 rounded-lg space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>Nama Pihak Ketiga</flux:label>
                                <flux:input type="text" wire:model="nama_pihak_ketiga" placeholder="Toko/Vendor/Dealer..." />
                                <flux:error name="nama_pihak_ketiga" />
                            </flux:field>
                            <flux:field>
                                <flux:label>No. Telepon Pihak Ketiga</flux:label>
                                <flux:input type="text" wire:model="no_telp_pihak_ketiga" placeholder="08..." />
                                <flux:error name="no_telp_pihak_ketiga" />
                            </flux:field>
                        </div>
                        <flux:field>
                            <flux:label>Alamat Pihak Ketiga</flux:label>
                            <flux:input type="text" wire:model="alamat_pihak_ketiga" placeholder="Alamat lengkap..." />
                            <flux:error name="alamat_pihak_ketiga" />
                        </flux:field>
                    </div>

                    <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400 mt-4">Rekening Bank Tujuan</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <flux:field>
                            <flux:label>Nama Bank</flux:label>
                            <flux:select wire:model="nama_bank_pb" placeholder="Pilih bank...">
                                @foreach($this->bankList as $bank)
                                    <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="nama_bank_pb" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Nomor Rekening</flux:label>
                            <flux:input type="text" wire:model="no_rekening_pb" placeholder="1234567890" />
                            <flux:error name="no_rekening_pb" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Atas Nama</flux:label>
                            <flux:input type="text" wire:model="nama_pemilik_rekening_pb" />
                            <flux:error name="nama_pemilik_rekening_pb" />
                        </flux:field>
                    </div>

                    <flux:separator variant="subtle" />
                    <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Status & Catatan Admin</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:field>
                            <flux:label>Status Pengajuan</flux:label>
                            <flux:select wire:model.live="status_pb">
                                <flux:select.option value="diajukan">Diajukan</flux:select.option>
                                <flux:select.option value="diproses">Diproses</flux:select.option>
                                <flux:select.option value="berjalan">Disetujui / Berjalan</flux:select.option>
                                <flux:select.option value="ditolak">Ditolak</flux:select.option>
                            </flux:select>
                            <flux:error name="status_pb" />
                            @if($status_pb === 'berjalan')
                                <flux:description class="text-emerald-600 dark:text-emerald-400">Memilih status ini akan menganggap dana sudah cair.</flux:description>
                            @endif
                        </flux:field>
                        
                        <flux:field class="{{ $status_pb === 'ditolak' ? 'block' : 'hidden' }}">
                            <flux:label>Alasan Penolakan</flux:label>
                            <flux:textarea wire:model="alasan_penolakan_pb" rows="2" placeholder="Tulis alasan menolak..." />
                            <flux:error name="alasan_penolakan_pb" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Catatan Internal <span class="text-zinc-400 font-normal">(opsional)</span></flux:label>
                        <flux:textarea wire:model="catatan_pb" rows="2" placeholder="Catatan untuk pengurus koperasi (tidak dilihat anggota)..." />
                    </flux:field>

                    <div class="flex justify-end pt-4">
                        <flux:button type="submit" variant="primary" icon="check">Simpan Data Pembiayaan</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>
</div>

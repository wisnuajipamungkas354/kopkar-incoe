<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use App\Models\Employee;
use App\Models\Pinjaman;
use App\Models\NamaBank;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::admin', ['title' => 'Tambah Pinjaman'])] class extends Component
{
    // ── Pilih Karyawan ──
    public $employee_id      = '';
    public $employeeSearch   = '';
    public $selectedEmployee = null;

    // ── PINJAMAN FIELDS ──
    public $jenis_pinjaman          = 'qard';
    public $nominal_pengajuan_p     = '';
    public $nominal_disetujui_p     = '';
    public $tenor_bulan_p           = 12;
    public $nama_bank_p             = '';
    public $no_rekening_p           = '';
    public $nama_pemilik_rekening_p = '';
    public $status_p                = 'diajukan';
    public $alasan_penolakan_p      = '';
    public $catatan_p               = '';

    public function updatedJenisPinjaman($value)
    {
        $this->tenor_bulan_p = $value === 'bon' ? 1 : 12;
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
                $this->nama_bank_p             = $this->selectedEmployee->nama_bank;
                $this->no_rekening_p           = $this->selectedEmployee->no_rekening;
                $this->nama_pemilik_rekening_p = $this->selectedEmployee->nama_pemilik_rekening;
                $this->js("Flux.toast({ text: 'Data rekening terisi otomatis dari profil.', variant: 'success' })");
            } else {
                $this->nama_pemilik_rekening_p  = $this->selectedEmployee->nama_lengkap;
            }
        }
    }

    public function simpanPinjaman()
    {
        $this->validate([
            'employee_id'             => 'required|exists:employees,id',
            'jenis_pinjaman'          => 'required|in:qard,bon',
            'nominal_pengajuan_p'     => 'required|numeric|min:1',
            'tenor_bulan_p'           => 'required|integer|min:1|max:120',
            'nama_bank_p'             => 'required|string|max:100',
            'no_rekening_p'           => 'required|string|max:50',
            'nama_pemilik_rekening_p' => 'required|string|max:150',
            'status_p'                => 'required|in:diajukan,diproses,ditolak,dibatalkan,berjalan,lunas',
            'alasan_penolakan_p'      => 'required_if:status_p,ditolak',
        ], [
            'employee_id.required'            => 'Pilih karyawan terlebih dahulu.',
            'alasan_penolakan_p.required_if'  => 'Alasan penolakan wajib diisi jika status Ditolak.',
        ]);

        if ($this->jenis_pinjaman === 'bon' && $this->tenor_bulan_p != 1) {
            $this->addError('tenor_bulan_p', 'Tenor Bon Sementara harus 1 bulan.');
            return;
        }

        $nomDisetujui = $this->nominal_disetujui_p !== '' ? (float)$this->nominal_disetujui_p : (float)$this->nominal_pengajuan_p;
        $nomAngsuran  = $nomDisetujui / (int)$this->tenor_bulan_p;

        DB::transaction(function () use ($nomDisetujui, $nomAngsuran) {
            $pinjaman = Pinjaman::create([
                'nomor_pengajuan'       => 'PJ-'.date('YmdHis').'-'.rand(1000,9999),
                'employee_id'           => $this->employee_id,
                'jenis_pinjaman'        => $this->jenis_pinjaman,
                'nominal_pengajuan'     => $this->nominal_pengajuan_p,
                'nominal_disetujui'     => $nomDisetujui,
                'tenor_bulan'           => $this->tenor_bulan_p,
                'nominal_angsuran'      => $nomAngsuran,
                'no_rekening'           => $this->no_rekening_p,
                'nama_bank'             => $this->nama_bank_p,
                'nama_pemilik_rekening' => $this->nama_pemilik_rekening_p,
                'status'                => $this->status_p,
                'alasan_penolakan'      => $this->status_p === 'ditolak' ? $this->alasan_penolakan_p : null,
                'catatan'               => $this->catatan_p ?: null,
                'diajukan_oleh'         => auth('web')->user()->id,
                'diajukan_pada'         => now(),
            ]);

            if ($this->status_p === 'berjalan') {
                $pinjaman->update([
                    'diproses_oleh'     => auth('web')->user()->id,
                    'diproses_pada'     => now(),
                    'tanggal_pencairan' => now()->toDateString(),
                ]);
            } elseif ($this->status_p === 'diproses') {
                $pinjaman->update([
                    'diproses_oleh' => auth('web')->user()->id,
                    'diproses_pada' => now(),
                ]);
            } elseif ($this->status_p === 'ditolak') {
                $pinjaman->update([
                    'ditolak_oleh' => auth('web')->user()->id,
                    'ditolak_pada' => now(),
                ]);
            }
        });

        $this->js("Flux.toast({ heading: 'Berhasil', text: 'Data pinjaman baru ditambahkan.', variant: 'success' })");
        return redirect()->to('/admin/pinjaman');
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button variant="subtle" icon="arrow-left" href="/admin/pinjaman" wire:navigate>Kembali</flux:button>
        <div>
            <flux:heading size="xl" level="1">Tambah Pinjaman</flux:heading>
            <flux:text class="mt-1 text-base text-zinc-500">Isi form di bawah untuk membuat data pinjaman anggota.</flux:text>
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

        {{-- Kanan: Form Pinjaman --}}
        <div class="md:col-span-2">
            <flux:card>
                <form wire:submit="simpanPinjaman" class="space-y-6">
                    <flux:heading size="lg" class="mb-4">Informasi Pinjaman</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:field>
                            <flux:label>Jenis Pinjaman</flux:label>
                            <flux:select wire:model.live="jenis_pinjaman">
                                <flux:select.option value="qard">Qard Hasan (Bunga 0%)</flux:select.option>
                                <flux:select.option value="bon">Bon Sementara (Maks 1 Bulan)</flux:select.option>
                            </flux:select>
                            <flux:error name="jenis_pinjaman" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Tenor (Bulan)</flux:label>
                            <flux:input type="number" wire:model.live="tenor_bulan_p" placeholder="Misal: 10" :disabled="$jenis_pinjaman === 'bon'" />
                            <flux:error name="tenor_bulan_p" />
                            @if($jenis_pinjaman === 'bon')
                                <flux:description class="text-amber-600 dark:text-amber-400">Dikunci 1 bulan untuk Bon.</flux:description>
                            @endif
                        </flux:field>

                        <flux:field>
                            <flux:label>Nominal Pengajuan (Rp)</flux:label>
                            <flux:input type="number" wire:model="nominal_pengajuan_p" placeholder="Misal: 3000000" />
                            <flux:error name="nominal_pengajuan_p" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label>Nominal Disetujui (Rp) <span class="text-zinc-400 font-normal">(opsional)</span></flux:label>
                            <flux:input type="number" wire:model="nominal_disetujui_p" placeholder="Isi jika berbeda dengan pengajuan" />
                            <flux:error name="nominal_disetujui_p" />
                        </flux:field>
                    </div>

                    <flux:separator variant="subtle" />

                    <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Rekening Pencairan Dana</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <flux:field>
                            <flux:label>Nama Bank</flux:label>
                            <flux:select wire:model="nama_bank_p" placeholder="Pilih bank...">
                                @foreach($this->bankList as $bank)
                                    <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="nama_bank_p" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Nomor Rekening</flux:label>
                            <flux:input type="text" wire:model="no_rekening_p" placeholder="Misal: 1234567890" />
                            <flux:error name="no_rekening_p" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Atas Nama</flux:label>
                            <flux:input type="text" wire:model="nama_pemilik_rekening_p" />
                            <flux:error name="nama_pemilik_rekening_p" />
                        </flux:field>
                    </div>

                    <flux:separator variant="subtle" />
                    <flux:heading size="sm" class="text-zinc-600 dark:text-zinc-400">Status & Catatan Admin</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:field>
                            <flux:label>Status Pengajuan</flux:label>
                            <flux:select wire:model.live="status_p">
                                <flux:select.option value="diajukan">Diajukan</flux:select.option>
                                <flux:select.option value="diproses">Diproses</flux:select.option>
                                <flux:select.option value="berjalan">Disetujui / Berjalan</flux:select.option>
                                <flux:select.option value="ditolak">Ditolak</flux:select.option>
                            </flux:select>
                            <flux:error name="status_p" />
                            @if($status_p === 'berjalan')
                                <flux:description class="text-emerald-600 dark:text-emerald-400">Memilih status ini akan menganggap dana sudah cair.</flux:description>
                            @endif
                        </flux:field>
                        
                        <flux:field class="{{ $status_p === 'ditolak' ? 'block' : 'hidden' }}">
                            <flux:label>Alasan Penolakan</flux:label>
                            <flux:textarea wire:model="alasan_penolakan_p" rows="2" placeholder="Tulis alasan menolak..." />
                            <flux:error name="alasan_penolakan_p" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>Catatan Internal <span class="text-zinc-400 font-normal">(opsional)</span></flux:label>
                        <flux:textarea wire:model="catatan_p" rows="2" placeholder="Catatan untuk pengurus koperasi (tidak dilihat anggota)..." />
                    </flux:field>

                    <div class="flex justify-end pt-4">
                        <flux:button type="submit" variant="primary" icon="check">Simpan Data Pinjaman</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </div>
</div>

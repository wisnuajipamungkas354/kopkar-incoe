<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Computed;
use App\Models\Employee;
use App\Models\Pinjaman;
use App\Models\NamaBank;

new #[Layout('layouts::admin')] class extends Component
{
    public Pinjaman $pinjaman;

    // Search Karyawan
    public $employee_id = '';
    public $employeeSearch = '';
    public $selectedEmployee = null;

    // Form fields
    #[Validate('required|in:qard,bon')]
    public $jenis_pinjaman = 'qard';

    #[Validate('required|numeric|min:1')]
    public $nominal_pengajuan = '';

    public $nominal_disetujui = '';

    #[Validate('required|integer|min:1')]
    public $tenor_bulan = 12;

    #[Validate('required|string|max:100')]
    public $nama_bank = '';

    #[Validate('required|string|max:50')]
    public $no_rekening = '';

    #[Validate('required|string|max:150')]
    public $nama_pemilik_rekening = '';

    #[Validate('required|in:draft,diajukan,diproses,ditolak,dibatalkan,berjalan,lunas')]
    public $status = 'draft';

    public $alasan_penolakan = '';
    public $catatan = '';

    public function mount($id)
    {
        $this->pinjaman = Pinjaman::with('employee')->findOrFail($id);
        $this->employee_id = $this->pinjaman->employee_id;
        
        if ($this->pinjaman->employee) {
            $this->employeeSearch = $this->pinjaman->employee->npk . ' - ' . $this->pinjaman->employee->nama_lengkap;
            $this->selectedEmployee = $this->pinjaman->employee;
        }
        
        $this->jenis_pinjaman = $this->pinjaman->jenis_pinjaman;
        $this->nominal_pengajuan = $this->pinjaman->nominal_pengajuan;
        $this->nominal_disetujui = $this->pinjaman->nominal_disetujui ?? '';
        $this->tenor_bulan = $this->pinjaman->tenor_bulan;
        $this->nama_bank = $this->pinjaman->nama_bank;
        $this->no_rekening = $this->pinjaman->no_rekening;
        $this->nama_pemilik_rekening = $this->pinjaman->nama_pemilik_rekening;
        $this->status = $this->pinjaman->status;
        $this->alasan_penolakan = $this->pinjaman->alasan_penolakan ?? '';
        $this->catatan = $this->pinjaman->catatan ?? '';
    }

    public function updatedJenisPinjaman($value)
    {
        if ($value === 'bon') {
            $this->tenor_bulan = 1;
        } else {
            $this->tenor_bulan = 12;
        }
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
        if (empty($banks)) {
            return ['BCA', 'BRI', 'BNI', 'BSI', 'BJB', 'BTN', 'Mandiri', 'Bank DKI', 'Bank Muamalat', 'Seabank', 'Permata'];
        }
        return $banks;
    }

    public function selectEmployee($id, $label)
    {
        $this->employee_id = $id;
        $this->employeeSearch = $label;
        $this->selectedEmployee = Employee::with('koperasiMember')->find($id);

        if ($this->selectedEmployee) {
            $this->nama_pemilik_rekening = $this->selectedEmployee->nama_lengkap;
            
            if ($this->selectedEmployee->nama_bank) {
                $this->nama_bank = $this->selectedEmployee->nama_bank;
                $this->no_rekening = $this->selectedEmployee->no_rekening;
                $this->nama_pemilik_rekening = $this->selectedEmployee->nama_pemilik_rekening;
                $this->js("Flux.toast({ text: 'Data rekening bank otomatis terisi dari profil anggota Koperasi.', variant: 'success' })");
            } else {
                $this->nama_bank = '';
                $this->no_rekening = '';
            }
        }
    }

    public function save()
    {
        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'alasan_penolakan' => 'required_if:status,ditolak',
        ], [
            'employee_id.required' => 'Pilih karyawan terlebih dahulu.',
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika status Ditolak.',
        ]);

        $this->validate();

        if ($this->jenis_pinjaman === 'bon' && $this->tenor_bulan != 1) {
            $this->addError('tenor_bulan', 'Tenor Bon Sementara harus 1 bulan.');
            return;
        }

        $userId = auth('web')->user()->id;
        $now = now();
        $originalStatus = $this->pinjaman->status;

        $nomDisetujui = $this->nominal_disetujui !== '' && $this->nominal_disetujui !== null 
            ? (float) $this->nominal_disetujui 
            : (float) $this->nominal_pengajuan;

        $nomAngsuran = $nomDisetujui / (int) $this->tenor_bulan;

        $updateData = [
            'employee_id' => $this->employee_id,
            'jenis_pinjaman' => $this->jenis_pinjaman,
            'nominal_pengajuan' => $this->nominal_pengajuan,
            'nominal_disetujui' => $nomDisetujui,
            'tenor_bulan' => $this->tenor_bulan,
            'nominal_angsuran' => $nomAngsuran,
            'no_rekening' => $this->no_rekening,
            'nama_bank' => $this->nama_bank,
            'nama_pemilik_rekening' => $this->nama_pemilik_rekening,
            'status' => $this->status,
            'catatan' => $this->catatan ?: null,
        ];

        // Audit log updates
        if ($this->status !== $originalStatus) {
            if ($this->status !== 'draft' && $this->pinjaman->diajukan_oleh === null) {
                $updateData['diajukan_oleh'] = $userId;
                $updateData['diajukan_pada'] = $now;
            }

            if (in_array($this->status, ['diproses', 'berjalan', 'lunas']) && $this->pinjaman->diproses_oleh === null) {
                $updateData['diproses_oleh'] = $userId;
                $updateData['diproses_pada'] = $now;
            }

            if (in_array($this->status, ['berjalan', 'lunas']) && $this->pinjaman->tanggal_pencairan === null) {
                $updateData['tanggal_pencairan'] = now()->toDateString();
            }

            if ($this->status === 'dibatalkan' && $this->pinjaman->dibatalkan_oleh === null) {
                $updateData['dibatalkan_oleh'] = $userId;
                $updateData['dibatalkan_pada'] = $now;
            }

            if ($this->status === 'ditolak') {
                $updateData['ditolak_oleh']     = $userId;
                $updateData['ditolak_pada']     = $now;
                $updateData['alasan_penolakan'] = $this->alasan_penolakan ?: null;
            }
        } else {
            if ($this->status === 'ditolak') {
                $updateData['alasan_penolakan'] = $this->alasan_penolakan ?: null;
            }
        }

        $this->pinjaman->update($updateData);

        $this->js("Flux.toast({ text: 'Data pinjaman berhasil diubah.', variant: 'success' })");

        return $this->redirect('/admin/pinjaman', navigate: true);
    }
};
?>

<div>
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="/admin/pinjaman" wire:navigate variant="ghost" icon="arrow-left" class="!px-2" />
        <div>
            <flux:heading size="xl" level="1">Edit Pinjaman - {{ $pinjaman->nomor_pengajuan }}</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Perbarui data detail pengajuan pinjaman karyawan di bawah ini.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- INFO LOG PERSETUJUAN -->
    @if($pinjaman->status !== 'draft')
        <div class="mt-6 p-4 border border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-900/30 text-sm space-y-2">
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-200">Log Riwayat Pinjaman:</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                @if($pinjaman->diajukan_pada)
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Diajukan Pada:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($pinjaman->diajukan_pada)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
                @if($pinjaman->diproses_pada)
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Diproses Pada:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($pinjaman->diproses_pada)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
                @if($pinjaman->tanggal_pencairan)
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Tanggal Pencairan:</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ \Carbon\Carbon::parse($pinjaman->tanggal_pencairan)->format('d/m/Y') }}</span>
                    </div>
                @endif
                @if($pinjaman->ditolak_pada)
                    <div class="flex justify-between">
                        <span class="text-zinc-400 text-red-500">Ditolak Pada:</span>
                        <span class="font-medium text-red-600 dark:text-red-400">{{ \Carbon\Carbon::parse($pinjaman->ditolak_pada)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
                @if($pinjaman->diproses_pada)
                    <div class="flex justify-between">
                        <span class="text-zinc-400">Diproses/Dicairkan Pada:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($pinjaman->diproses_pada)->format('d/m/Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <flux:card class="mt-6">
        <form wire:submit="save" class="space-y-6">
            
            <!-- SECTION 1: PILIH KARYAWAN -->
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
                    <!-- Employee Preview Card -->
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
                            <span class="text-zinc-450 block text-xs">Status Koperasi</span>
                            @if($selectedEmployee->koperasiMember)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 dark:text-green-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Anggota Koperasi
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-500 dark:text-zinc-450">
                                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                    Bukan Anggota
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- SECTION 2: DETAIL PINJAMAN -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">2. Informasi & Parameter Pinjaman</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Jenis Pinjaman -->
                    <flux:field>
                        <flux:label>Jenis Pinjaman</flux:label>
                        <flux:select wire:model.live="jenis_pinjaman">
                            <flux:select.option value="qard">Qard Hasan</flux:select.option>
                            <flux:select.option value="bon">Bon Sementara</flux:select.option>
                        </flux:select>
                        <flux:error name="jenis_pinjaman" />
                    </flux:field>

                    <!-- Tenor Bulan -->
                    <flux:field>
                        <flux:label>Tenor Pinjaman (Bulan)</flux:label>
                        <flux:input 
                            type="number" 
                            wire:model="tenor_bulan" 
                            placeholder="Contoh: 12" 
                            :disabled="$jenis_pinjaman === 'bon'"
                        />
                        <flux:error name="tenor_bulan" />
                        @if($jenis_pinjaman === 'bon')
                            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Pinjaman Bon Sementara terkunci pada tenor 1 bulan.</p>
                        @endif
                    </flux:field>

                    <!-- Nominal Pengajuan -->
                    <flux:field>
                        <flux:label>Nominal Pengajuan (Rp)</flux:label>
                        <flux:input type="number" wire:model="nominal_pengajuan" placeholder="Contoh: 5000000" />
                        <flux:error name="nominal_pengajuan" />
                    </flux:field>

                    <!-- Nominal Disetujui (Opsional) -->
                    <flux:field>
                        <flux:label>Nominal Disetujui (Rp) <span class="text-zinc-400 text-xs">(Opsional, jika kosong disamakan dengan pengajuan)</span></flux:label>
                        <flux:input type="number" wire:model="nominal_disetujui" placeholder="Contoh: 5000000" />
                        <flux:error name="nominal_disetujui" />
                    </flux:field>
                </div>
            </div>

            <!-- SECTION 3: REKENING PENCAIRAN -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-6">
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">3. Rekening Pencairan</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Nama Bank -->
                    <flux:field>
                        <flux:label>Nama Bank</flux:label>
                        <flux:select wire:model="nama_bank" placeholder="Pilih Bank...">
                            @foreach($this->bankList as $bank)
                                <flux:select.option value="{{ $bank }}">{{ $bank }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="nama_bank" />
                    </flux:field>

                    <!-- Nomor Rekening -->
                    <flux:field>
                        <flux:label>Nomor Rekening</flux:label>
                        <flux:input type="text" wire:model="no_rekening" placeholder="Contoh: 7012398412" />
                        <flux:error name="no_rekening" />
                    </flux:field>

                    <!-- Nama Pemilik Rekening -->
                    <flux:field>
                        <flux:label>Nama Pemilik Rekening</flux:label>
                        <flux:input type="text" wire:model="nama_pemilik_rekening" placeholder="Sesuai buku tabungan" />
                        <flux:error name="nama_pemilik_rekening" />
                    </flux:field>
                </div>
            </div>

            <!-- SECTION 4: STATUS & CATATAN -->
            <div>
                <flux:heading size="lg" class="mb-4 text-zinc-900 dark:text-white">4. Status & Catatan Tambahan</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Status -->
                    <flux:field>
                        <flux:label>Status Pinjaman</flux:label>
                        <flux:select wire:model.live="status">
                            <flux:select.option value="draft">Draft</flux:select.option>
                            <flux:select.option value="diajukan">Diajukan</flux:select.option>
                            <flux:select.option value="diproses">Diproses</flux:select.option>
                            <flux:select.option value="ditolak">Ditolak</flux:select.option>
                            <flux:select.option value="dibatalkan">Dibatalkan</flux:select.option>
                            <flux:select.option value="berjalan">Berjalan</flux:select.option>
                            <flux:select.option value="lunas">Lunas</flux:select.option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>

                    <!-- Alasan Penolakan -->
                    @if($status === 'ditolak')
                        <flux:field>
                            <flux:label>Alasan Penolakan <span class="text-red-500">*</span></flux:label>
                            <flux:input type="text" wire:model="alasan_penolakan" placeholder="Sebutkan alasan penolakan pinjaman..." />
                            <flux:error name="alasan_penolakan" />
                        </flux:field>
                    @endif

                    <!-- Catatan -->
                    <div class="md:col-span-2">
                        <flux:field>
                            <flux:label>Catatan Internal / Keterangan</flux:label>
                            <flux:textarea wire:model="catatan" rows="3" placeholder="Masukkan catatan tambahan jika diperlukan..." />
                            <flux:error name="catatan" />
                        </flux:field>
                    </div>
                </div>
            </div>

            <!-- BUTTONS -->
            <div class="mt-8 flex justify-end gap-3">
                <flux:button href="/admin/pinjaman" wire:navigate variant="subtle">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>

        </form>
    </flux:card>
</div>

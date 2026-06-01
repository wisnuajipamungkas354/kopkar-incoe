<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use App\Models\Employee;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('layouts::admin')] class extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $file;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        $query = Employee::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('npk', 'like', '%' . $this->search . '%')
                  ->orWhere('nama_lengkap', 'like', '%' . $this->search . '%')
                  ->orWhere('seksi', 'like', '%' . $this->search . '%')
                  ->orWhere('employment_status', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('npk', 'asc')->paginate($this->perPage);
    }

    public function delete($id)
    {
        $employee = Employee::find($id);

        if ($employee) {
            // Check relationships for integrity
            if ($employee->koperasiMember()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan terdaftar sebagai anggota koperasi.', variant: 'danger' })");
                return;
            }
            if ($employee->koperasiManagements()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan terdaftar sebagai pengurus/manajemen.', variant: 'danger' })");
                return;
            }
            if ($employee->user()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki akun pengguna aktif.', variant: 'danger' })");
                return;
            }
            if ($employee->pengajuanPerubahanPotonganPayroll()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki riwayat pengajuan potongan payroll.', variant: 'danger' })");
                return;
            }
            if ($employee->potonganPayrollEmployee()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki potongan payroll aktif.', variant: 'danger' })");
                return;
            }
            if ($employee->tagihanPayrollEmployee()->exists()) {
                $this->js("Flux.toast({ text: 'Tidak dapat menghapus: Karyawan memiliki tagihan payroll.', variant: 'danger' })");
                return;
            }

            $employee->delete();
            $this->js("Flux.toast({ text: 'Data karyawan berhasil dihapus.', variant: 'success' })");
        }
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $path = $this->file->getRealPath();

            // Read Excel/CSV data into array using anonymous class
            $data = Excel::toArray(new class {}, $path);

            if (empty($data) || empty($data[0])) {
                $this->js("Flux.toast({ text: 'File kosong atau tidak valid.', variant: 'danger' })");
                return;
            }

            $rows = $data[0];
            if (count($rows) <= 1) {
                $this->js("Flux.toast({ text: 'File tidak memiliki baris data (hanya header atau kosong).', variant: 'danger' })");
                return;
            }

            $header = array_map(function ($val) {
                return strtolower(trim($val));
            }, $rows[0]);

            // Find matching index positions for each header column
            $npkIdx = array_search('npk', $header);
            $namaIdx = array_search('nama_lengkap', $header);
            $jkIdx = array_search('jk', $header);
            $tempatLahirIdx = array_search('tempat_lahir', $header);
            $tanggalLahirIdx = array_search('tanggal_lahir', $header);
            $alamatIdx = array_search('alamat', $header);
            $noTelpIdx = array_search('no_telp', $header);
            $pendidikanIdx = array_search('pendidikan_terakhir', $header);
            $seksiIdx = array_search('seksi', $header);
            $gradeIdx = array_search('grade_category', $header);
            $statusIdx = array_search('employment_status', $header);
            $noRekeningIdx = array_search('no_rekening', $header);
            $namaBankIdx = array_search('nama_bank', $header);
            $namaPemilikRekeningIdx = array_search('nama_pemilik_rekening', $header);

            // Tolerant checks for headers without underscores
            if ($namaIdx === false) $namaIdx = array_search('nama lengkap', $header);
            if ($jkIdx === false) $jkIdx = array_search('jenis kelamin', $header);
            if ($tempatLahirIdx === false) $tempatLahirIdx = array_search('tempat lahir', $header);
            if ($tanggalLahirIdx === false) $tanggalLahirIdx = array_search('tanggal lahir', $header);
            if ($noTelpIdx === false) {
                foreach (['no telp', 'no telepon', 'no. telp', 'no_telpon'] as $alias) {
                    if (($idx = array_search($alias, $header)) !== false) {
                        $noTelpIdx = $idx;
                        break;
                    }
                }
            }
            if ($pendidikanIdx === false) $pendidikanIdx = array_search('pendidikan terakhir', $header);
            if ($statusIdx === false) {
                foreach (['status kerja', 'status pekerjaan', 'employment status'] as $alias) {
                    if (($idx = array_search($alias, $header)) !== false) {
                        $statusIdx = $idx;
                        break;
                    }
                }
            }
            if ($noRekeningIdx === false) {
                foreach (['no rekening', 'nomor rekening', 'rekening'] as $alias) {
                    if (($idx = array_search($alias, $header)) !== false) {
                        $noRekeningIdx = $idx;
                        break;
                    }
                }
            }
            if ($namaBankIdx === false) $namaBankIdx = array_search('nama bank', $header);
            if ($namaPemilikRekeningIdx === false) {
                foreach (['nama pemilik rekening', 'pemilik rekening'] as $alias) {
                    if (($idx = array_search($alias, $header)) !== false) {
                        $namaPemilikRekeningIdx = $idx;
                        break;
                    }
                }
            }

            if ($npkIdx === false || $namaIdx === false) {
                $this->js("Flux.toast({ text: 'Header kolom npk atau nama_lengkap tidak ditemukan.', variant: 'danger' })");
                return;
            }

            $successCount = 0;
            $skipCount = 0;

            DB::beginTransaction();

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Skip if the entire row is empty
                if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $npk = isset($row[$npkIdx]) ? trim($row[$npkIdx]) : '';
                $nama = isset($row[$namaIdx]) ? trim($row[$namaIdx]) : '';
                $jk = ($jkIdx !== false && isset($row[$jkIdx])) ? strtoupper(trim($row[$jkIdx])) : null;
                $tempatLahir = ($tempatLahirIdx !== false && isset($row[$tempatLahirIdx])) ? trim($row[$tempatLahirIdx]) : null;
                $tanggalLahirStr = ($tanggalLahirIdx !== false && isset($row[$tanggalLahirIdx])) ? trim($row[$tanggalLahirIdx]) : null;
                $alamat = ($alamatIdx !== false && isset($row[$alamatIdx])) ? trim($row[$alamatIdx]) : null;
                $noTelp = ($noTelpIdx !== false && isset($row[$noTelpIdx])) ? trim($row[$noTelpIdx]) : null;
                $pendidikan = ($pendidikanIdx !== false && isset($row[$pendidikanIdx])) ? trim($row[$pendidikanIdx]) : null;
                $seksi = ($seksiIdx !== false && isset($row[$seksiIdx])) ? trim($row[$seksiIdx]) : null;
                $grade = ($gradeIdx !== false && isset($row[$gradeIdx])) ? trim($row[$gradeIdx]) : null;
                $status = ($statusIdx !== false && isset($row[$statusIdx])) ? trim($row[$statusIdx]) : null;
                $noRekening = ($noRekeningIdx !== false && isset($row[$noRekeningIdx])) ? trim($row[$noRekeningIdx]) : null;
                $namaBank = ($namaBankIdx !== false && isset($row[$namaBankIdx])) ? trim($row[$namaBankIdx]) : null;
                $namaPemilikRekening = ($namaPemilikRekeningIdx !== false && isset($row[$namaPemilikRekeningIdx])) ? trim($row[$namaPemilikRekeningIdx]) : null;

                // Validate required fields
                if (!$npk || !$nama) {
                    $skipCount++;
                    continue;
                }

                // Check NPK uniqueness
                if (Employee::where('npk', $npk)->exists()) {
                    $skipCount++;
                    continue;
                }

                // Normalize Gender
                if ($jk) {
                    if (in_array($jk, ['L', 'LAKI-LAKI', 'LAKI LAKI', 'MALE', 'M'])) {
                        $jk = 'L';
                    } elseif (in_array($jk, ['P', 'PEREMPUAN', 'FEMALE', 'F'])) {
                        $jk = 'P';
                    } else {
                        $jk = null;
                    }
                }

                // Parse birthdate (handling Excel numeric dates)
                $tanggalLahir = null;
                if ($tanggalLahirStr) {
                    try {
                        if (is_numeric($tanggalLahirStr)) {
                            $tanggalLahir = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalLahirStr))->format('Y-m-d');
                        } else {
                            $tanggalLahir = Carbon::parse($tanggalLahirStr)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        $tanggalLahir = null;
                    }
                }

                // Normalize Seksi
                if ($seksi) {
                    $seksi = ucwords(strtolower($seksi));
                    if (!in_array($seksi, ['Produksi', 'Warehouse', 'QC', 'HR', 'IT', 'Finance'])) {
                        // Keep it, but fallback is allowed since DB column is standard varchar
                    }
                }

                // Normalize Status
                if ($status) {
                    $status = strtolower($status);
                    if (in_array($status, ['tetap', 'permanent'])) {
                        $status = 'tetap';
                    } elseif (in_array($status, ['kontrak', 'contract'])) {
                        $status = 'kontrak';
                    }
                }

                Employee::create([
                    'npk' => $npk,
                    'nama_lengkap' => $nama,
                    'jk' => $jk,
                    'tempat_lahir' => $tempatLahir,
                    'tanggal_lahir' => $tanggalLahir,
                    'alamat' => $alamat,
                    'no_telp' => $noTelp,
                    'pendidikan_terakhir' => $pendidikan,
                    'seksi' => $seksi,
                    'grade_category' => $grade,
                    'employment_status' => $status,
                    'no_rekening' => $noRekening,
                    'nama_bank' => $namaBank,
                    'nama_pemilik_rekening' => $namaPemilikRekening,
                ]);

                $successCount++;
            }

            DB::commit();

            $this->reset('file');
            $this->js("Flux.modal('import-modal').close()");

            $msg = "Berhasil mengimport {$successCount} data karyawan.";
            if ($skipCount > 0) {
                $msg .= " {$skipCount} data dilewati karena konflik NPK atau data kosong.";
            }

            $variant = $successCount > 0 ? 'success' : 'warning';
            $this->js("Flux.toast({ text: '{$msg}', variant: '{$variant}' })");
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->js("Flux.toast({ text: 'Gagal memproses file: " . addslashes($e->getMessage()) . "', variant: 'danger' })");
        }
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl" level="1">Karyawan</flux:heading>
            <flux:text class="mt-2 text-base">Kelola seluruh data master karyawan perusahaan</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:modal.trigger name="import-modal">
                <flux:button variant="primary" color="emerald" icon="arrow-up-tray">Import</flux:button>
            </flux:modal.trigger>
            <flux:button href="/admin/employee/create" wire:navigate variant="primary" icon="plus">Tambah</flux:button>
        </div>
    </div>
    
    <flux:separator variant="subtle" />
    
    <flux:card class="flex flex-col mt-3">
        <div class="flex flex-col sm:flex-row sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <flux:heading size="md">Daftar Karyawan</flux:heading>
            </div>
            <flux:input size="sm" class="max-w-md w-full sm:w-72" placeholder="Cari NPK, nama, seksi, status..." icon="magnifying-glass" wire:model.live.debounce.300ms="search" />
        </div>
        
        <flux:separator variant="subtle" class="mt-3 mb-1" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-3" :paginate="$this->employees">
                <flux:table.columns>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>L/P</flux:table.column>
                    <flux:table.column>Seksi / Departemen</flux:table.column>
                    <flux:table.column>Grade</flux:table.column>
                    <flux:table.column>Status Kerja</flux:table.column>
                    <flux:table.column>No. Telepon</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($this->employees as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->npk }}</flux:table.cell>
                            <flux:table.cell>{{ $row->nama_lengkap }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->jk === 'L')
                                    <flux:badge size="sm" color="zinc" inset="top bottom">Laki-laki</flux:badge>
                                @elseif($row->jk === 'P')
                                    <flux:badge size="sm" color="pink" inset="top bottom">Perempuan</flux:badge>
                                @else
                                    -
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->seksi ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" color="indigo" inset="top bottom">{{ $row->grade_category ?? '-' }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($row->employment_status === 'tetap')
                                    <flux:badge size="sm" color="green" inset="top bottom">Tetap</flux:badge>
                                @elseif($row->employment_status === 'kontrak')
                                    <flux:badge size="sm" color="yellow" inset="top bottom">Kontrak</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" inset="top bottom">{{ $row->employment_status ?? '-' }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->no_telp ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" href="/admin/employee/{{ $row->id }}/edit" wire:navigate>Edit</flux:menu.item>
                                        <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $row->id }})" wire:confirm="Apakah Anda yakin ingin menghapus data karyawan ini?">Hapus</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8" class="text-center py-6 text-zinc-500">
                                Tidak ada data karyawan yang ditemukan.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Import -->
    <flux:modal name="import-modal" class="md:w-2xl max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Import Data Karyawan</flux:heading>
                <flux:text class="text-sm">Silakan unggah file Excel (.xlsx, .xls) atau CSV (.csv) dengan susunan header kolom berikut:</flux:text>
            </div>

            <!-- Tabel Requirement Kolom -->
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden text-xs">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                            <th class="p-2.5 font-semibold text-zinc-700 dark:text-zinc-300">Nama Kolom (Header)</th>
                            <th class="p-2.5 font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                            <th class="p-2.5 font-semibold text-zinc-700 dark:text-zinc-300">Keterangan / Format</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">npk</td>
                            <td class="p-2.5 text-red-600 dark:text-red-400 font-semibold">Wajib</td>
                            <td class="p-2.5">Unik, misal: 10021</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">nama_lengkap</td>
                            <td class="p-2.5 text-red-600 dark:text-red-400 font-semibold">Wajib</td>
                            <td class="p-2.5">Nama lengkap sesuai KTP</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">jk</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">L (Laki-laki) atau P (Perempuan)</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">tempat_lahir</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">Kota kelahiran</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">tanggal_lahir</td>
                            <td class="p-2.5 text-red-600 dark:text-red-400 font-semibold">Wajib</td>
                            <td class="p-2.5">Format YYYY-MM-DD (Contoh: 1995-12-31)</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">alamat</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">Alamat domisili lengkap</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">no_telp</td>
                            <td class="p-2.5 text-red-600 dark:text-red-400 font-semibold">Wajib</td>
                            <td class="p-2.5">Nomor HP/WhatsApp aktif</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">pendidikan_terakhir</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">SMP, SMA/K, D3, S1, S2, S3</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">seksi</td>
                            <td class="p-2.5 text-red-600 dark:text-red-400 font-semibold">Wajib</td>
                            <td class="p-2.5">Produksi, Warehouse, QC, HR, IT, Finance</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">grade_category</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">A, B, C</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">employment_status</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">tetap atau kontrak</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">no_rekening</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">Nomor rekening bank</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">nama_bank</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">Nama bank (Contoh: BCA, Mandiri)</td>
                        </tr>
                        <tr>
                            <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">nama_pemilik_rekening</td>
                            <td class="p-2.5 text-zinc-500">Opsional</td>
                            <td class="p-2.5">Nama sesuai buku tabungan</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Form Upload -->
            <form wire:submit="import" class="space-y-6">
                <flux:field>
                    <flux:label>File Dokumen (.xlsx, .xls, .csv)</flux:label>
                    <flux:input type="file" wire:model="file" accept=".xlsx,.xls,.csv" required />
                    <flux:error name="file" />
                    <div wire:loading wire:target="file" class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-medium">
                        Mengunggah file ke server... Mohon tunggu.
                    </div>
                </flux:field>

                <div class="flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button variant="subtle">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="file, import">
                        <span wire:loading.remove wire:target="import">Proses Import</span>
                        <span wire:loading wire:target="import">Memproses Data...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

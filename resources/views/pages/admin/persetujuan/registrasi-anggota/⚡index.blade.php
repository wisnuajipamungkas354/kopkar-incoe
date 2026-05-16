<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\User;

new #[Layout('layouts::admin', ['title' => 'Persetujuan Registrasi Anggota'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedAnggota = null;

    #[Computed]
    public function pendaftarBaru()
    {
        $data = User::where('status_user', 0)->where('ext_is_approved', false)->orderBy('updated_at', 'DESC')->get();

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->nama, $this->search) !== false || 
                       stripos($item->npk, $this->search) !== false;
            });
        }

        return $data;
    }

    public function detailPendaftar($id)
    {
        $this->selectedAnggota = $this->pendaftarBaru()->firstWhere('id', $id);
    }

    public function approve($id)
    {
        $user = User::find($id);

        if(!empty($user)) {
            $user->update([
                'ext_is_approved' => true,
                'updated_at' => now(),
                'join_date' => now(),
                'status_user' => 1,
            ]);
        }
    }

    public function tolak($id)
    {
        // logic reject
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Persetujuan Registrasi Anggota</flux:heading>
            <flux:text class="mt-2 text-base text-zinc-500">Verifikasi dan persetujuan pendaftaran anggota koperasi baru.</flux:text>
        </div>
    </div>
    
    <flux:separator variant="subtle" />

    <!-- Table Section -->
    <flux:card class="flex flex-col mt-6">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Daftar Pendaftar Baru</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari nama / NPK..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal Daftar</flux:table.column>
                    <flux:table.column>NPK</flux:table.column>
                    <flux:table.column>Nama Lengkap</flux:table.column>
                    <flux:table.column>Departemen</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->pendaftarBaru as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal_daftar)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-900 dark:text-white">{{ $row->username }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                        {{ substr($row->nama_anggota, 0, 1) }}
                                    </div>
                                    <span class="font-medium">{{ $row->nama_anggota }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $row->seksi }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="orange" size="sm" inset="top bottom">Menunggu Verifikasi</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" icon="eye" wire:click="detailPendaftar({{ $row->id }})" x-on:click="$flux.modal('detail-pendaftar').show()">Detail</flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-gray-500 py-6">Tidak ada pendaftaran anggota baru.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Modal Detail -->
    <flux:modal name="detail-pendaftar" class="md:w-[36rem]">
        @if($selectedAnggota)
            <div>
                <flux:heading size="lg">Detail Pendaftar</flux:heading>
                <flux:text size="sm" class="mt-1">Periksa kembali data pendaftar sebelum memberikan persetujuan.</flux:text>
            </div>

            <div class="mt-6 flex flex-col gap-6">
                <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800">
                    <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ substr($selectedAnggota->nama_anggota, 0, 1) }}
                    </div>
                    <div>
                        <flux:heading size="md">{{ $selectedAnggota->nama_anggota }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedAnggota->username }} • {{ $selectedAnggota->seksi }}</flux:text>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">Tanggal Pendaftaran</flux:text>
                        <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ \Carbon\Carbon::parse($selectedAnggota->updated_at)->format('d F Y') }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-zinc-500 mb-1">No. WhatsApp</flux:text>
                        <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $selectedAnggota->no_telp }}</flux:text>
                    </div>
                </div>

                <flux:separator variant="subtle" />

                <div class="space-y-3 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-900/40">
                    <div class="flex gap-3">
                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" />
                        <flux:text class="text-sm text-blue-800 dark:text-blue-200">Bersedia untuk iuran Simpanan Pokok & Wajib Koperasi via Payroll CBI.</flux:text>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <flux:button variant="danger" icon="x-mark" wire:click="tolak({{ $selectedAnggota->id }})" x-on:click="$flux.modal('detail-pendaftar').close()">Tolak</flux:button>
                    <flux:button variant="primary" icon="check" wire:click="approve({{ $selectedAnggota->id }})" x-on:click="$flux.modal('detail-pendaftar').close()">Approve & Aktifkan</flux:button>
                </div>
            </div>
        @else
            <div class="py-8 text-center text-zinc-500">
                Memuat data pendaftar...
            </div>
        @endif
    </flux:modal>
</div>
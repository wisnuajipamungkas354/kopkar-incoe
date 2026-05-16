<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Midtrans\Config;
use Midtrans\CoreApi;

new #[Layout('layouts::anggota', ['title' => 'Pembayaran LAZIS'])] class extends Component
{
    use WithPagination;

    public $search = '';
    public $nominalLazis = '';
    public $jenisLazis = 'Zakat';

    #[Computed]
    public function totalZakat()
    {
        return 800000;
    }

    #[Computed]
    public function totalInfaq()
    {
        return 350000;
    }

    #[Computed]
    public function riwayatLazis()
    {
        $data = collect([
            (object) [
                'id' => 1,
                'tanggal' => '2024-01-25',
                'jenis' => 'Zakat Profesi',
                'metode' => 'Payroll',
                'nominal' => 150000,
                'status' => 'Berhasil'
            ],
            (object) [
                'id' => 2,
                'tanggal' => '2024-02-25',
                'jenis' => 'Zakat Profesi',
                'metode' => 'Payroll',
                'nominal' => 150000,
                'status' => 'Berhasil'
            ],
            (object) [
                'id' => 3,
                'tanggal' => '2024-03-10',
                'jenis' => 'Infaq Kemanusiaan',
                'metode' => 'QRIS',
                'nominal' => 100000,
                'status' => 'Berhasil'
            ],
            (object) [
                'id' => 4,
                'tanggal' => '2024-04-05',
                'jenis' => 'Shadaqah Masjid',
                'metode' => 'Cash',
                'nominal' => 50000,
                'status' => 'Berhasil'
            ],
            (object) [
                'id' => 5,
                'tanggal' => '2024-05-01',
                'jenis' => 'Zakat Maal',
                'metode' => 'QRIS',
                'nominal' => 500000,
                'status' => 'Pending'
            ],
        ]);

        if (!empty($this->search)) {
            return $data->filter(function($item) {
                return stripos($item->jenis, $this->search) !== false || 
                       stripos($item->metode, $this->search) !== false;
            });
        }

        return $data;
    }

    public function generatePayment()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $createPayment = [
            'transaction_details' => array(
                'order_id' => rand(),
                'gross_amount' => (float) $this->nominalLazis
            ),
            'customer_details' => array(
                'user_id' => 1,
                'name' => 'Wisnu Aji Pamungkas',
                'email' => 'wisnuajipamungkas@gmail.com'
            ),
            'payment_type' => 'qris',
        ];

        $response = CoreApi::charge($createPayment);
        $this->dispatch('payment-created', qrImage: $response->actions[0]->url);
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">Lembaga Amil Zakat, Infaq, dan Shadaqah (LAZIS)</flux:heading>
            <flux:text class="mt-2 text-base">Layanan penyaluran dan riwayat pembayaran Zakat, Infaq, serta Shadaqah anggota.</flux:text>
        </div>
    </div>
    <flux:separator variant="subtle" />

    <!-- Summary Cards Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 mb-6">
        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl">
                <flux:icon name="heart" class="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Zakat Ditunaikan</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->totalZakat, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-sky-100 dark:bg-sky-900/40 rounded-xl">
                <flux:icon name="sparkles" class="w-8 h-8 text-sky-600 dark:text-sky-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Infaq & Shadaqah</flux:text>
                <flux:heading size="xl" class="mt-1">Rp {{ number_format($this->totalInfaq, 0, ',', '.') }}</flux:heading>
            </div>
        </flux:card>

        <flux:card class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/40 rounded-xl">
                <flux:icon name="shield-check" class="w-8 h-8 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <flux:text class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Penyaluran Terpercaya</flux:text>
                <flux:heading size="xl" class="mt-1">100% <span class="text-base font-normal text-zinc-500">Amanah</span></flux:heading>
            </div>
        </flux:card>
    </div>

    <!-- Tabel Riwayat LAZIS -->
    <flux:card class="flex flex-col">
        <div class="flex flex-col gap-3 md:flex-row md:gap-0 justify-between items-center">
            <flux:heading size="lg" level="2">Riwayat Pembayaran LAZIS</flux:heading>
            <flux:input wire:model.live="search" size="sm" class="max-w-64" placeholder="Cari jenis atau metode..." icon="magnifying-glass" />
        </div>
        
        <flux:separator variant="subtle" class="mt-4 mb-2" />
        
        <div class="overflow-x-auto">
            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>Tanggal</flux:table.column>
                    <flux:table.column>Jenis Saluran</flux:table.column>
                    <flux:table.column>Metode</flux:table.column>
                    <flux:table.column>Nominal</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($this->riwayatLazis as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</flux:table.cell>
                            <flux:table.cell class="font-medium text-zinc-800 dark:text-zinc-200">{{ $row->jenis }}</flux:table.cell>
                            <flux:table.cell>
                                @if(stripos($row->metode, 'Payroll') !== false)
                                    <flux:badge color="blue" size="sm" inset="top bottom">{{ $row->metode }}</flux:badge>
                                @elseif(stripos($row->metode, 'QRIS') !== false)
                                    <flux:badge color="purple" size="sm" inset="top bottom">{{ $row->metode }}</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm" inset="top bottom">{{ $row->metode }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-semibold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($row->nominal, 0, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                @if($row->status === 'Berhasil')
                                    <flux:badge color="green" size="sm" inset="top bottom">Berhasil</flux:badge>
                                @else
                                    <flux:badge color="orange" size="sm" inset="top bottom">Pending</flux:badge>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="text-center text-gray-500 py-6">Tidak ada data riwayat pembayaran LAZIS.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Form Tambah Pembayaran via QRIS -->
    <div class="mt-8">
        <div class="mb-4">
            <flux:heading size="lg" level="2">Tunaikan LAZIS via QRIS</flux:heading>
            <flux:text class="mt-1 text-base">Salurkan Zakat, Infaq, atau Shadaqah Anda secara mudah, aman, dan instan menggunakan scan QRIS.</flux:text>
        </div>
        
        <flux:card class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <form class="flex flex-col gap-6 justify-between" wire:submit.prevent="generatePayment">
                <div class="space-y-4">
                    <flux:radio.group wire:model.live="jenisLazis" label="Pilih Jenis Saluran">
                        <div class="flex gap-4 mt-2">
                            <flux:radio value="Zakat" label="Zakat" />
                            <flux:radio value="Infaq" label="Infaq" />
                            <flux:radio value="Shadaqah" label="Shadaqah" />
                        </div>
                    </flux:radio.group>

                    <flux:input wire:model.live="nominalLazis" label="Nominal Saluran (Rp)" min="10000" placeholder="Contoh: 100000" type="number" required />
                </div>
                
                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button class="w-full" type="submit" variant="primary" icon="qr-code">Buat QRIS Pembayaran</flux:button>
                </div>
            </form>
            <livewire:pages::anggota.pembayaran.lazis.generate-qris />
        </flux:card>
    </div>
</div>
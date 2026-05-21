<?php

use App\Models\TransaksiMutasi;
use App\Models\TransaksiMutasiQris;
use Livewire\Component;
use Livewire\Attributes\Modelable;
use Midtrans\Config;
use Midtrans\CoreApi;

new class extends Component
{
    
    public $qrImage;
    public $expiresAt;
    public $nominal;
    public $minNominal;
    public $hasActiveQris = false;

    public function mount()
    {
        $exists = TransaksiMutasi::where('user_id', auth('web')->user()->id)
            ->where('metode_pembayaran', 'qris')
            ->where('status_pembayaran', 'pending')
            ->where('tanggal_transaksi', '>=', now()->subMinutes(15))->first();
        if ($exists) {
            $qrisData = TransaksiMutasiQris::where('transaksi_mutasi_id', $exists->id)->first();
            $this->qrImage = $qrisData->url_image_qris;
            $this->expiresAt = $qrisData->created_at->addMinutes(15)->toIso8601String();
            $this->hasActiveQris = true;     
        } 
    }

    public function generatePayment()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Rumus Generate Payment
        $nominal = ceil($this->nominal / (1 - 0.007));
        $feeAplikasi = $nominal - $this->nominal;
        
        $createPayment = [
            'transaction_details' => array(
                'order_id' => 'TRX-SSA-' . time(),
                'gross_amount' => (int) $nominal
            ),
            'customer_details' => array(
                'user_id' => 1,
                'name' => auth('web')->user()->nama_anggota,
                'email' => auth('web')->user()->email,
            ),
            'expiry' => [
                'start_time' => date("Y-m-d H:i:s O"),
                'unit' => 'minute',
                'duration' => 15
            ],
            'payment_type' => 'qris',
        ];

        $response = CoreApi::charge($createPayment);
        $qrImage = collect($response->actions)->firstWhere('name', 'generate-qr-code');
        
        $transaksi = TransaksiMutasi::create([
            'user_id' => auth('web')->user()->id,
            'kategori_transaksi' => 'sukarela',
            'jenis_transaksi' => 'setoran_tambahan',
            'metode_pembayaran' => 'qris',
            'nominal' => $this->nominal,
            'status_pembayaran' => 'pending',
            'tanggal_transaksi' => now(),
        ]);
        
        TransaksiMutasiQris::create([
            'transaksi_mutasi_id' => $transaksi->id,
            'url_image_qris' => $qrImage->url,
            'transaction_id_vendor' => $createPayment['transaction_details']['order_id'],
            'fee_aplikasi_diwajibkan' => $feeAplikasi,
            'total_bayar_anggota' => $nominal,
        ]);


        $this->hasActiveQris = true;
        $this->dispatch('payment-created', qrImage: $qrImage->url, expiresAt: now()->addMinutes(15)->toIso8601String());
    }

    public function downloadQr()
    {
        if (!$this->qrImage) return;

        $content = file_get_contents($this->qrImage);

        return response()->streamDownload(function () use ($content) { echo $content; }, 'QRIS-Payment.png', [ 'Content-Type' => 'image/png']);
    }

    public function cancelQris()
    {
        $this->qrImage = null;
        $this->expiresAt = null;
        $this->hasActiveQris = false;
    }
};
?>

<flux:card class="grid grid-cols-1 {{ $hasActiveQris ? 'md:grid-cols-1' : 'md:grid-cols-2' }} gap-6">
    <form class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end" wire:submit.prevent="generatePayment" wire:show="!hasActiveQris">
        <div class="flex flex-col gap-4">
            <flux:input wire:model.live="nominal" label="Nominal Setoran (Rp)" min="{{ $minNominal }}" placeholder="Contoh: 500000" type="number" required />
        </div>
        
        <div class="mt-2">
            <flux:button class="w-full" type="submit" variant="primary" icon="qr-code">Buat QRIS</flux:button>
        </div>
    </form>
    <div class="flex flex-col justify-center items-center gap-3">
        <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800/50 text-sm text-purple-700 dark:text-purple-300 flex items-start gap-3">
            <flux:icon name="qr-code" class="w-5 h-5 mt-0.5 shrink-0" />
            <div>
                <span class="font-semibold">Otomatis via QRIS:</span> Pembayaran instan tanpa perlu unggah bukti transfer. Kode QRIS akan muncul setelah Anda klik tombol Buat QRIS.
            </div>
        </div>
        
        <div class="flex flex-col justify-center items-center w-full mt-2" wire:show="hasActiveQris">
            <div 
                x-data="{
                    timeLeft: '15:00',
                    expired: false,
                    intervalId: null,
                    updateTimer() {
                        if (!$wire.expiresAt) return;
                        const target = new Date($wire.expiresAt).getTime();
                        const now = new Date().getTime();
                        const distance = target - now;
    
                        if (distance < 0) {
                            this.expired = true;
                            this.timeLeft = '00:00';
                            clearInterval(this.intervalId);
                            return;
                        }
    
                        this.expired = false;
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        this.timeLeft = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                    }
                }"
                x-init="
                    if ($wire.expiresAt) {
                        updateTimer();
                        intervalId = setInterval(() => updateTimer(), 1000);
                    }
                    $watch('$wire.expiresAt', value => {
                        if (value) {
                            clearInterval(intervalId);
                            updateTimer();
                            intervalId = setInterval(() => updateTimer(), 1000);
                        }
                    });
                "
                class="w-full flex flex-col items-center mb-4"
                x-show="$wire.hasActiveQris"
            >
                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-widest mb-1">Berakhir Dalam</span>
                <div class="text-3xl font-mono font-bold text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 px-4 py-2 rounded-lg border border-orange-200 dark:border-orange-800/50" x-text="timeLeft"></div>
                <div x-show="expired" class="text-red-500 text-sm mt-2 font-medium bg-red-50 p-2 rounded-md" x-cloak>Kode QRIS telah kedaluwarsa. Silakan batalkan dan buat ulang.</div>
            </div>
    
            <img src="{{ $qrImage }}" class="max-w-64 max-h-64 border-2 border-zinc-100 shadow-sm rounded-xl mb-4" />
            
            <div class="flex flex-col gap-2 w-full max-w-64">
                <flux:button wire:click="downloadQr" variant="primary" color="primary" icon="arrow-down-tray">Download QRIS</flux:button>
                <flux:button variant="subtle" color="zinc" wire:click="cancelQris">Tutup & Batalkan</flux:button>
            </div>
        </div>
    </div>
</flux:card>
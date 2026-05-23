<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\TransaksiMutasi;
use App\Models\TransaksiMutasiQris;
use Carbon\Carbon;

new class extends Component
{
    public $qrImage;
    public $expiresAt;
    public $isGenerated = false;

    public function mount()
    {
        $activeTransaksi = TransaksiMutasi::where('user_id', auth('web')->user()->id)
            ->where('metode_pembayaran', 'qris')
            ->where('status_pembayaran', 'pending')
            ->where('tanggal_transaksi', '>=', now()->subMinutes(15))
            ->latest('tanggal_transaksi')
            ->first();

        if ($activeTransaksi) {
            $qris = TransaksiMutasiQris::where('transaksi_mutasi_id', $activeTransaksi->id)->first();
            if ($qris) {
                $this->qrImage = $qris->url_image_qris;
                $this->expiresAt = Carbon::parse($activeTransaksi->tanggal_transaksi)->addMinutes(15)->toIso8601String();
                $this->isGenerated = true;
            }
        }
    }

    #[On('payment-created')]
    public function updatePaymentImage($qrImage, $expiresAt)
    {
        $this->qrImage = $qrImage;
        $this->expiresAt = $expiresAt;
        $this->isGenerated = true;
    }

    public function cancelQris()
    {
        $activeTransaksi = TransaksiMutasi::where('user_id', auth('web')->user()->id)
            ->where('metode_pembayaran', 'qris')
            ->where('status_pembayaran', 'pending')
            ->where('tanggal_transaksi', '>=', now()->subMinutes(15))
            ->latest('tanggal_transaksi')
            ->first();

        if ($activeTransaksi) {
            $activeTransaksi->update(['status_pembayaran' => 'failed']);
        }

        $this->isGenerated = false;
        $this->dispatch('qris-cancelled');
    }

    public function downloadQr()
    {
        if (!$this->qrImage) return;

        $content = file_get_contents($this->qrImage);

        return response()->streamDownload(function () use ($content) { echo $content; }, 'QRIS-Payment.png', [ 'Content-Type' => 'image/png']);
    }
};
?>

<div class="flex flex-col justify-center items-center gap-3">
    <div class="p-4 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800/50 text-sm text-purple-700 dark:text-purple-300 flex items-start gap-3">
        <flux:icon name="qr-code" class="w-5 h-5 mt-0.5 shrink-0" />
        <div>
            <span class="font-semibold">Otomatis via QRIS:</span> Pembayaran instan tanpa perlu unggah bukti transfer. Kode QRIS akan muncul setelah Anda klik tombol Buat QRIS.
        </div>
    </div>
    
    <div class="flex flex-col justify-center items-center w-full mt-2" wire:show="isGenerated">
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
            x-show="$wire.isGenerated"
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
<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component
{
    public $qrImage;
    public $isGenerated = false;

    #[On('payment-created')]
    public function updatePaymentImage($qrImage)
    {
        $this->qrImage = $qrImage;
        $this->isGenerated = true;
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
    <div class="flex flex-col justify-center" wire:show="isGenerated">
        <img src="{{ $qrImage }}" class="max-w-64 max-h-64" />
        <flux:button variant="primary" color="red" x-on:click="$wire.isGenerated = false">Batalkan</flux:button>
    </div>
</div>
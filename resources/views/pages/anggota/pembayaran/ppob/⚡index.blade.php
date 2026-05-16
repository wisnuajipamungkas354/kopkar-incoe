<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <flux:heading size="xl" level="1">PPOB</flux:heading>
            <flux:text class="mt-2 text-base">Manajemen dan pengajuan penarikan saldo simpanan sukarela, simpanan lain-lain, dan SHU.</flux:text>
        </div>
        <div class="flex gap-2 mt-4 sm:mt-0">
            <flux:modal.trigger name="ajukan-penarikan">
                <flux:button size="sm" variant="primary" icon="arrow-down-tray">Ajukan Penarikan</flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:separator variant="subtle" />
</div>
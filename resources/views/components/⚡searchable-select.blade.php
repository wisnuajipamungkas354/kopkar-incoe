<?php

use Livewire\Component;
use Livewire\Attributes\Modelable;

new class extends Component
{
    #[Modelable]
    public $value = ''; // Ini yang akan sinkron ke parent

    public $options = [];
    public $placeholder = 'Pilih opsi...';
    public $label = null;
    public $search = '';
};
?>

@props([
    'options' => [], // Data array yang akan ditampilkan
    'selected' => null, // Binding nilai yang dipilih
    'placeholder' => 'Pilih opsi...',
    'label' => null
])

<div class="w-full" 
     x-data="{ 
        open: false, 
        search: @entangle('search'), 
        options: {{ json_encode($options) }},
        selected: @entangle('value'), {{-- Sinkron ke #[Modelable] --}}
        
        get filteredOptions() {
            if (this.search === '') return this.options;
            return this.options.filter(option => 
                option.toLowerCase().includes(this.search.toLowerCase())
            );
        },
        selectOption(option) {
            this.selected = option;
            this.search = '';
            this.open = false;
        }
     }" 
     @click.away="open = false">
    
    @if($label)
        <label class="block text-sm font-medium text-zinc-700 dark:text-white">{{ $label }}</label>
    @endif

    <div class="relative">
        <button type="button" 
                @click="open = !open"
                class="mt-4 w-full flex items-center justify-between rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/10 py-2 px-3 text-sm transition-all focus:ring-[0.1rem]">
            
            <span x-text="selected ? selected : '{{ $placeholder }}'" 
                  :class="!selected ? 'text-zinc-400' : 'text-zinc-900 dark:text-white'"></span>
            
            <svg class="h-4 w-4 text-zinc-400 dark:text-white transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="open" 
             x-cloak
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute z-50 mt-1 w-full bg-white dark:bg-zinc-700 rounded-lg border border-zinc-200 shadow-xl overflow-hidden"
             style="display: none;">
            
            <div class="p-2 border-b border-zinc-100 dark:border-white/10">
                <input type="text" 
                       x-model="search"
                       class="w-full border-none focus:ring-0 py-1 px-2 text-sm text-zinc-700 dark:text-white bg-transparent" 
                       placeholder="Cari..."
                       @click.stop>
            </div>

            <ul class="max-h-60 overflow-y-auto py-1">
                <template x-for="option in filteredOptions" :key="option">
                    <li>
                        <button type="button" 
                                @click="selectOption(option)"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                                :class="selected === option ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-zinc-700 dark:text-zinc-200'">
                            <span x-text="option"></span>
                        </button>
                    </li>
                </template>
                
                <div x-show="filteredOptions.length === 0" class="px-4 py-3 text-sm text-zinc-500 dark:text-white/80 italic text-center">
                    Data tidak ditemukan...
                </div>
            </ul>
        </div>
    </div>
</div>
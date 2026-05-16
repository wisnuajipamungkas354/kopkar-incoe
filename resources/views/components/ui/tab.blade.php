@props(['name'])

<button 
    type="button"
    @click="activeTab = '{{ $name }}'"
    :class="activeTab === '{{ $name }}' ? 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white shadow-sm ring-1 ring-zinc-900/5 dark:ring-white/10' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:bg-zinc-200/50 dark:hover:bg-zinc-700/50'"
    {{ $attributes->merge(['class' => 'flex-1 flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md whitespace-nowrap transition-all']) }}
>
    {{ $slot }}
</button>

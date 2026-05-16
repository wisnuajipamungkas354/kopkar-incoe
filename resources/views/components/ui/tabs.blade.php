@props(['active' => null])

<div x-data="{ 
        activeTab: @if($attributes->wire('model')->value()) @entangle($attributes->wire('model')) @else '{{ $active }}' @endif 
    }" 
    {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'w-full']) }}>
    {{ $slot }}
</div>

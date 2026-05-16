@props(['name'])

<div 
    x-show="activeTab === '{{ $name }}'" 
    style="display: none;"
    {{ $attributes->merge(['class' => 'focus:outline-none']) }}
>
    {{ $slot }}
</div>

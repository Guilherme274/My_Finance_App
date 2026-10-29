@props(['brand', 'size' => 'md'])

@php
    $dim = match ($size) {
        'xs' => 'w-4 h-4',
        'sm' => 'w-5 h-5',
        'md' => 'w-7 h-7',
        'lg' => 'w-8 h-8',
        'xl' => 'w-10 h-10',
        default => 'w-7 h-7',
    };
    $innerDim = match ($size) {
        'xs' => 'w-3 h-3',
        'sm' => 'w-3.5 h-3.5',
        'md' => 'w-4.5 h-4.5',
        'lg' => 'w-5.5 h-5.5',
        'xl' => 'w-7 h-7',
        default => 'w-4.5 h-4.5',
    };
    $rounded = match ($size) {
        'xs', 'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        default => 'rounded-lg',
    };
@endphp

@if ($brand === 'mercadopago')
    <img src="{{ asset('images/mercadopago.png') }}"
         alt="Mercado Pago"
         {{ $attributes->merge(['class' => "inline-block {$dim} {$rounded} object-contain shrink-0 shadow-sm bg-[#ffe600] border border-black/10"]) }}
         title="Mercado Pago"
         loading="lazy" />
@elseif ($brand === 'nubank')
    <img src="{{ asset('images/nubank.png') }}"
         alt="Nubank"
         {{ $attributes->merge(['class' => "inline-block {$dim} {$rounded} object-contain shrink-0 shadow-sm bg-[#8d0de3] border border-black/10"]) }}
         title="Nubank"
         loading="lazy" />
@endif


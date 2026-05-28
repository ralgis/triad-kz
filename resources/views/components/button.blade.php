@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'submit',
    'href' => null,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium rounded
             focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
             transition disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 focus-visible:ring-brand-600',
        'secondary' => 'bg-slate-100 text-slate-800 hover:bg-slate-200 focus-visible:ring-slate-600',
        'outline' => 'border border-slate-300 text-slate-800 hover:bg-slate-50 focus-visible:ring-brand-600',
        'ghost' => 'text-brand-600 hover:bg-brand-50 focus-visible:ring-brand-600',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-600',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-5 py-2.5 text-base',
        'lg' => 'px-6 py-3 text-base',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif

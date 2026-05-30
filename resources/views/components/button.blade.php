@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'submit',
    'href' => null,
])
{{--
    Drafting Floor button primitive.

    Variants:
    - primary   : Blueprint-blue solid. Default action.
    - stamp     : Hot-stamp red. CRITICAL action only (1 max per page).
    - outline   : Bordered, transparent fill. Secondary actions.
    - ghost     : Text-only with hover wash. Tertiary / cancel.
    - mono      : IBM Plex Mono uppercase. For catalog-nav style links.

    Outline uses inline border-color via class because Tailwind v4
    sometimes lags on generating `border-{custom-color}` utilities
    from the @theme block. Forcing the color via the !border-steel
    bang-important works around that.
--}}
@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium
             uppercase tracking-wider transition
             disabled:opacity-50 disabled:cursor-not-allowed';

    $variants = [
        'primary' => 'bg-blueprint-600 text-document hover:bg-blueprint-700 border-2 border-blueprint-600',
        'stamp' => 'bg-stamp-600 text-document hover:bg-stamp-700 border-2 border-stamp-600',
        'outline' => 'bg-transparent text-steel hover:bg-steel hover:text-document border-2 border-steel',
        'ghost' => 'text-blueprint-600 hover:bg-blueprint-50 border-2 border-transparent',
        'mono' => 'font-mono text-steel hover:text-blueprint-600 border-b-2 border-steel hover:border-blueprint-600 px-0 py-1',
    ];

    $sizes = [
        'sm' => 'px-4 py-2 text-xs',
        'md' => 'px-6 py-3 text-sm',
        'lg' => 'px-8 py-4 text-sm',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif

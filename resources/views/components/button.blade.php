@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'submit',
    'href' => null,
])
{{--
    Drafting Floor button primitive.

    Variants:
    - primary   : Blueprint-blue solid. Default action (B корзину, Запрос
                  цены). Heavy weight + tracking for industrial feel.
    - stamp     : Hot-stamp red. CRITICAL action only — order confirm,
                  delete. Should be rare on the page (1 max).
    - outline   : Bordered, transparent fill. Secondary actions.
    - ghost     : Text-only with hover wash. Tertiary / cancel.
    - mono      : IBM Plex Mono, uppercase. For technical / catalog
                  navigation («КАТАЛОГ →», «38 SKU»). NOT for body CTAs.

    Sizes set both padding and the tracking — bigger = tighter
    because Russo One / mono behave differently at scale.
--}}
@php
    /* SQUARE edges, no rounded — industrial DNA. Border 2px when
       outline / stamp for hard-edge industrial feel. focus-visible
       handled globally in app.css. */
    $base = 'inline-flex items-center justify-center gap-2 font-medium
             uppercase tracking-wider transition
             disabled:opacity-50 disabled:cursor-not-allowed
             border-2 border-transparent';

    $variants = [
        'primary' => 'bg-blueprint-600 text-document hover:bg-blueprint-700',
        'stamp' => 'bg-stamp-600 text-document hover:bg-stamp-700',
        'outline' => 'border-steel text-steel hover:bg-steel hover:text-document',
        'ghost' => 'text-blueprint-600 hover:bg-blueprint-50',
        'mono' => 'font-mono text-steel hover:text-blueprint-600 border-b border-steel hover:border-blueprint-600 border-x-0 border-t-0 px-0',
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

@props(['category'])

@php
    $image = $category->getFirstMediaUrl('cover', 'card');
    $count = $category->products_count ?? null;
@endphp

<a href="{{ $category->url() }}"
   class="group block bg-document border-2 border-edge hover:translate-y-[-2px] transition">
    <div class="aspect-[4/3] bg-concrete-dark border-b-2 border-edge overflow-hidden">
        @if($image)
            <img src="{{ $image }}"
                 alt="{{ $category->imageAlt() }}"
                 title="{{ $category->imageTitle() }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center font-display uppercase tracking-wider text-haze text-4xl">
                {{ mb_substr($category->name, 0, 1) }}
            </div>
        @endif
    </div>
    <div class="p-4 flex items-center justify-between gap-3">
        <h3 class="font-display uppercase tracking-tight text-base lg:text-lg text-steel group-hover:text-blueprint-600 transition leading-tight">
            {{ $category->name }}
        </h3>
        @if($count !== null)
            <span class="font-mono text-xs text-haze spec-value shrink-0">
                {{ $count }}<span class="text-haze/60"> SKU</span>
            </span>
        @endif
    </div>
</a>

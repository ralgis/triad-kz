@props(['category'])

@php
    $image = $category->getFirstMediaUrl('cover', 'card');
    $count = $category->products_count ?? null;
@endphp

<a href="{{ $category->url() }}"
   class="group block bg-white border border-slate-200 rounded-lg overflow-hidden hover:border-brand-400 hover:shadow-md transition">
    <div class="aspect-[4/3] bg-slate-50">
        @if($image)
            <img src="{{ $image }}"
                 alt="{{ $category->name }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-300 text-3xl font-semibold">
                {{ mb_substr($category->name, 0, 1) }}
            </div>
        @endif
    </div>
    <div class="p-4">
        <h3 class="font-medium text-slate-900 group-hover:text-brand-600">{{ $category->name }}</h3>
        @if($count !== null)
            <p class="mt-1 text-sm text-slate-500">{{ $count }} {{ trans_choice('товар|товара|товаров', $count) }}</p>
        @endif
    </div>
</a>

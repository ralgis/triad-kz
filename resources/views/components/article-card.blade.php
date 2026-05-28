@props(['article'])

@php
    $image = $article->getFirstMediaUrl('cover', 'card');
@endphp

<a href="{{ $article->url() }}"
   class="group block bg-white border border-slate-200 rounded-lg overflow-hidden hover:border-brand-400 hover:shadow-md transition">
    <div class="aspect-[16/9] bg-slate-50">
        @if($image)
            <img src="{{ $image }}"
                 alt="{{ $article->title }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-300 text-sm uppercase tracking-wider">
                Статья
            </div>
        @endif
    </div>
    <div class="p-4">
        @if($article->published_at)
            <p class="text-xs text-slate-500 uppercase tracking-wide mb-2">
                {{ $article->published_at->translatedFormat('j F Y') }}
            </p>
        @endif
        <h3 class="font-medium text-slate-900 group-hover:text-brand-600 leading-snug">
            {{ $article->title }}
        </h3>
        @if($article->excerpt)
            <p class="mt-2 text-sm text-slate-600 line-clamp-3">{{ $article->excerpt }}</p>
        @endif
    </div>
</a>

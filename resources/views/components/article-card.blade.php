@props(['article'])

@php
    $image = $article->getFirstMediaUrl('cover', 'card');
@endphp

<a href="{{ $article->url() }}"
   class="group block bg-document border-2 border-edge hover:translate-y-[-2px] transition">
    <div class="aspect-[16/9] bg-concrete-dark border-b-2 border-edge overflow-hidden">
        @if($image)
            <img src="{{ $image }}"
                 alt="{{ $article->imageAlt() }}"
                 title="{{ $article->imageTitle() }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        @else
            <div class="w-full h-full flex items-center justify-center font-mono text-xs text-haze uppercase tracking-wider">
                Статья
            </div>
        @endif
    </div>
    <div class="p-4 sm:p-5">
        @if($article->published_at)
            <p class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider mb-2">
                {{ $article->published_at->translatedFormat('j M Y') }}
                @if($article->reading_minutes)
                    · {{ $article->reading_minutes }} мин чтения
                @endif
            </p>
        @endif
        <h3 class="font-display uppercase tracking-tight text-base sm:text-lg text-steel group-hover:text-blueprint-600 transition leading-snug">
            {{ $article->title }}
        </h3>
        @if($article->excerpt)
            <p class="mt-2 text-sm text-steel-soft line-clamp-3 leading-relaxed">
                {{ $article->excerpt }}
            </p>
        @endif
    </div>
</a>

@props(['items' => []])

{{--
    Drafting Floor breadcrumb. Mono uppercase, haze color, hard «·»
    separator. Reads like a draft-sheet location strip.

    $items is an ordered list of [['label' => ..., 'url' => ...], ...]
    where the LAST item is the current page (rendered as plain text).
--}}
@if(count($items) > 0)
    @include('partials.schema.breadcrumb', ['items' => $items])
    <nav aria-label="Хлебные крошки" class="font-mono text-[10px] sm:text-xs text-haze uppercase tracking-wider">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li>
                <a href="{{ url('/') }}" class="hover:text-blueprint-600 transition">Главная</a>
            </li>
            @foreach($items as $i => $item)
                <li class="flex items-center gap-1.5">
                    <span aria-hidden="true" class="text-haze/60">·</span>
                    @if($i === count($items) - 1)
                        <span aria-current="page" class="text-steel">{{ $item['label'] }}</span>
                    @else
                        <a href="{{ $item['url'] }}" class="hover:text-blueprint-600 transition">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif

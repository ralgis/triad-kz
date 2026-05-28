@props(['items' => []])

{{--
    Semantic breadcrumb. $items is an ordered list of [['label' => ..., 'url' => ...], ...]
    where the LAST item is the current page (rendered as plain text, no link).

    Emits both the visual <nav> AND the BreadcrumbList JSON-LD so search
    engines render rich breadcrumbs without us hand-wiring the schema
    partial on every page.
--}}
@if(count($items) > 0)
    @include('partials.schema.breadcrumb', ['items' => $items])
    <nav aria-label="Хлебные крошки" class="text-sm">
        <ol class="flex flex-wrap items-center gap-1 text-slate-500">
            <li class="flex items-center gap-1">
                <a href="{{ url('/') }}" class="hover:text-brand-600">Главная</a>
            </li>
            @foreach($items as $i => $item)
                <li class="flex items-center gap-1">
                    <span aria-hidden="true" class="text-slate-300">/</span>
                    @if($i === count($items) - 1)
                        <span aria-current="page" class="text-slate-700">{{ $item['label'] }}</span>
                    @else
                        <a href="{{ $item['url'] }}" class="hover:text-brand-600">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif

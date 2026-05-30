<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="ru-RU">
    <title>{{ $settings->site_name }} — статьи блога</title>
    <subtitle>Статьи о ЖБИ-изделиях, ГОСТах и применении в строительстве.</subtitle>
    <link href="{{ url('/blog') }}" />
    <link rel="self" href="{{ $selfUrl }}" type="application/atom+xml" />
    <id>{{ url('/blog') }}</id>
    @if($articles->isNotEmpty())
        <updated>{{ $articles->first()->effectiveModifiedAt()?->toAtomString() ?? now()->toAtomString() }}</updated>
    @else
        <updated>{{ now()->toAtomString() }}</updated>
    @endif
    <author>
        <name>{{ $settings->site_name }}</name>
        @if($settings->public_email)
            <email>{{ $settings->public_email }}</email>
        @endif
    </author>

    @foreach($articles as $article)
        <entry>
            <title>{{ $article->title }}</title>
            <link href="{{ $article->url() }}" />
            <id>{{ $article->url() }}</id>
            @if($article->published_at)
                <published>{{ $article->published_at->toAtomString() }}</published>
            @endif
            <updated>{{ $article->effectiveModifiedAt()?->toAtomString() ?? $article->updated_at?->toAtomString() ?? now()->toAtomString() }}</updated>
            @if($article->blogCategory)
                <category term="{{ $article->blogCategory->slug }}" label="{{ $article->blogCategory->name }}" />
            @endif
            @if($article->excerpt)
                <summary type="text">{{ $article->excerpt }}</summary>
            @endif
            @if($article->content)
                <content type="html"><![CDATA[{!! $article->content !!}]]></content>
            @endif
        </entry>
    @endforeach
</feed>

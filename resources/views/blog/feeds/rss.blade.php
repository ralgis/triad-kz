<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>{{ $settings->site_name }} — статьи блога</title>
        <link>{{ url('/blog') }}</link>
        <description>Статьи о ЖБИ-изделиях, ГОСТах и применении в строительстве.</description>
        <language>ru-RU</language>
        <atom:link href="{{ $selfUrl }}" rel="self" type="application/rss+xml" />
        @if($articles->isNotEmpty())
            <lastBuildDate>{{ $articles->first()->effectiveModifiedAt()?->toRssString() ?? now()->toRssString() }}</lastBuildDate>
        @endif
        @foreach($articles as $article)
            <item>
                <title>{{ $article->title }}</title>
                <link>{{ $article->url() }}</link>
                <guid isPermaLink="true">{{ $article->url() }}</guid>
                @if($article->published_at)
                    <pubDate>{{ $article->published_at->toRssString() }}</pubDate>
                @endif
                @if($article->blogCategory)
                    <category>{{ $article->blogCategory->name }}</category>
                @endif
                <description><![CDATA[{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->content ?? ''), 280) }}]]></description>
                @if($article->content)
                    <content:encoded><![CDATA[{!! $article->content !!}]]></content:encoded>
                @endif
            </item>
        @endforeach
    </channel>
</rss>

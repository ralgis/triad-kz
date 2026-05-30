<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RSS 2.0 + Atom 1.0 feeds for the blog. Two formats because:
 * - RSS is what Telegram bots, Feedly, NetNewsWire, and most legacy
 *   readers consume.
 * - Atom is preferred by some modern aggregators and was Yandex.Dzen's
 *   intake format (Dzen sold to VK 2022, RSS-pumping still works but
 *   for B2B-ЖБИ it's noise — keeping Atom around is cheap).
 *
 * Cached at HTTP layer (Cache-Control: public, max-age=900) — feeds
 * change slow, and rendering 20 articles into XML on every poll wastes
 * cycles.
 */
final class BlogFeedController extends Controller
{
    private const ITEMS_LIMIT = 20;

    public function rss(Request $request): Response
    {
        $articles = $this->latestArticles();
        $settings = Setting::current();

        $xml = view('blog.feeds.rss', [
            'articles' => $articles,
            'settings' => $settings,
            'selfUrl' => route('blog.feed.rss'),
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }

    public function atom(Request $request): Response
    {
        $articles = $this->latestArticles();
        $settings = Setting::current();

        $xml = view('blog.feeds.atom', [
            'articles' => $articles,
            'settings' => $settings,
            'selfUrl' => route('blog.feed.atom'),
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/atom+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }

    /**
     * @return Collection<int, Article>
     */
    private function latestArticles(): Collection
    {
        return Article::query()
            ->published()
            ->with('blogCategory')
            ->latest('published_at')
            ->limit(self::ITEMS_LIMIT)
            ->get();
    }
}

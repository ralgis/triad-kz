<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Article;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets authenticated admin preview drafts and scheduled articles via a
 * signed URL with 15-minute TTL. Without this middleware, draft articles
 * return 404 from BlogController::show (which is the right default for
 * everyone else).
 *
 * Preview URL shape: /blog/{slug}?preview=1&expires=...&signature=...
 *
 * URL::signedRoute() emits + verifies the signature; we just check
 * Auth + URL::hasValidSignature() and let the controller proceed
 * even when scopePublished() would normally 404.
 *
 * Attaches to the show route via middleware('draft-preview') —
 * registered in bootstrap/app.php.
 */
final class AllowDraftPreviewForAdmin
{
    public const FLAG = '_preview';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->boolean('preview') && URL::hasValidSignature($request) && $request->user()) {
            // Stash a per-request flag the controller can read to skip
            // its published() existence check.
            $request->attributes->set(self::FLAG, true);
        }

        return $next($request);
    }

    public static function isPreviewing(Request $request): bool
    {
        return (bool) $request->attributes->get(self::FLAG, false);
    }

    /**
     * Mint a 15-minute signed URL for the article. Called from the
     * Filament «Preview» action on EditArticle.
     */
    public static function urlFor(Article $article): string
    {
        return URL::temporarySignedRoute(
            'blog.article',
            now()->addMinutes(15),
            ['article' => $article->slug, 'preview' => 1],
        );
    }
}

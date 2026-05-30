<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

/**
 * Re-runs HTMLPurifier over every existing Article.content. Used after
 * landing the sanitization setter (Phase 2 P1) — historical content
 * predating the setter was never purified and may carry inert but
 * unwanted markup. Idempotent: a second run on already-clean HTML
 * produces the same HTML.
 *
 * Usage: `php artisan articles:resanitize-content`
 */
final class ResanitizeArticleContent extends Command
{
    protected $signature = 'articles:resanitize-content
                            {--dry-run : Show what would change without persisting}';

    protected $description = 'Re-run HTMLPurifier over every Article.content to scrub historical markup.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $total = Article::count();
        if ($total === 0) {
            $this->info('No articles to process.');

            return self::SUCCESS;
        }

        $changed = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Article::query()
            ->withoutGlobalScopes()
            ->chunkById(50, function ($chunk) use (&$changed, $dry, $bar): void {
                foreach ($chunk as $article) {
                    $original = $article->getRawOriginal('content');
                    if ($original === null || $original === '') {
                        $bar->advance();

                        continue;
                    }

                    $clean = clean($original, 'default');
                    if ($clean !== $original) {
                        $changed++;
                        if (! $dry) {
                            // Use saveQuietly + forceFill so we bypass our
                            // saving hook recompute — we WANT it to
                            // recompute since content changed.
                            $article->content = $clean;
                            $article->save();
                        }
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf(
            '%s%d/%d articles had markup adjusted.',
            $dry ? '[dry-run] ' : '',
            $changed,
            $total,
        ));

        return self::SUCCESS;
    }
}

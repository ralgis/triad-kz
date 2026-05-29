<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Diagnostic dump for the media subsystem — prints what we usually
 * need to debug 403/missing-conversion issues on Plesk shared.
 * Output reads top-to-bottom: env, extensions, queue, media counts,
 * then a deep-dive on one media row including disk existence
 * checks for the original and each conversion.
 */
final class TriadDiagCommand extends Command
{
    protected $signature = 'triad:diag {--id=20 : Media row to deep-inspect}';

    protected $description = 'Diagnose media subsystem on Plesk';

    public function handle(): int
    {
        $this->info('=== ENV ===');
        $this->line('app.env       = '.config('app.env'));
        $this->line('app.url       = '.config('app.url'));
        $this->line('app.debug     = '.(config('app.debug') ? 'true' : 'false'));
        $this->line('PHP version   = '.PHP_VERSION);

        $this->info('=== PHP image extensions ===');
        $this->line('gd            = '.(extension_loaded('gd') ? 'YES' : 'NO'));
        $this->line('imagick       = '.(extension_loaded('imagick') ? 'YES' : 'NO'));
        $this->line('exif          = '.(extension_loaded('exif') ? 'YES' : 'NO'));
        $this->line('fileinfo      = '.(extension_loaded('fileinfo') ? 'YES' : 'NO'));

        if (extension_loaded('gd')) {
            $info = gd_info();
            $this->line('GD JPEG       = '.($info['JPEG Support'] ?? 'unknown'));
            $this->line('GD PNG        = '.($info['PNG Support'] ?? 'unknown'));
        }

        $this->info('=== Queue / Spatie config ===');
        $this->line('queue.default          = '.config('queue.default'));
        $this->line('media.queue_conn       = '.config('media-library.queue_connection_name'));
        $this->line('media.queue_by_default = '.(config('media-library.queue_conversions_by_default') ? 'true' : 'false'));
        $this->line('media.disk_name        = '.config('media-library.disk_name'));
        $this->line('media.path_generator   = '.config('media-library.path_generator'));

        $this->info('=== DB counts ===');
        $this->line('media total      = '.Media::count());
        foreach (DB::table('media')->select('collection_name', DB::raw('COUNT(*) AS n'))->groupBy('collection_name')->get() as $row) {
            $this->line('  '.$row->collection_name.' = '.$row->n);
        }

        $this->info('=== Storage disk "public" ===');
        $disk = Storage::disk('public');
        $this->line('root path = '.$disk->path(''));
        $this->line('writable  = '.(is_writable($disk->path('')) ? 'YES' : 'NO'));

        $id = (int) $this->option('id');
        $this->info("=== Deep dive on media id={$id} ===");

        $media = Media::find($id);
        if (! $media) {
            $this->error("Media row id={$id} not found");

            return self::SUCCESS;
        }

        $this->line('model_type      = '.$media->model_type);
        $this->line('collection_name = '.$media->collection_name);
        $this->line('name            = '.$media->name);
        $this->line('file_name       = '.$media->file_name);
        $this->line('disk            = '.$media->disk);
        $this->line('size            = '.$media->size);

        $originalPath = $media->getPath();
        $this->line('original path   = '.$originalPath);
        $this->line('original exists = '.(file_exists($originalPath) ? 'YES' : 'NO'));
        $this->line('original perms  = '.(file_exists($originalPath) ? substr(sprintf('%o', fileperms($originalPath)), -4) : '—'));

        $convDir = dirname($media->getPath('card'));
        $this->line('conv dir path   = '.$convDir);
        $this->line('conv dir exists = '.(is_dir($convDir) ? 'YES' : 'NO'));
        if (! is_dir($convDir)) {
            $parent = dirname($convDir);
            $this->line('parent dir      = '.$parent);
            $this->line('parent writable = '.(is_writable($parent) ? 'YES' : 'NO'));
        }

        foreach (['thumb', 'card', 'og', 'mobile'] as $conv) {
            $p = $media->getPath($conv);
            $this->line(sprintf(
                '  conv %-7s exists=%s perms=%s path=%s',
                $conv,
                file_exists($p) ? 'YES' : 'NO',
                file_exists($p) ? substr(sprintf('%o', fileperms($p)), -4) : '—',
                $p,
            ));
        }

        return self::SUCCESS;
    }
}

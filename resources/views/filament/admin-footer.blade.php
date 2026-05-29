@php
    // Short git ref for «which revision is live» visibility. .git/HEAD
    // is the cheapest source — no shell_exec needed (Plesk shared
    // blocks proc_open anyway). Falls back gracefully if anything
    // doesn't read.
    $gitRef = null;
    $headFile = base_path('.git/HEAD');
    if (is_readable($headFile)) {
        $head = trim((string) @file_get_contents($headFile));
        if (str_starts_with($head, 'ref:')) {
            $refPath = base_path('.git/'.trim(substr($head, 4)));
            if (is_readable($refPath)) {
                $gitRef = substr(trim((string) @file_get_contents($refPath)), 0, 7);
            }
        } else {
            // Detached HEAD — full hash in HEAD itself.
            $gitRef = substr($head, 0, 7);
        }
    }

    $siteName = \App\Models\Setting::current()->site_name ?? 'ТРИ АД Construction';
    $env = app()->environment();
@endphp

<footer class="px-4 sm:px-6 lg:px-8 py-3 mt-6 border-t border-gray-200 dark:border-white/10 text-xs text-gray-500 dark:text-gray-400">
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span>© {{ date('Y') }} {{ $siteName }}</span>

            @if($env !== 'production')
                {{--
                    Arbitrary utility classes (text-[10px], etc.)
                    won't ship in Filament's pre-built CSS bundle —
                    using a hand-rolled style for the badge fontsize
                    so it stays consistently small regardless of the
                    admin theme's utility coverage.
                --}}
                <span class="inline-flex items-center px-1.5 py-0.5 rounded font-semibold uppercase tracking-wide bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                      style="font-size: 10px; line-height: 14px;">
                    {{ $env }}
                </span>
            @endif

            @if($gitRef)
                <span class="font-mono text-gray-400 dark:text-gray-500" title="git revision">{{ $gitRef }}</span>
            @endif
        </div>

        <a href="{{ url('/') }}"
           target="_blank"
           rel="noopener"
           class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
            Открыть сайт
            {{--
                Width/height as HTML attributes — `size-3.5` Tailwind
                utility isn't in Filament's pre-built admin CSS, so the
                SVG was rendering at viewport intrinsic size (the giant
                arrow user reported). Native attributes guarantee size
                regardless of which utility classes are available.
            --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="14" height="14"
                 fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 3h7m0 0v7m0-7l-9 9M5 5v14h14"/>
            </svg>
        </a>
    </div>
</footer>

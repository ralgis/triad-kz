<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

/**
 * Universal renderer for /{slug} content pages (about, gosts, payment,
 * etc.). Registered as the LAST route in web.php so /catalog, /cart,
 * /checkout and friends take precedence; falls through to 404 →
 * missing-page-redirector if no Page with this slug exists.
 *
 * /contacts is the exception — it ships its own controller because of
 * the lead-form POST handling.
 */
final class PageController extends Controller
{
    public function show(Page $page): View
    {
        // /contacts has dedicated routing; if someone hits /contacts via
        // the catch-all (shouldn't happen with declared order) we still
        // 404 here to keep one true URL.
        abort_if($page->slug === 'contacts', 404);

        return view('page', compact('page'));
    }
}

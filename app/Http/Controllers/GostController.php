<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Gost;
use Illuminate\View\View;

/**
 * Public listing for «ГОСТы и Серии».
 *
 * Single-page accordion (mirrors the original triad.kz layout). Detail
 * URLs are anchor fragments — /gosts/#gost-8020-90 — so any incoming
 * link from a product card opens the accordion on the right item.
 *
 * Dedicated controller (not routed through PageController catch-all)
 * because this page renders DB rows, not a static Page record.
 */
final class GostController extends Controller
{
    public function index(): View
    {
        $gosts = Gost::query()
            ->ordered()
            ->get();

        return view('gosts.index', compact('gosts'));
    }
}

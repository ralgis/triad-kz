<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catalog browse — root list + single category. Product detail is
 * handled separately (ProductController) because its URL nests under
 * the category slug.
 */
final class CatalogController extends Controller
{
    /**
     * Numeric filter inputs the category page exposes. Tuple:
     *   [column, label, unit, step]
     * The same array drives the «available filters» discovery (skip
     * inputs whose column has no data in the category) AND the
     * actual query-builder where-clauses.
     *
     * @var list<array{0:string,1:string,2:string,3:float}>
     */
    private const NUMERIC_FILTERS = [
        ['length_mm', 'Длина', 'мм', 1],
        ['width_mm', 'Ширина', 'мм', 1],
        ['height_mm', 'Высота', 'мм', 1],
        ['inner_diameter_mm', 'Внутр. диаметр', 'мм', 1],
        ['outer_diameter_mm', 'Внеш. диаметр', 'мм', 1],
        ['plate_diameter_mm', 'Диаметр плиты', 'мм', 1],
        ['weight_t', 'Вес', 'т', 0.001],
    ];

    public function index(): View
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('published', true)
            ->where('listed', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('catalog.index', compact('categories'));
    }

    public function show(Category $category, Request $request): View
    {
        // 404 unpublished. `listed=false` still serves the page —
        // that's the whole point of the flag (direct URL works,
        // category just doesn't appear in the parent's listings or
        // the sitemap).
        abort_unless($category->published, 404);

        $children = $category->children()
            ->where('published', true)
            ->where('listed', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $filterMeta = $this->discoverAvailableFilters($category);

        // Fresh relation for the listing — we don't clone() the
        // earlier one because BelongsToMany's PHP-level shallow clone
        // leaks the inner query builder, leading to filters bleeding
        // across queries.
        $productsQuery = $this->publishedProducts($category)
            ->with(['gosts', 'categories:id,slug']);

        $this->applyFilters($productsQuery, $request, $filterMeta);

        $products = $productsQuery
            ->orderBy('products.name')
            ->paginate(12)
            ->withQueryString();

        return view('catalog.category', [
            'category' => $category,
            'children' => $children,
            'products' => $products,
            'filterMeta' => $filterMeta,
            'activeFilters' => $this->summarizeActive($request, $filterMeta),
        ]);
    }

    /**
     * Base scope: published + listed products in this category.
     * Returned fresh each call so callers don't share state.
     */
    private function publishedProducts(Category $category): BelongsToMany
    {
        return $category->products()
            ->where('products.published', true)
            ->where('products.listed', true);
    }

    /**
     * For each filter slot, return null if no product in the category
     * has data for that column. Otherwise return the (min,max) for
     * numeric or a list of distinct values for the categorical grade.
     *
     * @return array{numeric: array<string, array{label:string,unit:string,step:float,min:int|float,max:int|float}>, grades: array<int, string>}
     */
    private function discoverAvailableFilters(Category $category): array
    {
        $numeric = [];
        foreach (self::NUMERIC_FILTERS as [$column, $label, $unit, $step]) {
            $row = $this->publishedProducts($category)
                ->whereNotNull("products.{$column}")
                ->selectRaw("MIN(products.{$column}) as min_v, MAX(products.{$column}) as max_v")
                ->first();

            if ($row === null || $row->min_v === null) {
                continue;
            }

            $numeric[$column] = [
                'label' => $label,
                'unit' => $unit,
                'step' => $step,
                'min' => $step < 1 ? (float) $row->min_v : (int) $row->min_v,
                'max' => $step < 1 ? (float) $row->max_v : (int) $row->max_v,
            ];
        }

        $grades = $this->publishedProducts($category)
            ->whereNotNull('products.concrete_grade')
            ->distinct()
            ->orderBy('products.concrete_grade')
            ->pluck('products.concrete_grade')
            ->all();

        return ['numeric' => $numeric, 'grades' => $grades];
    }

    /**
     * @param  array{numeric: array<string, array{label:string,unit:string,step:float,min:int|float,max:int|float}>, grades: array<int, string>}  $meta
     */
    private function applyFilters(BelongsToMany $query, Request $request, array $meta): void
    {
        foreach (array_keys($meta['numeric']) as $column) {
            $min = $request->query("{$column}_min");
            $max = $request->query("{$column}_max");
            if (is_numeric($min)) {
                $query->where("products.{$column}", '>=', $min);
            }
            if (is_numeric($max)) {
                $query->where("products.{$column}", '<=', $max);
            }
        }

        $grades = array_filter((array) $request->query('grades', []), 'is_string');
        if ($grades && $meta['grades']) {
            $query->whereIn('products.concrete_grade', $grades);
        }
    }

    /**
     * Build a flat «(label, value)» list of active filter chips so
     * the view can render them with X-to-remove links.
     *
     * @param  array{numeric: array<string, array{label:string,unit:string,step:float,min:int|float,max:int|float}>, grades: array<int, string>}  $meta
     * @return list<array{key:string, label:string}>
     */
    private function summarizeActive(Request $request, array $meta): array
    {
        $chips = [];

        foreach ($meta['numeric'] as $column => $info) {
            $min = $request->query("{$column}_min");
            $max = $request->query("{$column}_max");
            if (is_numeric($min) || is_numeric($max)) {
                $chips[] = [
                    'key' => $column,
                    'label' => sprintf(
                        '%s: %s—%s %s',
                        $info['label'],
                        is_numeric($min) ? $min : '…',
                        is_numeric($max) ? $max : '…',
                        $info['unit'],
                    ),
                ];
            }
        }

        $grades = array_filter((array) $request->query('grades', []), 'is_string');
        foreach ($grades as $g) {
            $chips[] = ['key' => 'grades['.$g.']', 'label' => 'Марка: '.$g];
        }

        return $chips;
    }
}

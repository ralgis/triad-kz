<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Sort key → [label, column, direction]. `_nulls_last` means
     * products with NULL in that column are pushed to the end
     * regardless of asc/desc (otherwise MySQL would put them first
     * on ASC and we'd push empty cards in front of priced/weighted
     * ones).
     *
     * @var array<string, array{0:string,1:string,2:string}>
     */
    private const SORT_OPTIONS = [
        'name' => ['По названию (А-Я)', 'products.name', 'asc'],
        'name_desc' => ['По названию (Я-А)', 'products.name', 'desc'],
        'price_asc' => ['Цена: сначала дешевле', 'products.price', 'asc_nulls_last'],
        'price_desc' => ['Цена: сначала дороже', 'products.price', 'desc_nulls_last'],
        'weight_asc' => ['Вес: сначала легче', 'products.weight_t', 'asc_nulls_last'],
        'weight_desc' => ['Вес: сначала тяжелее', 'products.weight_t', 'desc_nulls_last'],
    ];

    private const DEFAULT_SORT = 'name';

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
        $this->applySearch($productsQuery, $request);

        $sort = $this->resolveSort((string) $request->query('sort', ''));
        $this->applySort($productsQuery, $sort);

        $products = $productsQuery
            ->paginate(12)
            ->withQueryString();

        return view('catalog.category', [
            'category' => $category,
            'children' => $children,
            'products' => $products,
            'filterMeta' => $filterMeta,
            'activeFilters' => $this->summarizeActive($request, $filterMeta),
            'sortOptions' => array_map(fn ($v) => $v[0], self::SORT_OPTIONS),
            'activeSort' => $sort,
            'searchQuery' => (string) $request->query('q', ''),
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
     * Discovery queries can't use the BelongsToMany relation because
     * Eloquent always appends the pivot columns
     * (category_product.category_id, category_product.product_id) to
     * the SELECT — mixing them with MIN/MAX in strict MySQL
     * (only_full_group_by) raises «Mixing of GROUP columns is illegal
     * if there is no GROUP BY clause» (1140). Dropping to the raw
     * query builder lets us write a clean aggregate-only SELECT.
     */
    private function categoryProductsBase(Category $category): QueryBuilder
    {
        return DB::table('products')
            ->join('category_product', 'category_product.product_id', '=', 'products.id')
            ->where('category_product.category_id', $category->id)
            ->where('products.published', true)
            ->where('products.listed', true)
            ->whereNull('products.deleted_at');
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
            $row = $this->categoryProductsBase($category)
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

        $grades = $this->categoryProductsBase($category)
            ->whereNotNull('products.concrete_grade')
            ->distinct()
            ->orderBy('products.concrete_grade')
            ->pluck('products.concrete_grade')
            ->all();

        return ['numeric' => $numeric, 'grades' => $grades];
    }

    /**
     * @param array{numeric: array<string, array{label:string,unit:string,step:float,min:int|float,max:int|float}>, grades: array<int, string>} $meta
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

    private function applySearch(BelongsToMany $query, Request $request): void
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
        $query->where(function ($w) use ($like) {
            $w->where('products.name', 'like', $like)
                ->orWhere('products.sku', 'like', $like);
        });
    }

    private function resolveSort(string $key): string
    {
        return array_key_exists($key, self::SORT_OPTIONS) ? $key : self::DEFAULT_SORT;
    }

    private function applySort(BelongsToMany $query, string $sortKey): void
    {
        [, $column, $direction] = self::SORT_OPTIONS[$sortKey];

        // MySQL puts NULLs first on ASC and last on DESC — fine for
        // strings but wrong UX for price/weight where empty values
        // should always sit at the tail. The `IS NULL` trick adds an
        // implicit secondary ordering that pushes them down.
        if (str_ends_with($direction, '_nulls_last')) {
            $base = str_replace('_nulls_last', '', $direction);
            $query->orderByRaw("{$column} IS NULL")
                ->orderBy($column, $base);
        } else {
            $query->orderBy($column, $direction);
        }
    }

    /**
     * Build a flat «(label, value)» list of active filter chips so
     * the view can render them with X-to-remove links.
     *
     * @param array{numeric: array<string, array{label:string,unit:string,step:float,min:int|float,max:int|float}>, grades: array<int, string>} $meta
     * @return list<array{key:string, label:string}>
     */
    private function summarizeActive(Request $request, array $meta): array
    {
        $chips = [];

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $chips[] = ['key' => 'q', 'label' => 'Поиск: «'.$q.'»'];
        }

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

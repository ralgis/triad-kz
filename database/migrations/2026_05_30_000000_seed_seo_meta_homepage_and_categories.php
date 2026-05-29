<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds homepage SEO meta columns to settings AND backfills both the
 * homepage and the 9 catalog categories with commercial-intent
 * Title/Description copy aimed at Алматы B2B queries.
 *
 * Idempotent: existing values are NEVER overwritten. The seed only
 * populates rows where meta_title / meta_description is currently
 * null or empty string. So if the admin edits any of these later
 * via Filament, a re-run of `migrate` won't blow away their work.
 *
 * Source for copy: «План работ для triad.kz» month-1, «Оптимизация
 * мета-тегов главной страницы и категорий». Drafts here are starting
 * points — final edits land in Filament Settings → SEO tab and
 * Categories → SEO section.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{title:string, description:string}>
     */
    private const CATEGORY_SEO = [
        'beton-koltsa' => [
            'title' => 'Бетонные кольца КС10–КС20 в Алматы — ГОСТ 8020-90',
            'description' => 'Бетонные кольца для колодцев в Алматы: КС10.6, КС15.9, КС20.6 от производителя. ГОСТ 8020-90, серия 3.900.1-14. Доставка по Казахстану.',
        ],
        'plity-perekrytiya' => [
            'title' => 'Плиты перекрытия колодцев Алматы — ПП10/15/20, ГОСТ 8020-90',
            'description' => 'Железобетонные плиты перекрытия колодцев ПП10-1, 1ПП15-1, 1ПП20-1 от производителя. ГОСТ 8020-90. Производство и доставка в Алматы.',
        ],
        'plity-dnishcha' => [
            'title' => 'Плиты днища колодцев ПН10–ПН20 Алматы — ГОСТ 8020-90',
            'description' => 'Бетонные плиты днища колодцев ПН10, ПН15, ПН20 от производителя в Алматы. ГОСТ 8020-90, серия 3.900.1-14. Поставка по Казахстану.',
        ],
        'opornye-koltsa' => [
            'title' => 'Опорные кольца КО6 в Алматы — ГОСТ 8020-90 | ТРИ АД',
            'description' => 'Железобетонные опорные кольца КО6 для регулировки люков колодцев. ГОСТ 8020-90, серия 3.900.1-14. Производство в Алматы. Доставка по РК.',
        ],
        'fbs' => [
            'title' => 'Фундаментные блоки ФБС в Алматы — ФБС9, ФБС12, ФБС24',
            'description' => 'Фундаментные блоки ФБС6, ФБС8, ФБС9, ФБС12, ФБС24 для стен подвалов. ГОСТ 13579-78. Доставка по Алматы и Казахстану. Цена с завода.',
        ],
        'plity-lotkov-teplotrass' => [
            'title' => 'Плиты лотков теплотрасс П6–П11 Алматы — Серия 3.006.1-2.87',
            'description' => 'Железобетонные плиты перекрытия лотков теплотрасс П6-15, П8-8, П11-8. Серия 3.006.1-2.87. Производство в Алматы и доставка по РК.',
        ],
        'opornye-podushki' => [
            'title' => 'Опорные подушки ОП1–ОП9 Алматы — Серия 3.006.1-2.87',
            'description' => 'Бетонные опорные подушки ОП1–ОП9 для трубопроводов теплотрасс. Серия 3.006.1-2.87 выпуск 2. Завод в Алматы. Доставка по Казахстану.',
        ],
        'arychnye-lotki' => [
            'title' => 'Арычные лотки Б-3 в Алматы — производство ЖБИ | ТРИ АД',
            'description' => 'Бетонные арычные лотки Б-3 для отведения арычных вод и поверхностного дренажа. Производство по СТ ТОО 40212232-03-2008 в Алматы.',
        ],
        'setka-svarnaya' => [
            'title' => 'Сетка сварная в Алматы — производство и продажа | ТРИ АД',
            'description' => 'Сетка сварная различных размеров и диаметров прутка от производителя в Алматы. Для армирования, строительства и ограждения.',
        ],
    ];

    private const HOMEPAGE_TITLE = 'Завод ЖБИ ТРИ АД Construction — Алматы | Кольца, ФБС, плиты';

    private const HOMEPAGE_DESCRIPTION = 'Производство и продажа железобетонных изделий в Алматы: бетонные кольца КС, плиты перекрытия, фундаментные блоки ФБС, опорные подушки. Доставка по РК.';

    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('home_meta_title')->nullable()->after('analytics_enabled');
            $table->string('home_meta_description', 500)->nullable()->after('home_meta_title');
        });

        // Homepage seed: only populate when admin hasn't already filled them.
        $current = DB::table('settings')->where('id', 1)->first();
        if ($current !== null) {
            $updates = [];
            if (empty($current->home_meta_title)) {
                $updates['home_meta_title'] = self::HOMEPAGE_TITLE;
            }
            if (empty($current->home_meta_description)) {
                $updates['home_meta_description'] = self::HOMEPAGE_DESCRIPTION;
            }
            if ($updates !== []) {
                DB::table('settings')->where('id', 1)->update($updates);
            }
        }

        // Categories: same idempotent rule per row.
        foreach (self::CATEGORY_SEO as $slug => $meta) {
            $category = DB::table('categories')->where('slug', $slug)->first();
            if ($category === null) {
                continue;
            }

            $updates = [];
            if (empty($category->meta_title)) {
                $updates['meta_title'] = $meta['title'];
            }
            if (empty($category->meta_description)) {
                $updates['meta_description'] = $meta['description'];
            }
            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('categories')->where('slug', $slug)->update($updates);
            }
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['home_meta_title', 'home_meta_description']);
        });

        // Categories.meta_title / meta_description NOT cleared on
        // rollback — they're general fields admin uses for any source
        // of content, not a column we own.
    }
};

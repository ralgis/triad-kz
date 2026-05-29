<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Gost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds the «ГОСТы и Серии» reference with the 5 entries that lived in
 * the legacy WP accordion page (post_id=3204). Idempotent — keyed by
 * slug so re-runs update label/description without duplicating rows.
 *
 * The legacy page is the source of truth for the descriptions;
 * normalizations applied here:
 *   - «Серия 3.006.1-2/82(87)» and «Серия 3.006.1-2.87 Выпуск 2» in
 *     product descriptions both map to the canonical «Серия 3.006.1-2.87»
 *     entry — same series, different free-text variants.
 *   - «Серия 3.900.1-14 (выпуск 1)» keeps its «(выпуск 1)» suffix to
 *     match exact accordion label; products that say just
 *     «Серия 3.900.1-14» still match by code.
 */
final class GostsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $rows = [
            [
                'slug' => 'gost-8020-90',
                'kind' => Gost::KIND_GOST,
                'label' => 'ГОСТ 8020-90',
                'code' => '8020-90',
                'description' => '<p>Конструкции бетонные и железобетонные для колодцев канализационных, водопроводных и газопроводных сетей.</p>',
                'sort_order' => 10,
            ],
            [
                'slug' => 'seriya-3-900-1-14',
                'kind' => Gost::KIND_SERIYA,
                'label' => 'Серия 3.900.1-14 (выпуск 1)',
                'code' => '3.900.1-14',
                'description' => '<p>Изделия железобетонные для круглых колодцев водопровода и канализации. Указания по применению и рабочие чертежи.</p>',
                'sort_order' => 20,
            ],
            [
                'slug' => 'seriya-3-900-3',
                'kind' => Gost::KIND_SERIYA,
                'label' => 'Серия 3.900-3 (выпуск 7)',
                'code' => '3.900-3',
                'description' => '<p>Сборные железобетонные конструкции емкостных сооружений для водоснабжения и канализации. Изделия для круглых колодцев.</p>',
                'sort_order' => 30,
            ],
            [
                'slug' => 'gost-13579-78',
                'kind' => Gost::KIND_GOST,
                'label' => 'ГОСТ 13579-78',
                'code' => '13579-78',
                'description' => '<p>Блоки бетонные для стен подвалов.</p>',
                'sort_order' => 40,
            ],
            [
                'slug' => 'seriya-3-006-1-2-87',
                'kind' => Gost::KIND_SERIYA,
                'label' => 'Серия 3.006.1-2.87',
                'code' => '3.006.1-2.87',
                'description' => <<<'HTML'
<p><strong>Выпуск 0</strong> — материалы для проектирования.</p>
<p><strong>Выпуск 1</strong> — Лотки. Рабочие чертежи.</p>
<p><strong>Выпуск 2</strong> — Плиты, опорные подушки. Рабочие чертежи.</p>
<p><strong>Выпуск 3</strong> — Лотки, арматура и закладные изделия. Рабочие чертежи.</p>
<p><strong>Выпуск 4</strong> — Плиты, опорные подушки. Рабочие чертежи.</p>
<p><strong>Выпуск 5</strong> — Узлы трасс. Рабочие чертежи.</p>
<p><strong>Выпуск 6</strong> — Узлы трасс. Лотки, плиты, балки. Рабочие чертежи.</p>
<p><strong>Выпуск 7</strong> — Узлы трасс. Лотки, плиты, балки. Арматура и закладные изделия. Рабочие чертежи.</p>
HTML,
                'sort_order' => 50,
            ],
        ];

        foreach ($rows as $row) {
            Gost::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}

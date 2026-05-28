<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight Russian → Latin transliteration for slugs. Follows the
 * GOST 7.79-2000 / Yandex style — easy to read for keyboard URL entry
 * and matches the Phase 4 plan's pre-agreed category slugs
 * (beton-koltsa, fbs, opornye-podushki, …).
 *
 * Why hand-rolled: we transliterate maybe 50 strings in the whole
 * project (categories + products at seed time, optionally articles).
 * A composer package would carry more rules than we need.
 */
final class Translit
{
    /**
     * @var array<string, string>
     */
    private const MAP = [
        'а' => 'a',  'б' => 'b',  'в' => 'v',  'г' => 'g',  'д' => 'd',
        'е' => 'e',  'ё' => 'e',  'ж' => 'zh', 'з' => 'z',  'и' => 'i',
        'й' => 'y',  'к' => 'k',  'л' => 'l',  'м' => 'm',  'н' => 'n',
        'о' => 'o',  'п' => 'p',  'р' => 'r',  'с' => 's',  'т' => 't',
        'у' => 'u',  'ф' => 'f',  'х' => 'h',  'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',   'ы' => 'y',  'ь' => '',
        'э' => 'e',  'ю' => 'yu', 'я' => 'ya',
    ];

    public static function slug(string $value): string
    {
        $lower = mb_strtolower($value);
        $translit = strtr($lower, self::MAP);
        // Strip remaining non-ASCII (punctuation, parentheses) and squash.
        $clean = preg_replace('/[^a-z0-9]+/u', '-', $translit) ?? '';

        return trim($clean, '-');
    }
}

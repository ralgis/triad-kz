<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Maps Product.dimensions JSON keys (English snake_case from the factory /
 * admin form) to Russian labels for catalog display. Centralizing here
 * lets us add labels for new dimension types (e.g. ЖБИ-specific
 * radius/inner_diameter) without touching templates.
 */
final class DimensionLabels
{
    /**
     * @var array<string, string>
     */
    private const LABELS = [
        'diameter' => 'Диаметр',
        'inner_diameter' => 'Внутренний диаметр',
        'outer_diameter' => 'Наружный диаметр',
        'height' => 'Высота',
        'width' => 'Ширина',
        'length' => 'Длина',
        'wall' => 'Толщина стенки',
        'thickness' => 'Толщина',
        'radius' => 'Радиус',
    ];

    public static function label(string $key): string
    {
        return self::LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}

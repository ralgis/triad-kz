<?php

declare(strict_types=1);

namespace App\Enums;

enum MenuPosition: string
{
    case Header = 'header';
    case Footer = 'footer';
    case FooterSecondary = 'footer_secondary';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Главное меню (шапка)',
            self::Footer => 'Футер (основной)',
            self::FooterSecondary => 'Футер (доп. колонка)',
        };
    }
}

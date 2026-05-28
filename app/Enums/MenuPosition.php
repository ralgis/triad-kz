<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuPosition: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->label();
    }
}

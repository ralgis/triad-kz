<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CustomerType: string implements HasLabel
{
    case Individual = 'individual';
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Физическое лицо',
            self::Legal => 'Юридическое лицо',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}

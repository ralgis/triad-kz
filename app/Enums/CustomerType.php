<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerType: string
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
}

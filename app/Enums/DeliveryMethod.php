<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeliveryMethod: string implements HasLabel
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Самовывоз',
            self::Delivery => 'Доставка по адресу',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}

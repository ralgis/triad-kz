<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryMethod: string
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
}

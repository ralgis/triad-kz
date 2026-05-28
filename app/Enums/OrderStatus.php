<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Invoiced = 'invoiced';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Confirmed => 'Подтверждён',
            self::Invoiced => 'Счёт выставлен',
            self::Paid => 'Оплачен',
            self::Shipped => 'Отгружен',
            self::Completed => 'Завершён',
            self::Cancelled => 'Отменён',
        };
    }

    /**
     * Tailwind color hint for Filament badges and email status indicators.
     */
    public function color(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Confirmed => 'info',
            self::Invoiced => 'warning',
            self::Paid => 'success',
            self::Shipped => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Безналичный расчёт (счёт на оплату)',
            self::Cash => 'Наличный расчёт',
        };
    }

    /**
     * Bank-transfer orders trigger PDF invoice generation.
     */
    public function generatesInvoice(): bool
    {
        return $this === self::BankTransfer;
    }
}

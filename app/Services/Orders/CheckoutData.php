<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;

/**
 * Strongly-typed input for OrderService::create().
 *
 * Validated/built upstream by CheckoutFormRequest (Phase 1.4); the service
 * trusts the data is already shape-correct.
 */
final class CheckoutData
{
    public function __construct(
        public readonly CustomerType $customerType,
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $customerPhone,
        public readonly ?string $customerCompanyName,
        public readonly ?string $customerBin,
        public readonly ?string $customerAddress,
        public readonly DeliveryMethod $deliveryMethod,
        public readonly ?string $deliveryAddress,
        public readonly PaymentMethod $paymentMethod,
        public readonly ?string $comment,
    ) {}
}

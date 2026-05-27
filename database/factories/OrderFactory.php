<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(10000, 500000);

        return [
            'order_number' => 'T-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_type' => CustomerType::Individual,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '+7'.fake()->numerify('##########'),
            'delivery_method' => DeliveryMethod::Pickup,
            'payment_method' => PaymentMethod::Cash,
            'subtotal' => $total,
            'total' => $total,
            'status' => OrderStatus::New,
        ];
    }

    public function legal(): static
    {
        return $this->state([
            'customer_type' => CustomerType::Legal,
            'customer_company_name' => 'ТОО '.fake()->company(),
            'customer_bin' => (string) fake()->numerify('############'),
            'payment_method' => PaymentMethod::BankTransfer,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(['payment_method' => PaymentMethod::BankTransfer]);
    }

    public function withDelivery(): static
    {
        return $this->state([
            'delivery_method' => DeliveryMethod::Delivery,
            'delivery_address' => fake()->address(),
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function paid(): static
    {
        return $this->status(OrderStatus::Paid);
    }

    public function cancelled(): static
    {
        return $this->status(OrderStatus::Cancelled);
    }
}

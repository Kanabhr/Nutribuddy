<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => $this->faker->randomElement(['razorpay', 'cashfree', 'cod']),
            'transaction_type' => 'payment',
            'status' => $this->faker->randomElement(['pending', 'paid', 'failed']),
            'currency' => 'INR',
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'gateway_order_id' => $this->faker->uuid(),
            'gateway_payment_id' => null,
            'gateway_signature' => null,
            'gateway_payload' => null,
            'notes' => null,
            'paid_at' => null,
        ];
    }
}

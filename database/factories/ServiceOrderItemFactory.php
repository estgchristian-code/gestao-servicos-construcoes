<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderItem>
 */
class ServiceOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitPrice = fake()->randomFloat(2, 10, 2000);
        $subtotal = round($quantity * $unitPrice, 2);

        return [
            'company_id' => Company::factory(),
            'service_order_id' => ServiceOrder::factory(),
            'service_id' => Service::factory(),
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ];
    }

    /**
     * Attach the item to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    /**
     * Attach the item to the given service order.
     */
    public function serviceOrder(ServiceOrder $order): static
    {
        return $this->state(fn (array $attributes) => ['service_order_id' => $order->id]);
    }

    /**
     * Attach the item to the given service.
     */
    public function service(Service $service): static
    {
        return $this->state(fn (array $attributes) => ['service_id' => $service->id]);
    }
}
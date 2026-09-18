<?php

namespace Database\Factories;

use App\Enums\ServiceOrderExecutionEventType;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderExecutionEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderExecutionEvent>
 */
class ServiceOrderExecutionEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'service_order_id' => ServiceOrder::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(ServiceOrderExecutionEventType::cases()),
            'notes' => fake()->optional(0.6)->sentence(6),
        ];
    }

    /**
     * Attach the event to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    /**
     * Attach the event to the given service order.
     */
    public function serviceOrder(ServiceOrder $order): static
    {
        return $this->state(fn (array $attributes) => ['service_order_id' => $order->id]);
    }

    /**
     * Attach the event to the given user.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ]);
    }

    /**
     * Set the event type.
     */
    public function type(ServiceOrderExecutionEventType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }
}
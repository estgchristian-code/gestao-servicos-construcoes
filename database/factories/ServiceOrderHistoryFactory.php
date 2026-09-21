<?php

namespace Database\Factories;

use App\Enums\ServiceOrderHistoryType;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderHistory>
 */
class ServiceOrderHistoryFactory extends Factory
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
            'type' => ServiceOrderHistoryType::Created,
            'description' => 'Ordem de serviço criada.',
        ];
    }

    /**
     * Attach the history event to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    /**
     * Attach the history event to the given service order.
     */
    public function serviceOrder(ServiceOrder $order): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $order->company_id,
            'service_order_id' => $order->id,
        ]);
    }

    /**
     * Attach the history event to the given responsible user.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $user->id]);
    }
}
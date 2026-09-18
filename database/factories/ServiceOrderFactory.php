<?php

namespace Database\Factories;

use App\Enums\ServiceOrderStatus;
use App\Models\Budget;
use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrder>
 */
class ServiceOrderFactory extends Factory
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
            'budget_id' => fake()->boolean(50) ? Budget::factory() : null,
            'client_id' => Client::factory(),
            'number' => (string) fake()->unique()->numberBetween(100000, 999999),
            'title' => fake()->randomElement([
                'Instalação de ar-condicionado',
                'Manutenção preventiva',
                'Reparo emergencial',
                'Dedetização',
            ]),
            'status' => ServiceOrderStatus::Pending,
            'scheduled_at' => fake()->optional(0.7)->dateTimeBetween('today', '+2 weeks'),
            'notes' => fake()->optional(0.5)->paragraph(2),
        ];
    }

    /**
     * Attach the service order to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    /**
     * Attach the service order to the given client.
     */
    public function client(Client $client): static
    {
        return $this->state(fn (array $attributes) => ['client_id' => $client->id]);
    }

    /**
     * Attach the service order to the given budget.
     */
    public function budget(Budget $budget): static
    {
        return $this->state(fn (array $attributes) => ['budget_id' => $budget->id]);
    }

    /**
     * Attach the service order to the given status.
     */
    public function status(ServiceOrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
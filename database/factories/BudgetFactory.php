<?php

namespace Database\Factories;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
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
            'client_id' => Client::factory(),
            'number' => (string) fake()->unique()->numberBetween(100000, 999999),
            'title' => fake()->randomElement([
                'Orçamento de instalação',
                'Orçamento de manutenção',
                'Orçamento de reparo',
                'Orçamento de dedetização',
            ]),
            'status' => BudgetStatus::Draft,
            'total' => fake()->randomFloat(2, 100, 50000),
            'valid_until' => fake()->optional(0.7)->dateTimeBetween('+1 week', '+3 months'),
            'notes' => fake()->optional(0.5)->paragraph(2),
        ];
    }

    /**
     * Attach the budget to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    /**
     * Attach the budget to the given client.
     */
    public function client(Client $client): static
    {
        return $this->state(fn (array $attributes) => ['client_id' => $client->id]);
    }

    /**
     * Attach the budget to the given status.
     */
    public function status(BudgetStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
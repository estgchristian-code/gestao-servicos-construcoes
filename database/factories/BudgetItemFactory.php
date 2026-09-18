<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Company;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetItem>
 */
class BudgetItemFactory extends Factory
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
            'budget_id' => Budget::factory(),
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
     * Attach the item to the given budget.
     */
    public function budget(Budget $budget): static
    {
        return $this->state(fn (array $attributes) => ['budget_id' => $budget->id]);
    }

    /**
     * Attach the item to the given service.
     */
    public function service(Service $service): static
    {
        return $this->state(fn (array $attributes) => ['service_id' => $service->id]);
    }
}
<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Company;
use App\Support\Documents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['pf', 'pj']);
        $document = $type === 'pf' ? Documents::generateCpf() : Documents::generateCnpj();

        return [
            'company_id' => Company::factory(),
            'type' => $type,
            'name' => $type === 'pf' ? fake()->name() : fake()->company(),
            'document' => $document,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'whatsapp' => fake()->phoneNumber(),
            'notes' => fake()->optional(0.4)->paragraph(2),
            'tags' => fake()->optional(0.5)->randomElements(['vip', 'recorrente', 'indicado', 'premium'], 2),
            'active' => true,
        ];
    }

    /**
     * Person (CPF).
     */
    public function pf(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pf',
            'name' => fake()->name(),
            'document' => Documents::generateCpf(),
        ]);
    }

    /**
     * Company (CNPJ).
     */
    public function pj(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pj',
            'name' => fake()->company(),
            'document' => Documents::generateCnpj(),
        ]);
    }

    /**
     * Inactive client.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Attach the client to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }
}
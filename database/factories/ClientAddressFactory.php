<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientAddress>
 */
class ClientAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'label' => fake()->randomElement(['Casa', 'Trabalho', 'Sede', 'Filial']),
            'street' => fake()->streetName(),
            'number' => (string) fake()->numberBetween(1, 9999),
            'complement' => fake()->optional(0.4)->words(2, true),
            'district' => fake()->optional(0.6)->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->numerify('#####-###'),
            'reference' => fake()->optional(0.3)->sentence(4),
            'main' => false,
        ];
    }

    /**
     * Mark this address as the client main address.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'main' => true,
        ]);
    }

    /**
     * Attach the address to the given client.
     */
    public function client(Client $client): static
    {
        return $this->state(fn (array $attributes) => ['client_id' => $client->id]);
    }
}
<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (User $user) => $this->assignRole($user, UserRole::Tecnico));
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'company_id' => Company::factory(),
            'is_superadmin' => false,
            'active' => true,
        ];
    }

    /**
     * Assign the given role to the created user.
     */
    protected function assignRole(User $user, UserRole $role): void
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $role->value],
            ['name' => $role->label()],
        );

        $user->roles()->sync([$role->id]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Make the user an administrator (admin profile).
     */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $this->assignRole($user, UserRole::Admin));
    }

    /**
     * Make the user a comercial profile.
     */
    public function comercial(): static
    {
        return $this->afterCreating(fn (User $user) => $this->assignRole($user, UserRole::Comercial));
    }

    /**
     * Make the user a tecnico profile.
     */
    public function tecnico(): static
    {
        return $this->afterCreating(fn (User $user) => $this->assignRole($user, UserRole::Tecnico));
    }

    /**
     * Make the user a platform superadmin, not tied to a company.
     */
    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => null,
            'is_superadmin' => true,
        ]);
    }

    /**
     * Mark the user as inactive (cannot authenticate).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Attach the user to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }
}

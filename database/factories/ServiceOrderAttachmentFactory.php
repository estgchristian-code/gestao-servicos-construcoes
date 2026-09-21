<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrderAttachment>
 */
class ServiceOrderAttachmentFactory extends Factory
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
            'name' => fake()->word() . '.jpg',
            'path' => 'attachments/' . fake()->uuid() . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1024, 5_242_880),
        ];
    }

    /**
     * Attach the attachment to the given company.
     */
    public function company(Company $company): static
    {
        return $this->state(fn (array $attributes) => ['company_id' => $company->id]);
    }

    /**
     * Attach the attachment to the given service order.
     */
    public function serviceOrder(ServiceOrder $order): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $order->company_id,
            'service_order_id' => $order->id,
        ]);
    }

    /**
     * Attach the attachment to the given uploader.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $user->id]);
    }

    /**
     * Mark this attachment as a PDF file.
     */
    public function asPdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->word() . '.pdf',
            'path' => 'attachments/' . fake()->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }
}
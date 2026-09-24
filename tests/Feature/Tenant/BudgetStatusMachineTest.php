<?php

namespace Tests\Feature\Tenant;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetStatusMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_budget_model_defaults_to_draft(): void
    {
        $this->assertSame(BudgetStatus::Draft, (new Budget)->status);
    }

    public function test_new_budget_always_born_as_draft(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Orçamento recém-criado')
            ->set('total', '1200,00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $budget = Budget::query()->first();

        $this->assertNotNull($budget);
        $this->assertSame(BudgetStatus::Draft, $budget->status);
    }

    public function test_creating_already_as_approved_is_forced_to_draft(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Orçamento forjado como aprovado')
            ->set('status', BudgetStatus::Approved->value)
            ->set('total', '100,00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $budget = Budget::query()->first();

        $this->assertNotNull($budget);
        $this->assertSame(BudgetStatus::Draft, $budget->status);
    }

    public function test_creating_as_refused_cancelled_or_expired_is_forced_to_draft(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        foreach ([BudgetStatus::Refused, BudgetStatus::Cancelled, BudgetStatus::Expired] as $forged) {
            Livewire::actingAs($user)
                ->test('pages::budgets.create')
                ->set('client_id', (string) $client->id)
                ->set('title', 'Orçamento '.$forged->value)
                ->set('status', $forged->value)
                ->set('total', '100,00')
                ->call('save')
                ->assertHasNoErrors()
                ->assertRedirect();
        }

        $budgets = Budget::query()->get();

        $this->assertCount(3, $budgets);
        $this->assertTrue($budgets->every(fn (Budget $budget) => $budget->status === BudgetStatus::Draft));
    }

    public function test_creating_with_status_outside_enum_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Status inválido')
            ->set('status', 'aprovado')
            ->set('total', '100,00')
            ->call('save')
            ->assertHasErrors('status');

        $this->assertSame(0, Budget::query()->count());
    }

    public function test_draft_to_sent_transition_is_allowed(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Draft,
            'total' => 100,
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('title', 'Enviado ao cliente')
            ->set('status', BudgetStatus::Sent->value)
            ->set('total', '100,00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(BudgetStatus::Sent, $budget->fresh()->status);
    }

    public function test_sent_to_approved_transition_is_allowed(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Sent,
            'total' => 100,
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('status', BudgetStatus::Approved->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(BudgetStatus::Approved, $budget->fresh()->status);
    }

    public function test_invalid_transitions_from_draft_are_blocked(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        foreach ([BudgetStatus::Approved, BudgetStatus::Refused, BudgetStatus::Expired, BudgetStatus::Cancelled] as $target) {
            $client = Client::factory()->company($company)->create();
            $budget = Budget::factory()->company($company)->client($client)->create([
                'status' => BudgetStatus::Draft,
                'total' => 100,
            ]);

            Livewire::actingAs($user)
                ->test('pages::budgets.edit', ['budget' => $budget])
                ->set('status', $target->value)
                ->call('save')
                ->assertForbidden();

            $this->assertSame(BudgetStatus::Draft, $budget->fresh()->status);
        }
    }

    public function test_refused_budget_cannot_be_reopened(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Refused,
            'total' => 100,
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('status', BudgetStatus::Draft->value)
            ->call('save')
            ->assertForbidden();

        $this->assertSame(BudgetStatus::Refused, $budget->fresh()->status);
    }

    public function test_approved_cannot_be_changed_to_any_other_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        foreach ([BudgetStatus::Draft, BudgetStatus::Sent, BudgetStatus::Refused, BudgetStatus::Expired, BudgetStatus::Cancelled] as $target) {
            $client = Client::factory()->company($company)->create();
            $budget = Budget::factory()->company($company)->client($client)->create([
                'status' => BudgetStatus::Approved,
                'total' => 100,
            ]);

            Livewire::actingAs($user)
                ->test('pages::budgets.edit', ['budget' => $budget])
                ->set('status', $target->value)
                ->call('save')
                ->assertForbidden();

            $this->assertSame(BudgetStatus::Approved, $budget->fresh()->status);
        }
    }

    public function test_approved_budget_can_be_edited_keeping_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'total' => 100,
            'valid_until' => now()->addDays(10)->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('title', 'Ajuste após aprovação')
            ->set('status', BudgetStatus::Approved->value)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $fresh = $budget->fresh();

        $this->assertSame('Ajuste após aprovação', $fresh->title);
        $this->assertSame(BudgetStatus::Approved, $fresh->status);
    }

    public function test_stale_edit_cannot_overwrite_budget_converted_in_the_meantime(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'title' => 'Orçamento original',
            'total' => 100,
        ]);

        $staleEdit = Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget]);

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);

        $staleEdit
            ->set('title', 'Sobrescrita após conversão')
            ->set('status', BudgetStatus::Refused->value)
            ->call('save')
            ->assertForbidden();

        $fresh = $budget->fresh();

        $this->assertSame(BudgetStatus::Approved, $fresh->status);
        $this->assertSame($order->id, $fresh->service_order_id);
        $this->assertSame('Orçamento original', $fresh->title);
    }

    public function test_converted_budget_keeps_service_order_link_and_blocks_status_change(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'total' => 100,
            'valid_until' => now()->addDays(5)->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $order = ServiceOrder::query()->first();

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget->fresh()])
            ->assertForbidden();

        $fresh = $budget->fresh();

        $this->assertSame($order->id, $fresh->service_order_id);
        $this->assertSame(BudgetStatus::Approved, $fresh->status);
    }

    public function test_expired_approved_budget_cannot_be_converted(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'total' => 100,
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertForbidden();

        $this->assertDatabaseCount('service_orders', 0);
        $this->assertNull($budget->fresh()->service_order_id);
    }

    public function test_approved_budget_within_validity_is_still_convertible(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'total' => 100,
            'valid_until' => now()->addDays(10)->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $this->assertDatabaseCount('service_orders', 1);
        $this->assertNotNull($budget->fresh()->service_order_id);
    }

    public function test_expired_approved_budget_cannot_be_linked_through_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'total' => 100,
            'valid_until' => now()->subDay()->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('budget_id', (string) $budget->id)
            ->set('title', 'OS com orçamento vencido')
            ->call('save')
            ->assertHasErrors('budget_id');

        $this->assertDatabaseCount('service_orders', 0);
        $this->assertNull($budget->fresh()->service_order_id);
    }

    public function test_edit_forging_status_outside_enum_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Draft,
            'total' => 100,
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('status', 'aprovado')
            ->call('save')
            ->assertHasErrors('status');

        $this->assertSame(BudgetStatus::Draft, $budget->fresh()->status);
    }

    public function test_cross_tenant_budget_remains_isolated(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->admin()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create();
        $budgetB = Budget::factory()->company($companyB)->client($clientB)->create([
            'status' => BudgetStatus::Draft,
            'total' => 100,
        ]);

        Livewire::actingAs($userA)
            ->test('pages::budgets.edit', ['budget' => $budgetB])
            ->assertForbidden();

        $this->assertSame(BudgetStatus::Draft, $budgetB->fresh()->status);
    }
}

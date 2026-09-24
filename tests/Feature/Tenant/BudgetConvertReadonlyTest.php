<?php

namespace Tests\Feature\Tenant;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Client;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetConvertReadonlyTest extends TestCase
{
    use RefreshDatabase;

    private function conversionContext(Company $company, Client $client): array
    {
        $service = Service::factory()->company($company)->create(['name' => 'Serviço original']);

        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'title' => 'Orçamento convertido',
            'notes' => 'Notas originais',
            'total' => 100,
            'valid_until' => now()->addDays(5)->toDateString(),
        ]);

        BudgetItem::factory()->company($company)->budget($budget)->service($service)->create([
            'description' => 'Item original',
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);

        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'budget_id' => $budget->id,
            'title' => 'OS gerada do orçamento',
            'total' => 100,
        ]);

        Budget::query()->whereKey($budget->id)->update(['service_order_id' => $order->id]);

        return [$budget->fresh(), $order->fresh(), $service];
    }

    public function test_converted_budget_cannot_be_edited(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget, $order] = $this->conversionContext($company, $client);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->assertForbidden();

        $this->assertSame($client->id, $budget->fresh()->client_id);
        $this->assertSame('Orçamento convertido', $budget->fresh()->title);
        $this->assertSame('Notas originais', $budget->fresh()->notes);
        $this->assertSame('100.00', $budget->fresh()->total);
        $this->assertSame(now()->addDays(5)->toDateString(), $budget->fresh()->valid_until?->format('Y-m-d'));
        $this->assertSame(BudgetStatus::Approved, $budget->fresh()->status);
        $this->assertSame($order->id, $budget->fresh()->service_order_id);
    }

    public function test_converted_budget_cannot_have_status_changed(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->assertForbidden();

        $this->assertSame(BudgetStatus::Approved, $budget->fresh()->status);
    }

    public function test_converted_budget_cannot_change_client(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->assertForbidden();

        $this->assertSame($client->id, $budget->fresh()->client_id);
    }

    public function test_converted_budget_cannot_change_title_notes_or_validity(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->assertForbidden();

        $this->assertSame('Orçamento convertido', $budget->fresh()->title);
        $this->assertSame('Notas originais', $budget->fresh()->notes);
        $this->assertSame(now()->addDays(5)->toDateString(), $budget->fresh()->valid_until?->format('Y-m-d'));
    }

    public function test_converted_budget_cannot_add_item(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('add')
            ->assertForbidden();

        $this->assertSame(1, $budget->items()->count());
        $this->assertSame('100.00', $budget->fresh()->total);
    }

    public function test_converted_budget_cannot_edit_item(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);
        $item = $budget->items()->first();

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('edit', $item->id)
            ->assertForbidden();

        $this->assertSame('Item original', $item->fresh()->description);
        $this->assertSame('100.00', $item->fresh()->subtotal);
        $this->assertSame('100.00', $budget->fresh()->total);
    }

    public function test_converted_budget_cannot_delete_item(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);
        $item = $budget->items()->first();

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('delete', $item->id)
            ->assertForbidden();

        $this->assertNotNull($item->fresh());
        $this->assertSame(1, $budget->items()->count());
        $this->assertSame('100.00', $budget->fresh()->total);
    }

    public function test_converted_budget_cannot_be_deleted_and_links_stay_intact(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget, $order] = $this->conversionContext($company, $client);

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
        $this->assertSame($order->id, $budget->fresh()->service_order_id);
        $this->assertSame($budget->id, $order->fresh()->budget_id);
    }

    public function test_service_order_remains_untouched_after_blocked_attempts(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget, $order] = $this->conversionContext($company, $client);
        $item = $budget->items()->first();

        $snapshot = [
            'budget_id' => $order->budget_id,
            'client_id' => $order->client_id,
            'title' => $order->title,
            'notes' => $order->notes,
            'total' => $order->total,
            'scheduled_at' => $order->scheduled_at,
        ];

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('edit', $item->id)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('delete')
            ->assertForbidden();

        $fresh = $order->fresh();

        $this->assertSame($snapshot['budget_id'], $fresh->budget_id);
        $this->assertSame($snapshot['client_id'], $fresh->client_id);
        $this->assertSame($snapshot['title'], $fresh->title);
        $this->assertSame($snapshot['notes'], $fresh->notes);
        $this->assertSame($snapshot['total'], $fresh->total);
        $this->assertSame(
            $snapshot['scheduled_at']?->format('Y-m-d H:i:s'),
            $fresh->scheduled_at?->format('Y-m-d H:i:s'),
        );
    }

    public function test_converted_budget_remains_viewable(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget, $order] = $this->conversionContext($company, $client);

        $this->actingAs($user)
            ->get(route('budgets.show', $budget))
            ->assertOk()
            ->assertSee('Orçamento convertido')
            ->assertSee('somente leitura')
            ->assertSee('OS #' . $order->number)
            ->assertDontSee('>Editar<')
            ->assertDontSee('>Excluir<');
    }

    public function test_converted_budget_index_hides_edit_and_shows_readonly_badge(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertSee('somente leitura')
            ->assertDontSee('>Editar<');
    }

    public function test_non_converted_budget_remains_editable(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'title' => 'Orçamento antigo',
            'status' => BudgetStatus::Draft,
            'total' => 100,
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('title', 'Orçamento atualizado')
            ->set('status', BudgetStatus::Sent->value)
            ->set('total', '2500,75')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame('Orçamento atualizado', $budget->fresh()->title);
        $this->assertSame(BudgetStatus::Sent, $budget->fresh()->status);
        $this->assertSame('2500.75', $budget->fresh()->total);
    }

    public function test_non_converted_budget_items_remain_manageable(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create(['total' => 0]);
        $service = Service::factory()->company($company)->create(['name' => 'Instalação']);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('add')
            ->set('item_service_id', (string) $service->id)
            ->set('item_description', 'Instalação de ar-condicionado')
            ->set('item_quantity', '2')
            ->set('item_unit_price', '150,50')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $budget->items()->count());
        $this->assertSame('301.00', $budget->fresh()->total);
    }

    public function test_non_converted_budget_remains_deletable(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('delete')
            ->assertRedirect(route('budgets.index'));

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_converted_budget_from_another_company_remains_inaccessible(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->admin()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create();
        [$budgetB] = $this->conversionContext($companyB, $clientB);

        $this->actingAs($userA)
            ->get(route('budgets.edit', $budgetB))
            ->assertNotFound();

        $this->actingAs($userA)
            ->get(route('budgets.show', $budgetB))
            ->assertNotFound();

        Livewire::actingAs($userA)
            ->test('pages::budgets.edit', ['budget' => $budgetB])
            ->assertForbidden();

        Livewire::actingAs($userA)
            ->test('pages::budgets.show', ['budget' => $budgetB])
            ->assertForbidden();
    }

    public function test_direct_backend_policy_denies_converted_budget_operations(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget] = $this->conversionContext($company, $client);
        $item = $budget->items()->first();

        $this->assertFalse($user->can('update', $budget));
        $this->assertFalse($user->can('delete', $budget));
        $this->assertFalse($user->can('update', $item));
        $this->assertFalse($user->can('delete', $item));

        $this->assertTrue($user->can('view', $budget));
        $this->assertTrue($user->can('view', $item));
    }

    public function test_tecnico_may_view_converted_budget_but_cannot_manage(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        [$budget, $order] = $this->conversionContext($company, $client);

        $this->actingAs($user)
            ->get(route('budgets.show', $budget))
            ->assertOk()
            ->assertSee('somente leitura')
            ->assertDontSee('>Editar<')
            ->assertDontSee('>Excluir<');

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->assertForbidden();
    }
}
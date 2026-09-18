<?php

namespace Tests\Feature\Tenant;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Client;
use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetItemTest extends TestCase
{
    use RefreshDatabase;

    private function budgetFor(Company $company): Budget
    {
        $client = Client::factory()->company($company)->create();

        return Budget::factory()->company($company)->client($client)->create(['total' => 0]);
    }

    public function test_admin_can_add_item_and_recalculates_total(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $budget = $this->budgetFor($company);
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

        $this->assertDatabaseHas('budget_items', [
            'budget_id' => $budget->id,
            'company_id' => $company->id,
            'service_id' => $service->id,
            'description' => 'Instalação de ar-condicionado',
            'quantity' => '2.00',
            'unit_price' => '150.50',
            'subtotal' => '301.00',
        ]);

        $this->assertSame('301.00', $budget->fresh()->total);
    }

    public function test_multiple_items_sum_into_budget_total(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $budget = $this->budgetFor($company);
        $serviceA = Service::factory()->company($company)->create();
        $serviceB = Service::factory()->company($company)->create();

        $saveItem = function (string $serviceId, string $qty, string $price) use ($user, $budget): void {
            Livewire::actingAs($user)
                ->test('manage-budget-items', ['budget' => $budget])
                ->call('add')
                ->set('item_service_id', $serviceId)
                ->set('item_description', 'Item')
                ->set('item_quantity', $qty)
                ->set('item_unit_price', $price)
                ->call('save')
                ->assertHasNoErrors();
        };

        $saveItem((string) $serviceA->id, '1', '100,00');
        $saveItem((string) $serviceB->id, '2', '50,25');

        $this->assertSame(2, $budget->items()->count());
        $this->assertSame('200.50', $budget->fresh()->total);
    }

    public function test_admin_can_edit_item_and_recalculates_total(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $budget = $this->budgetFor($company);
        $service = Service::factory()->company($company)->create();
        $item = BudgetItem::factory()->company($company)->budget($budget)->service($service)->create([
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);
        $budget->update(['total' => 100]);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('edit', $item->id)
            ->set('item_quantity', '2,5')
            ->set('item_unit_price', '200,00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('500.00', $item->fresh()->subtotal);
        $this->assertSame('500.00', $budget->fresh()->total);
    }

    public function test_admin_can_delete_item_and_recalculates_total(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $budget = $this->budgetFor($company);
        $service = Service::factory()->company($company)->create();
        $keep = BudgetItem::factory()->company($company)->budget($budget)->service($service)->create([
            'quantity' => 1,
            'unit_price' => 300,
            'subtotal' => 300,
        ]);
        $remove = BudgetItem::factory()->company($company)->budget($budget)->service($service)->create([
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);
        $budget->update(['total' => 400]);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('delete', $remove->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('budget_items', ['id' => $remove->id]);
        $this->assertSame('300.00', $budget->fresh()->total);
        $this->assertSame($keep->id, BudgetItem::query()->first()->id);
    }

    public function test_service_from_another_company_is_rejected(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $budget = $this->budgetFor($companyA);
        $serviceB = Service::factory()->company($companyB)->create();

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('add')
            ->set('item_service_id', (string) $serviceB->id)
            ->set('item_description', 'Item')
            ->set('item_quantity', '1')
            ->set('item_unit_price', '100,00')
            ->call('save')
            ->assertHasErrors('item_service_id');

        $this->assertSame(0, $budget->items()->count());
        $this->assertSame('0.00', $budget->fresh()->total);
    }

    public function test_service_is_required_when_adding_item(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $budget = $this->budgetFor($company);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('add')
            ->set('item_service_id', '')
            ->set('item_description', 'Item')
            ->set('item_quantity', '1')
            ->set('item_unit_price', '100,00')
            ->call('save')
            ->assertHasErrors('item_service_id');

        $this->assertSame(0, $budget->items()->count());
    }

    public function test_quantity_and_unit_price_are_validated(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $budget = $this->budgetFor($company);
        $service = Service::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('add')
            ->set('item_service_id', (string) $service->id)
            ->set('item_description', 'Item')
            ->set('item_quantity', '0')
            ->set('item_unit_price', 'abc')
            ->call('save')
            ->assertHasErrors(['item_quantity', 'item_unit_price']);

        $this->assertSame(0, $budget->items()->count());
    }

    public function test_item_uses_service_name_as_description_when_blank(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $budget = $this->budgetFor($company);
        $service = Service::factory()->company($company)->create(['name' => 'Manutenção Preventiva']);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('add')
            ->set('item_service_id', (string) $service->id)
            ->set('item_description', '')
            ->set('item_quantity', '1')
            ->set('item_unit_price', '100,00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('budget_items', [
            'budget_id' => $budget->id,
            'description' => 'Manutenção Preventiva',
        ]);
    }

    public function test_tecnico_can_view_items_but_cannot_add(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $budget = $this->budgetFor($company);
        $service = Service::factory()->company($company)->create();
        BudgetItem::factory()->company($company)->budget($budget)->service($service)->create([
            'description' => 'Item Visível',
            'quantity' => 2,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->assertOk()
            ->assertSee('Item Visível')
            ->call('add')
            ->assertForbidden();
    }

    public function test_tecnico_cannot_edit_or_delete_item(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $budget = $this->budgetFor($company);
        $service = Service::factory()->company($company)->create();
        $item = BudgetItem::factory()->company($company)->budget($budget)->service($service)->create();

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budget])
            ->call('edit', $item->id)
            ->assertForbidden();

        $this->assertDatabaseHas('budget_items', ['id' => $item->id]);
    }

    public function test_tecnico_show_page_renders_items_without_management_actions(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $budget = $this->budgetFor($company);
        $service = Service::factory()->company($company)->create(['name' => 'Serviço X']);
        BudgetItem::factory()->company($company)->budget($budget)->service($service)->create([
            'description' => 'Item Visível',
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);
        $budget->update(['total' => 100]);

        $this->actingAs($user)
            ->get(route('budgets.show', $budget))
            ->assertOk()
            ->assertSee('Item Visível')
            ->assertSee('R$ 100,00')
            ->assertDontSee('Adicionar item')
            ->assertDontSee('Excluir');
    }

    public function test_cross_tenant_budget_cannot_be_managed(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $budgetB = $this->budgetFor($companyB);

        Livewire::actingAs($user)
            ->test('manage-budget-items', ['budget' => $budgetB])
            ->assertForbidden();
    }

    public function test_item_policy_restricts_management_and_tenants(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->company($companyA)->create();
        $comercialA = User::factory()->comercial()->company($companyA)->create();
        $tecnicoA = User::factory()->tecnico()->company($companyA)->create();
        $budgetA = $this->budgetFor($companyA);
        $budgetB = $this->budgetFor($companyB);
        $serviceA = Service::factory()->company($companyA)->create();
        $serviceB = Service::factory()->company($companyB)->create();

        $itemA = BudgetItem::factory()->company($companyA)->budget($budgetA)->service($serviceA)->create();
        $itemB = BudgetItem::factory()->company($companyB)->budget($budgetB)->service($serviceB)->create();

        $this->assertTrue($adminA->can('view', $itemA));
        $this->assertTrue($adminA->can('update', $itemA));
        $this->assertTrue($comercialA->can('update', $itemA));
        $this->assertTrue($tecnicoA->can('view', $itemA));
        $this->assertFalse($tecnicoA->can('update', $itemA));
        $this->assertFalse($tecnicoA->can('delete', $itemA));
        $this->assertFalse($adminA->can('view', $itemB));
        $this->assertFalse($adminA->can('update', $itemB));
    }
}
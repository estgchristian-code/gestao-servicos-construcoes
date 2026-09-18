<?php

namespace Tests\Feature\Tenant;

use App\Enums\BudgetStatus;
use App\Enums\ServiceOrderStatus;
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

class BudgetConversionTest extends TestCase
{
    use RefreshDatabase;

    private function approvedBudgetWithItems(Company $company): Budget
    {
        $client = Client::factory()->company($company)->create();
        $service = Service::factory()->company($company)->create();

        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
            'title' => 'Orçamento aprovado para conversão',
            'notes' => 'Observações do orçamento original.',
            'total' => 3250,
        ]);

        BudgetItem::factory()->company($company)->budget($budget)->create([
            'service_id' => null,
            'description' => 'Mão de obra básica',
            'quantity' => 2,
            'unit_price' => 500,
            'subtotal' => 1000,
        ]);

        BudgetItem::factory()->company($company)->budget($budget)->create([
            'service_id' => $service->id,
            'description' => 'Instalação de ar-condicionado',
            'quantity' => 1.5,
            'unit_price' => 1500,
            'subtotal' => 2250,
        ]);

        return $budget;
    }

    public function test_only_approved_budget_can_be_converted(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();

        foreach ([BudgetStatus::Draft, BudgetStatus::Sent, BudgetStatus::Refused, BudgetStatus::Expired, BudgetStatus::Cancelled] as $status) {
            $client = Client::factory()->company($company)->create();
            $budget = Budget::factory()->company($company)->client($client)->create(['status' => $status]);

            Livewire::actingAs($admin)
                ->test('pages::budgets.show', ['budget' => $budget])
                ->call('convert')
                ->assertForbidden();
        }

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_admin_can_convert_approved_budget(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        Livewire::actingAs($admin)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk()
            ->assertSee('Orçamento convertido em OS #000001 com sucesso.')
            ->assertSee('Ordem de serviço gerada');

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertSame($budget->id, $order->budget_id);
        $this->assertSame(ServiceOrderStatus::Pending, $order->status);
        $this->assertSame('000001', $order->number);
        $this->assertSame($budget->id, $budget->fresh()->service_order_id);
    }

    public function test_comercial_can_convert_approved_budget(): void
    {
        $company = Company::factory()->create();
        $comercial = User::factory()->comercial()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        Livewire::actingAs($comercial)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $this->assertDatabaseCount('service_orders', 1);
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'service_order_id' => $budget->fresh()->service_order_id,
        ]);
    }

    public function test_technician_cannot_convert_budget(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        Livewire::actingAs($technician)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertForbidden();

        $this->assertDatabaseCount('service_orders', 0);
        $this->assertDatabaseCount('service_order_items', 0);
        $this->assertNull($budget->fresh()->service_order_id);
    }

    public function test_cannot_convert_budget_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->company($companyA)->create();
        $budgetB = $this->approvedBudgetWithItems($companyB);

        $this->actingAs($adminA)
            ->get(route('budgets.show', $budgetB))
            ->assertNotFound();

        $this->assertDatabaseCount('service_orders', 0);
        $this->assertNull($budgetB->fresh()->service_order_id);
    }

    public function test_convert_copies_budget_data_and_items_to_the_order(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        Livewire::actingAs($admin)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $order = ServiceOrder::query()->first();

        $this->assertSame($company->id, $order->company_id);
        $this->assertSame($budget->client_id, $order->client_id);
        $this->assertSame($budget->id, $order->budget_id);
        $this->assertSame($budget->title, $order->title);
        $this->assertSame($budget->notes, $order->notes);
        $this->assertSame(ServiceOrderStatus::Pending, $order->status);
        $this->assertNull($order->technician_id);
        $this->assertNull($order->scheduled_at);

        $items = $order->items()->orderBy('id')->get();

        $this->assertCount(2, $items);

        $this->assertSame('Mão de obra básica', $items[0]->description);
        $this->assertSame('2.00', $items[0]->quantity);
        $this->assertSame('500.00', $items[0]->unit_price);
        $this->assertSame('1000.00', $items[0]->subtotal);
        $this->assertNull($items[0]->service_id);
        $this->assertSame($company->id, $items[0]->company_id);

        $this->assertSame('Instalação de ar-condicionado', $items[1]->description);
        $this->assertSame('1.50', $items[1]->quantity);
        $this->assertSame('1500.00', $items[1]->unit_price);
        $this->assertSame('2250.00', $items[1]->subtotal);
        $this->assertSame($budget->items()->orderBy('id')->get()[1]->service_id, $items[1]->service_id);
        $this->assertSame($company->id, $items[1]->company_id);
    }

    public function test_order_total_matches_the_sum_of_copied_items(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        Livewire::actingAs($admin)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $order = ServiceOrder::query()->first();

        $this->assertSame('3250.00', $order->total);
        $this->assertSame('3250.00', $budget->fresh()->total);
    }

    public function test_cannot_convert_the_same_budget_twice(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        Livewire::actingAs($admin)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        Livewire::actingAs($admin)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertForbidden();

        $this->assertDatabaseCount('service_orders', 1);
        $this->assertDatabaseCount('service_order_items', 2);
        $this->assertNotNull($budget->fresh()->service_order_id);
    }

    public function test_original_budget_remains_unchanged(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        $originalItems = $budget->items()->orderBy('id')->get()->map(fn (BudgetItem $item) => [
            'service_id' => $item->service_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'subtotal' => $item->subtotal,
        ])->all();

        Livewire::actingAs($admin)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $fresh = $budget->fresh();

        $this->assertSame($budget->number, $fresh->number);
        $this->assertSame($budget->title, $fresh->title);
        $this->assertSame($budget->notes, $fresh->notes);
        $this->assertSame($budget->client_id, $fresh->client_id);
        $this->assertSame($budget->company_id, $fresh->company_id);
        $this->assertSame('3250.00', $fresh->total);
        $this->assertSame(BudgetStatus::Approved, $fresh->status);
        $this->assertSame(3250.0, round((float) $fresh->items()->sum('subtotal'), 2));

        $this->assertSame(
            $originalItems,
            $fresh->items()->orderBy('id')->get()->map(fn (BudgetItem $item) => [
                'service_id' => $item->service_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->all()
        );
    }

    public function test_conversion_is_atomic(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $budget = $this->approvedBudgetWithItems($company);

        ServiceOrder::creating(function () {
            throw new \RuntimeException('Falha forçada durante a criação da OS.');
        });

        try {
            Livewire::actingAs($admin)
                ->test('pages::budgets.show', ['budget' => $budget])
                ->call('convert');
        } catch (\Throwable) {
            // Exceção esperada propagada pelo Livewire.
        } finally {
            ServiceOrder::flushEventListeners();
        }

        $this->assertDatabaseCount('service_orders', 0);
        $this->assertDatabaseCount('service_order_items', 0);
        $this->assertNull($budget->fresh()->service_order_id);
        $this->assertSame('approved', $budget->fresh()->status->value);
        $this->assertSame('3250.00', $budget->fresh()->total);
    }
}
<?php

namespace Tests\Feature\Tenant;

use App\Models\Budget;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('budgets.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_budgets_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk();
    }

    public function test_comercial_can_access_budgets_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk();
    }

    public function test_tecnico_can_access_budgets_index_but_only_view(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        Budget::factory()->company($company)->client($client)->create(['title' => 'Orçamento Único']);

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertSee('Orçamento Único')
            ->assertDontSee('Novo orçamento')
            ->assertDontSee('Editar');
    }

    public function test_index_only_lists_budgets_from_same_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientA = Client::factory()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create();

        Budget::factory()->company($companyA)->client($clientA)->create(['title' => 'Orçamento Alpha']);
        Budget::factory()->company($companyB)->client($clientB)->create(['title' => 'Orçamento Bravo']);

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertOk()
            ->assertSee('Orçamento Alpha')
            ->assertDontSee('Orçamento Bravo');
    }

    public function test_cross_tenant_budget_returns_404_on_show(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();
        $budgetB = Budget::factory()->company($companyB)->client($clientB)->create();

        $this->actingAs($user)
            ->get(route('budgets.show', $budgetB))
            ->assertNotFound();
    }

    public function test_cross_tenant_budget_returns_404_on_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();
        $budgetB = Budget::factory()->company($companyB)->client($clientB)->create();

        $this->actingAs($user)
            ->get(route('budgets.edit', $budgetB))
            ->assertNotFound();
    }

    public function test_admin_can_create_budget_for_own_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Instalação de ar-condicionado')
            ->set('status', 'draft')
            ->set('total', '1500,50')
            ->set('valid_until', now()->addDays(10)->format('Y-m-d'))
            ->set('notes', 'Pagamento em 2x.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $budget = Budget::query()->first();

        $this->assertNotNull($budget);
        $this->assertSame($company->id, $budget->company_id);
        $this->assertSame($client->id, $budget->client_id);
        $this->assertSame('000001', $budget->number);
        $this->assertSame('draft', $budget->status->value);
        $this->assertSame('1500.50', $budget->total);
        $this->assertSame('Instalação de ar-condicionado', $budget->title);
        $this->assertSame('Pagamento em 2x.', $budget->notes);
        $this->assertNotNull($budget->valid_until);
    }

    public function test_comercial_can_create_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Manutenção corretiva')
            ->set('total', '890,90')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $budget = Budget::query()->first();

        $this->assertNotNull($budget);
        $this->assertSame($company->id, $budget->company_id);
    }

    public function test_sequential_number_is_generated_per_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Primeiro orçamento')
            ->set('total', '100,00')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Segundo orçamento')
            ->set('total', '200,00')
            ->call('save')
            ->assertHasNoErrors();

        $budgets = Budget::query()->orderBy('id')->get();

        $this->assertCount(2, $budgets);
        $this->assertSame('000001', $budgets->get(0)->number);
        $this->assertSame('000002', $budgets->get(1)->number);
    }

    public function test_tecnico_cannot_create_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Orçamento Proibido')
            ->set('total', '1000,00')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Budget::query()->count());
    }

    public function test_client_is_required_when_creating_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', '')
            ->set('title', 'Sem cliente')
            ->set('total', '100,00')
            ->call('save')
            ->assertHasErrors('client_id');

        $this->assertSame(0, Budget::query()->count());
    }

    public function test_cannot_link_client_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create(['name' => 'Cliente Bravo']);

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $clientB->id)
            ->set('title', 'Cliente de outra empresa')
            ->set('total', '100,00')
            ->call('save')
            ->assertHasErrors('client_id');

        $this->assertSame(0, Budget::query()->count());
    }

    public function test_title_is_required_when_creating_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', '')
            ->set('total', '100,00')
            ->call('save')
            ->assertHasErrors('title');

        $this->assertSame(0, Budget::query()->count());
    }

    public function test_total_is_required_when_creating_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Sem valor')
            ->set('total', '')
            ->call('save')
            ->assertHasErrors('total');

        $this->assertSame(0, Budget::query()->count());
    }

    public function test_total_must_be_valid_decimal(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Valor inválido')
            ->set('total', 'abc')
            ->call('save')
            ->assertHasErrors('total');

        $this->assertSame(0, Budget::query()->count());
    }

    public function test_admin_can_update_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'title' => 'Orçamento Antigo',
            'status' => 'draft',
            'total' => 100,
        ]);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('client_id', (string) $client->id)
            ->set('title', 'Orçamento Atualizado')
            ->set('status', 'sent')
            ->set('total', '2500,75')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'company_id' => $company->id,
            'client_id' => $client->id,
            'title' => 'Orçamento Atualizado',
            'status' => 'sent',
            'total' => '2500.75',
        ]);
    }

    public function test_comercial_can_update_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create(['title' => 'Orçamento Antigo']);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('client_id', (string) $client->id)
            ->set('title', 'Orçamento Comercial')
            ->set('total', '500,00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Orçamento Comercial', $budget->fresh()->title);
    }

    public function test_update_keeps_budget_in_same_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->set('client_id', (string) $client->id)
            ->set('title', 'Nome Atualizado')
            ->set('total', '300,00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($company->id, $budget->fresh()->company_id);
    }

    public function test_tecnico_cannot_update_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create(['title' => 'Orçamento Antigo']);

        Livewire::actingAs($user)
            ->test('pages::budgets.edit', ['budget' => $budget])
            ->assertForbidden();

        $this->assertSame('Orçamento Antigo', $budget->fresh()->title);
    }

    public function test_show_page_renders_budget_info(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create(['name' => 'Cliente Vista']);
        $budget = Budget::factory()->company($company)->client($client)->create(['title' => 'Orçamento Visível']);

        $this->actingAs($user)
            ->get(route('budgets.show', $budget))
            ->assertOk()
            ->assertSee('Orçamento Visível')
            ->assertSee('Cliente Vista');
    }

    public function test_tecnico_can_view_show_page_but_not_manage(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create(['title' => 'Orçamento Visível']);

        $this->actingAs($user)
            ->get(route('budgets.show', $budget))
            ->assertOk()
            ->assertSee('Orçamento Visível')
            ->assertDontSee('Editar')
            ->assertDontSee('Excluir');
    }

    public function test_admin_can_delete_budget(): void
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

    public function test_comercial_can_delete_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('delete')
            ->assertRedirect(route('budgets.index'));

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_tecnico_cannot_delete_budget(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('budgets', ['id' => $budget->id]);
    }
}
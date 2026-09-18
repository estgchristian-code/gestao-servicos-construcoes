<?php

namespace Tests\Feature\Tenant;

use App\Enums\ServiceOrderStatus;
use App\Models\Budget;
use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('service-orders.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_service_orders_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $this->actingAs($user)
            ->get(route('service-orders.index'))
            ->assertOk();
    }

    public function test_comercial_can_access_service_orders_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();

        $this->actingAs($user)
            ->get(route('service-orders.index'))
            ->assertOk();
    }

    public function test_tecnico_can_access_service_orders_index_but_only_view(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        ServiceOrder::factory()->company($company)->client($client)->create(['title' => 'OS Única']);

        $this->actingAs($user)
            ->get(route('service-orders.index'))
            ->assertOk()
            ->assertSee('OS Única')
            ->assertDontSee('Nova ordem de serviço')
            ->assertDontSee('Editar');
    }

    public function test_index_only_lists_orders_from_same_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientA = Client::factory()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create();

        ServiceOrder::factory()->company($companyA)->client($clientA)->create(['title' => 'OS Alpha']);
        ServiceOrder::factory()->company($companyB)->client($clientB)->create(['title' => 'OS Bravo']);

        $this->actingAs($user)
            ->get(route('service-orders.index'))
            ->assertOk()
            ->assertSee('OS Alpha')
            ->assertDontSee('OS Bravo');
    }

    public function test_cross_tenant_order_returns_404_on_show(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();
        $orderB = ServiceOrder::factory()->company($companyB)->client($clientB)->create();

        $this->actingAs($user)
            ->get(route('service-orders.show', $orderB))
            ->assertNotFound();
    }

    public function test_cross_tenant_order_returns_404_on_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();
        $orderB = ServiceOrder::factory()->company($companyB)->client($clientB)->create();

        $this->actingAs($user)
            ->get(route('service-orders.edit', $orderB))
            ->assertNotFound();
    }

    public function test_admin_can_create_service_order_for_own_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Instalação de ar-condicionado')
            ->set('status', 'pending')
            ->set('scheduled_at', now()->addDays(2)->format('Y-m-d'))
            ->set('notes', 'Instalar no 2º andar.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertSame($company->id, $order->company_id);
        $this->assertSame($client->id, $order->client_id);
        $this->assertSame('000001', $order->number);
        $this->assertSame('pending', $order->status->value);
        $this->assertSame('Instalação de ar-condicionado', $order->title);
        $this->assertSame('Instalar no 2º andar.', $order->notes);
        $this->assertNotNull($order->scheduled_at);
        $this->assertNull($order->budget_id);
    }

    public function test_comercial_can_create_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Manutenção corretiva')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertSame($company->id, $order->company_id);
    }

    public function test_sequential_number_is_generated_per_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Primeira OS')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Segunda OS')
            ->call('save')
            ->assertHasNoErrors();

        $orders = ServiceOrder::query()->orderBy('id')->get();

        $this->assertCount(2, $orders);
        $this->assertSame('000001', $orders->get(0)->number);
        $this->assertSame('000002', $orders->get(1)->number);
    }

    public function test_sequential_numbers_are_isolated_between_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->admin()->company($companyA)->create();
        $userB = User::factory()->admin()->company($companyB)->create();

        $clientA = Client::factory()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create();

        Livewire::actingAs($userA)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientA->id)
            ->set('title', 'OS Empresa A')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($userB)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientB->id)
            ->set('title', 'OS Empresa B')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('000001', ServiceOrder::query()->where('company_id', $companyA->id)->first()->number);
        $this->assertSame('000001', ServiceOrder::query()->where('company_id', $companyB->id)->first()->number);
    }

    public function test_tecnico_cannot_create_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS Proibida')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, ServiceOrder::query()->count());
    }

    public function test_client_is_required_when_creating_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', '')
            ->set('title', 'Sem cliente')
            ->call('save')
            ->assertHasErrors('client_id');

        $this->assertSame(0, ServiceOrder::query()->count());
    }

    public function test_cannot_link_client_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create(['name' => 'Cliente Bravo']);

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientB->id)
            ->set('title', 'Cliente de outra empresa')
            ->call('save')
            ->assertHasErrors('client_id');

        $this->assertSame(0, ServiceOrder::query()->count());
    }

    public function test_admin_can_link_budget_from_same_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('budget_id', (string) $budget->id)
            ->set('title', 'OS com orçamento')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertSame($budget->id, $order->budget_id);
    }

    public function test_cannot_link_budget_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $clientA = Client::factory()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create();
        $budgetB = Budget::factory()->company($companyB)->client($clientB)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientA->id)
            ->set('budget_id', (string) $budgetB->id)
            ->set('title', 'Orçamento de outra empresa')
            ->call('save')
            ->assertHasErrors('budget_id');

        $this->assertSame(0, ServiceOrder::query()->count());
    }

    public function test_title_is_required_when_creating_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors('title');

        $this->assertSame(0, ServiceOrder::query()->count());
    }

    public function test_admin_can_update_service_order_and_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'title' => 'OS Antiga',
            'status' => 'pending',
        ]);

        Livewire::actingAs($user)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS Atualizada')
            ->set('status', 'scheduled')
            ->set('scheduled_at', now()->addDays(5)->format('Y-m-d'))
            ->set('notes', 'Atualizada.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('service_orders', [
            'id' => $order->id,
            'company_id' => $company->id,
            'client_id' => $client->id,
            'title' => 'OS Atualizada',
            'status' => 'scheduled',
            'notes' => 'Atualizada.',
        ]);
    }

    public function test_comercial_can_update_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create(['title' => 'OS Antiga']);

        Livewire::actingAs($user)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS Comercial')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('OS Comercial', $order->fresh()->title);
    }

    public function test_update_keeps_order_in_same_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('client_id', (string) $client->id)
            ->set('title', 'Nome Atualizado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($company->id, $order->fresh()->company_id);
    }

    public function test_tecnico_cannot_update_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create(['title' => 'OS Antiga']);

        Livewire::actingAs($user)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->assertForbidden();

        $this->assertSame('OS Antiga', $order->fresh()->title);
    }

    public function test_show_page_renders_order_info(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create(['name' => 'Cliente Vista']);
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'title' => 'OS Visível',
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->get(route('service-orders.show', $order))
            ->assertOk()
            ->assertSee('OS Visível')
            ->assertSee('Em execução')
            ->assertSee('Cliente Vista');
    }

    public function test_tecnico_can_view_show_page_but_not_manage(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create(['title' => 'OS Visível']);

        $this->actingAs($user)
            ->get(route('service-orders.show', $order))
            ->assertOk()
            ->assertSee('OS Visível')
            ->assertDontSee('Editar')
            ->assertDontSee('Excluir');
    }

    public function test_admin_can_delete_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.show', ['order' => $order])
            ->call('delete')
            ->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseMissing('service_orders', ['id' => $order->id]);
    }

    public function test_comercial_can_delete_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.show', ['order' => $order])
            ->call('delete')
            ->assertRedirect(route('service-orders.index'));

        $this->assertDatabaseMissing('service_orders', ['id' => $order->id]);
    }

    public function test_tecnico_cannot_delete_service_order(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.show', ['order' => $order])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('service_orders', ['id' => $order->id]);
    }

    public function test_all_statuses_are_persisted_and_labeled(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        foreach (ServiceOrderStatus::cases() as $status) {
            $order = ServiceOrder::factory()->company($company)->client($client)->status($status)->create();

            $this->assertSame($status->value, $order->fresh()->status->value);
        }

        $this->assertSame('Concluída', ServiceOrderStatus::Completed->label());
        $this->assertSame('Cancelada', ServiceOrderStatus::Cancelled->label());
    }
}
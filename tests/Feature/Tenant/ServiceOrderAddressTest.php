<?php

namespace Tests\Feature\Tenant;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderAddressTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(Company $company, ?User $technician = null, array $attributes = []): ServiceOrder
    {
        $client = Client::factory()->company($company)->create();

        return ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $technician?->id,
            ...$attributes,
        ]);
    }

    public function test_service_order_can_exist_without_an_address(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Instalação de ar-condicionado')
            ->set('status', 'pending')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertNull($order->client_address_id);
    }

    public function test_address_of_the_selected_client_can_be_selected(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $address = ClientAddress::factory()->client($client)->create([
            'label' => 'Sede',
            'street' => 'Rua das Flores',
            'number' => '100',
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('client_address_id', (string) $address->id)
            ->set('title', 'Manutenção preventiva')
            ->set('status', 'pending')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertSame($address->id, $order->client_address_id);
    }

    public function test_address_of_another_client_is_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $clientA = Client::factory()->company($company)->create();
        $clientB = Client::factory()->company($company)->create();
        $addressB = ClientAddress::factory()->client($clientB)->create();

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientA->id)
            ->set('client_address_id', (string) $addressB->id)
            ->set('title', 'OS inválida')
            ->set('status', 'pending')
            ->call('save')
            ->assertHasErrors('client_address_id');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_address_of_another_company_is_rejected(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->company($companyA)->create();
        $clientA = Client::factory()->company($companyA)->create();
        $clientB = Client::factory()->company($companyB)->create();
        $addressB = ClientAddress::factory()->client($clientB)->create();

        Livewire::actingAs($adminA)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientA->id)
            ->set('client_address_id', (string) $addressB->id)
            ->set('title', 'OS inválida')
            ->set('status', 'pending')
            ->call('save')
            ->assertHasErrors('client_address_id');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_changing_the_client_clears_the_selected_address(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $clientA = Client::factory()->company($company)->create();
        $clientB = Client::factory()->company($company)->create();
        $addressA = ClientAddress::factory()->client($clientA)->create();

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientA->id)
            ->set('client_address_id', (string) $addressA->id)
            ->assertSet('client_address_id', (string) $addressA->id)
            ->set('client_id', (string) $clientB->id)
            ->assertSet('client_address_id', '')
            ->assertSet('budget_id', '');
    }

    public function test_technician_can_view_the_service_order_address(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $address = ClientAddress::factory()->client($client)->create([
            'label' => 'Loja',
            'street' => 'Avenida Central',
            'number' => '500',
            'district' => 'Centro',
            'city' => 'Florianópolis',
            'state' => 'SC',
        ]);
        $order = $this->orderFor($company, $technician, [
            'client_id' => $client->id,
            'client_address_id' => $address->id,
        ]);

        $this->actingAs($technician)
            ->get(route('service-orders.show', $order))
            ->assertOk()
            ->assertSee('Endereço de execução')
            ->assertSee('Loja')
            ->assertSee('Avenida Central')
            ->assertSee('500')
            ->assertSee('Florianópolis')
            ->assertSee('SC');
    }

    public function test_existing_orders_without_address_still_work(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company, null, ['title' => 'OS Sem Endereço']);

        $this->assertNull($order->client_address_id);

        $this->actingAs($admin)
            ->get(route('service-orders.show', $order))
            ->assertOk()
            ->assertSee('OS Sem Endereço')
            ->assertSee('Não informado');

        $this->actingAs($admin)
            ->get(route('service-orders.edit', $order))
            ->assertOk()
            ->assertSee('Sem endereço selecionado');
    }

    public function test_selecting_a_client_shows_its_address_in_the_options(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        ClientAddress::factory()->client($client)->create([
            'street' => 'Rua das Flores',
            'number' => '100',
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->assertDontSee('Rua das Flores')
            ->set('client_id', (string) $client->id)
            ->assertSee('Rua das Flores');
    }

    public function test_address_of_another_client_is_not_listed_in_the_options(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $clientA = Client::factory()->company($company)->create();
        $clientB = Client::factory()->company($company)->create();

        ClientAddress::factory()->client($clientA)->create(['street' => 'Rua Alfa']);
        ClientAddress::factory()->client($clientB)->create(['street' => 'Rua Beta']);

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $clientA->id)
            ->assertSee('Rua Alfa')
            ->assertDontSee('Rua Beta');
    }

    public function test_selecting_a_client_filters_the_dependent_budgets(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $clientA = Client::factory()->company($company)->create();
        $clientB = Client::factory()->company($company)->create();

        Budget::factory()->company($company)->client($clientA)->create([
            'title' => 'Budget Alpha',
            'status' => BudgetStatus::Approved,
        ]);
        Budget::factory()->company($company)->client($clientB)->create([
            'title' => 'Budget Beta',
            'status' => BudgetStatus::Approved,
        ]);

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->assertSee('Budget Beta')
            ->set('client_id', (string) $clientA->id)
            ->assertSee('Budget Alpha')
            ->assertDontSee('Budget Beta');
    }

    public function test_edit_form_refreshes_addresses_when_the_client_changes(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $clientA = Client::factory()->company($company)->create();
        $clientB = Client::factory()->company($company)->create();

        ClientAddress::factory()->client($clientA)->create(['street' => 'Rua Alfa']);
        ClientAddress::factory()->client($clientB)->create(['street' => 'Rua Beta']);

        $order = ServiceOrder::factory()->company($company)->client($clientA)->create();

        Livewire::actingAs($admin)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->assertSee('Rua Alfa')
            ->set('client_id', (string) $clientB->id)
            ->assertSee('Rua Beta')
            ->assertDontSee('Rua Alfa');
    }

    public function test_client_select_uses_live_binding_so_dependent_lists_refresh(): void
    {
        $partial = file_get_contents(resource_path('views/partials/service-order-form.blade.php'));

        $this->assertStringContainsString('wire:model.live="client_id"', $partial);
        $this->assertStringNotContainsString('wire:model="client_id"', $partial);
    }
}

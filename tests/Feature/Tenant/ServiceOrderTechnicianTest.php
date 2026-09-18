<?php

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderTechnicianTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(Company $company): ServiceOrder
    {
        $client = Client::factory()->company($company)->create();

        return ServiceOrder::factory()->company($company)->client($client)->create();
    }

    public function test_service_order_can_be_created_without_technician(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS sem técnico')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertNull($order->technician_id);
    }

    public function test_admin_can_assign_technician_from_same_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS com técnico')
            ->set('technician_id', (string) $technician->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame($technician->id, ServiceOrder::query()->first()->technician_id);
    }

    public function test_comercial_can_assign_technician_from_same_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS com técnico comercial')
            ->set('technician_id', (string) $technician->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame($technician->id, ServiceOrder::query()->first()->technician_id);
    }

    public function test_technician_from_another_company_cannot_be_assigned(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $client = Client::factory()->company($companyA)->create();
        $technicianB = User::factory()->tecnico()->company($companyB)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS com técnico de outra empresa')
            ->set('technician_id', (string) $technicianB->id)
            ->call('save')
            ->assertHasErrors('technician_id');

        $this->assertSame(0, ServiceOrder::query()->count());
    }

    public function test_user_without_tecnico_role_cannot_be_assigned(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $otherAdmin = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'OS com usuário não-técnico')
            ->set('technician_id', (string) $otherAdmin->id)
            ->call('save')
            ->assertHasErrors('technician_id');

        $this->assertSame(0, ServiceOrder::query()->count());
    }

    public function test_technician_cannot_change_the_assignment(): void
    {
        $company = Company::factory()->create();
        $assigned = User::factory()->tecnico()->company($company)->create();
        $other = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $assigned->id,
        ]);

        Livewire::actingAs($assigned)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->assertForbidden();

        $this->assertSame($assigned->id, $order->fresh()->technician_id);
    }

    public function test_admin_can_remove_the_assignment(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $technician->id,
            'budget_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('technician_id', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertNull($order->fresh()->technician_id);
    }

    public function test_comercial_can_remove_the_assignment(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $technician->id,
            'budget_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('technician_id', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($order->fresh()->technician_id);
    }

    public function test_technician_can_view_the_assignment(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $technician->id,
        ]);

        $this->actingAs($technician)
            ->get(route('service-orders.show', $order))
            ->assertOk()
            ->assertSee($technician->name)
            ->assertDontSee('Editar')
            ->assertDontSee('Excluir');
    }

    public function test_assigned_technician_relation_and_reverse_relation(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $technician->id,
        ]);

        $this->assertSame($technician->id, $order->technician->id);
        $this->assertSame($order->id, $technician->assignedServiceOrders()->first()->id);
    }
}
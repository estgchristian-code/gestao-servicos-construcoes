<?php

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_address_to_client(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $client])
            ->call('add')
            ->set('label', 'Casa')
            ->set('street', 'Av. Paulista')
            ->set('number', '1000')
            ->set('city', 'São Paulo')
            ->set('state', 'SP')
            ->set('zip', '01310-100')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('client_addresses', [
            'client_id' => $client->id,
            'street' => 'Av. Paulista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip' => '01310100',
        ]);
    }

    public function test_first_address_is_automatically_main(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $client])
            ->call('add')
            ->set('street', 'Rua A')
            ->set('number', '10')
            ->set('city', 'São Paulo')
            ->set('state', 'SP')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $client->addresses()->where('main', true)->count());
    }

    public function test_addressing_validation_requires_city_and_state(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $client])
            ->call('add')
            ->set('street', '')
            ->set('city', '')
            ->set('state', 'S')
            ->call('save')
            ->assertHasErrors(['street', 'city', 'state']);
    }

    public function test_only_one_main_address_per_client(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        $first = ClientAddress::factory()->client($client)->main()->create();
        $second = ClientAddress::factory()->client($client)->main()->create();

        $this->assertSame(1, $client->addresses()->where('main', true)->count());
        $this->assertFalse($first->fresh()->main);
        $this->assertTrue($second->fresh()->main);
    }

    public function test_make_main_unmarks_previous_main_address(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        $main = ClientAddress::factory()->client($client)->main()->create();
        $other = ClientAddress::factory()->client($client)->create();

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $client])
            ->call('makeMain', $other->id);

        $this->assertFalse($main->fresh()->main);
        $this->assertTrue($other->fresh()->main);
    }

    public function test_admin_can_edit_address(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $address = ClientAddress::factory()->client($client)->create(['street' => 'Rua Antiga']);

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $client])
            ->call('edit', $address->id)
            ->set('street', 'Rua Nova')
            ->set('number', '99')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('client_addresses', ['id' => $address->id, 'street' => 'Rua Nova']);
    }

    public function test_admin_can_delete_address(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $address = ClientAddress::factory()->client($client)->create();

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $client])
            ->call('delete', $address->id);

        $this->assertDatabaseMissing('client_addresses', ['id' => $address->id]);
    }

    public function test_tecnico_cannot_open_address_manager(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $client])
            ->assertForbidden();
    }

    public function test_cross_tenant_client_cannot_have_addresses_managed(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();

        Livewire::actingAs($user)
            ->test('manage-client-addresses', ['client' => $clientB])
            ->assertForbidden();
    }

    public function test_address_of_other_company_cannot_be_edited(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();
        $addressB = ClientAddress::factory()->client($clientB)->create();

        Livewire::actingAs($userA)
            ->test('manage-client-addresses', ['client' => $clientB])
            ->assertForbidden();

        $this->assertFalse($userA->can('update', $addressB));
    }
}

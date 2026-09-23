<?php

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Company;
use App\Models\User;
use App\Support\Documents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('clients.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_clients_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk();
    }

    public function test_commercial_can_access_clients_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk();
    }

    public function test_tecnico_cannot_access_clients_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertForbidden();
    }

    public function test_index_only_lists_clients_from_same_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientA = Client::factory()->company($companyA)->create(['name' => 'Cliente Alpha']);
        Client::factory()->company($companyB)->create(['name' => 'Cliente Bravo']);

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Cliente Alpha')
            ->assertDontSee('Cliente Bravo');
    }

    public function test_index_links_directly_to_address_management(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create(['name' => 'Cliente com Endereços']);

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Cliente com Endereços')
            ->assertSee(route('clients.addresses', $client));

        $this->actingAs($user)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('id="enderecos"', false);
    }

    public function test_show_page_renders_addresses_read_only(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        ClientAddress::factory()->client($client)->create();

        $this->actingAs($user)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Endereços')
            ->assertDontSee('wire:click="add"', false)
            ->assertDontSee('wire:click="makeMain(', false)
            ->assertDontSee('wire:click="edit(', false)
            ->assertDontSee('wire:click="delete(', false);
    }

    public function test_addresses_page_renders_management_actions(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        $this->actingAs($user)
            ->get(route('clients.addresses', $client))
            ->assertOk()
            ->assertSee('Endereços')
            ->assertSee('wire:click="add"', false);
    }

    public function test_addresses_page_returns_404_for_cross_tenant_client(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();

        $this->actingAs($userA)
            ->get(route('clients.addresses', $clientB))
            ->assertNotFound();
    }

    public function test_addresses_page_returns_403_for_tecnico(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        $this->actingAs($user)
            ->get(route('clients.addresses', $client))
            ->assertForbidden();
    }

    public function test_cross_tenant_client_returns_404_on_show(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();

        $this->actingAs($user)
            ->get(route('clients.show', $clientB))
            ->assertNotFound();
    }

    public function test_cross_tenant_client_returns_404_on_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $clientB = Client::factory()->company($companyB)->create();

        $this->actingAs($user)
            ->get(route('clients.edit', $clientB))
            ->assertNotFound();
    }

    public function test_admin_can_create_client_for_own_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $cpf = Documents::generateCpf();

        Livewire::actingAs($user)
            ->test('pages::clients.create')
            ->set('type', 'pf')
            ->set('name', 'Ana Paula Souza')
            ->set('document', $cpf)
            ->set('email', 'ana@exemplo.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $client = Client::query()->first();

        $this->assertNotNull($client);
        $this->assertSame($company->id, $client->company_id);
        $this->assertSame(Documents::normalize($cpf), $client->document);
    }

    public function test_duplicate_document_within_same_company_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Client::factory()->company($company)->pf()->create(['document' => Documents::generateCpf()]);
        $existing = Client::query()->first();

        Livewire::actingAs($user)
            ->test('pages::clients.create')
            ->set('type', 'pf')
            ->set('name', 'Outra Pessoa')
            ->set('document', $existing->document)
            ->call('save')
            ->assertHasErrors('document');

        $this->assertSame(1, Client::query()->count());
    }

    public function test_same_document_is_allowed_in_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->admin()->company($companyA)->create();
        $userB = User::factory()->admin()->company($companyB)->create();

        $cpf = Documents::generateCpf();

        Livewire::actingAs($userA)
            ->test('pages::clients.create')
            ->set('type', 'pf')
            ->set('name', 'Ana')
            ->set('document', $cpf)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($userB)
            ->test('pages::clients.create')
            ->set('type', 'pf')
            ->set('name', 'Outra Ana')
            ->set('document', $cpf)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Client::query()->count());
    }

    public function test_invalid_document_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::clients.create')
            ->set('type', 'pf')
            ->set('name', 'Ana')
            ->set('document', '11111111111')
            ->call('save')
            ->assertHasErrors('document');

        $this->assertSame(0, Client::query()->count());
    }

    public function test_tecnico_cannot_create_client(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::clients.create')
            ->set('type', 'pf')
            ->set('name', 'Ana')
            ->set('document', Documents::generateCpf())
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Client::query()->count());
    }

    public function test_admin_can_update_client(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->pf()->create();

        Livewire::actingAs($user)
            ->test('pages::clients.edit', ['client' => $client])
            ->set('name', 'Nome Atualizado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Nome Atualizado',
            'company_id' => $company->id,
        ]);
    }

    public function test_update_keeps_client_in_same_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->pf()->create();

        Livewire::actingAs($user)
            ->test('pages::clients.edit', ['client' => $client])
            ->set('email', 'novo@exemplo.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($company->id, $client->fresh()->company_id);
    }

    public function test_tecnico_cannot_update_client(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->pf()->create();

        Livewire::actingAs($user)
            ->test('pages::clients.edit', ['client' => $client])
            ->assertForbidden();
    }

    public function test_show_page_renders_client_info(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->pf()->create(['name' => 'Cliente Visível']);

        $this->actingAs($user)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Cliente Visível');
    }

    public function test_admin_can_toggle_client_active_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create(['active' => true]);

        Livewire::actingAs($user)
            ->test('pages::clients.show', ['client' => $client])
            ->call('toggleActive');

        $this->assertFalse($client->fresh()->active);

        Livewire::actingAs($user)
            ->test('pages::clients.show', ['client' => $client])
            ->call('toggleActive');

        $this->assertTrue($client->fresh()->active);
    }

    public function test_admin_can_delete_client_softly(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::clients.show', ['client' => $client])
            ->call('delete')
            ->assertRedirect(route('clients.index'));

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_tecnico_cannot_delete_client(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::clients.show', ['client' => $client])
            ->assertForbidden();

        $this->assertNotSoftDeleted('clients', ['id' => $client->id]);
    }
}

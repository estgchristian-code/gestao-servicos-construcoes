<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('services.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_services_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $this->actingAs($user)
            ->get(route('services.index'))
            ->assertOk();
    }

    public function test_comercial_can_access_services_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();

        $this->actingAs($user)
            ->get(route('services.index'))
            ->assertOk();
    }

    public function test_tecnico_can_access_services_index_but_only_view(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        Service::factory()->company($company)->create(['name' => 'Serviço Único']);

        $this->actingAs($user)
            ->get(route('services.index'))
            ->assertOk()
            ->assertSee('Serviço Único')
            ->assertDontSee('Novo serviço')
            ->assertDontSee('Editar');
    }

    public function test_index_only_lists_services_from_same_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        Service::factory()->company($companyA)->create(['name' => 'Serviço Alpha']);
        Service::factory()->company($companyB)->create(['name' => 'Serviço Bravo']);

        $this->actingAs($user)
            ->get(route('services.index'))
            ->assertOk()
            ->assertSee('Serviço Alpha')
            ->assertDontSee('Serviço Bravo');
    }

    public function test_cross_tenant_service_returns_404_on_show(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $serviceB = Service::factory()->company($companyB)->create();

        $this->actingAs($user)
            ->get(route('services.show', $serviceB))
            ->assertNotFound();
    }

    public function test_cross_tenant_service_returns_404_on_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        $serviceB = Service::factory()->company($companyB)->create();

        $this->actingAs($user)
            ->get(route('services.edit', $serviceB))
            ->assertNotFound();
    }

    public function test_admin_can_create_service_for_own_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.create')
            ->set('name', 'Manutenção Preventiva')
            ->set('description', 'Check-up completo do equipamento')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $service = Service::query()->first();

        $this->assertNotNull($service);
        $this->assertSame($company->id, $service->company_id);
        $this->assertSame('Manutenção Preventiva', $service->name);
        $this->assertSame('Check-up completo do equipamento', $service->description);
        $this->assertTrue($service->active);
    }

    public function test_comercial_can_create_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.create')
            ->set('name', 'Instalação Elétrica')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertSame(1, Service::query()->count());
        $this->assertSame($company->id, Service::query()->first()->company_id);
    }

    public function test_tecnico_cannot_create_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.create')
            ->set('name', 'Serviço Proibido')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Service::query()->count());
    }

    public function test_name_is_required_when_creating_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.create')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors('name');

        $this->assertSame(0, Service::query()->count());
    }

    public function test_admin_can_update_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $service = Service::factory()->company($company)->create(['name' => 'Serviço Antigo']);

        Livewire::actingAs($user)
            ->test('pages::services.edit', ['service' => $service])
            ->set('name', 'Serviço Atualizado')
            ->set('description', 'Nova descrição')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Serviço Atualizado',
            'description' => 'Nova descrição',
            'company_id' => $company->id,
        ]);
    }

    public function test_comercial_can_update_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $service = Service::factory()->company($company)->create(['name' => 'Serviço Antigo']);

        Livewire::actingAs($user)
            ->test('pages::services.edit', ['service' => $service])
            ->set('name', 'Serviço Comercial')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Serviço Comercial', $service->fresh()->name);
    }

    public function test_update_keeps_service_in_same_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $service = Service::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.edit', ['service' => $service])
            ->set('name', 'Nome Atualizado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($company->id, $service->fresh()->company_id);
    }

    public function test_tecnico_cannot_update_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $service = Service::factory()->company($company)->create(['name' => 'Serviço Antigo']);

        Livewire::actingAs($user)
            ->test('pages::services.edit', ['service' => $service])
            ->assertForbidden();

        $this->assertSame('Serviço Antigo', $service->fresh()->name);
    }

    public function test_show_page_renders_service_info(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $service = Service::factory()->company($company)->create(['name' => 'Serviço Visível']);

        $this->actingAs($user)
            ->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('Serviço Visível');
    }

    public function test_tecnico_can_view_show_page_but_not_manage(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $service = Service::factory()->company($company)->create(['name' => 'Serviço Visível']);

        $this->actingAs($user)
            ->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('Serviço Visível')
            ->assertDontSee('Editar')
            ->assertDontSee('Excluir')
            ->assertDontSee('Desativar');
    }

    public function test_admin_can_toggle_service_active_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $service = Service::factory()->company($company)->create(['active' => true]);

        Livewire::actingAs($user)
            ->test('pages::services.show', ['service' => $service])
            ->call('toggleActive');

        $this->assertFalse($service->fresh()->active);

        Livewire::actingAs($user)
            ->test('pages::services.show', ['service' => $service])
            ->call('toggleActive');

        $this->assertTrue($service->fresh()->active);
    }

    public function test_admin_can_delete_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $service = Service::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.show', ['service' => $service])
            ->call('delete')
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_comercial_can_delete_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $service = Service::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.show', ['service' => $service])
            ->call('delete')
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_tecnico_cannot_delete_service(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $service = Service::factory()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::services.show', ['service' => $service])
            ->call('delete')
            ->assertForbidden();

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_tecnico_cannot_toggle_service_active_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();
        $service = Service::factory()->company($company)->create(['active' => true]);

        Livewire::actingAs($user)
            ->test('pages::services.show', ['service' => $service])
            ->call('toggleActive')
            ->assertForbidden();

        $this->assertTrue($service->fresh()->active);
    }
}
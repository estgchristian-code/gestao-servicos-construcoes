<?php

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TechnicianManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_technicians_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_comercial_cannot_access_technicians_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_tecnico_cannot_access_technicians_index(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_index_only_lists_technicians_from_same_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        User::factory()->tecnico()->company($companyA)->create(['name' => 'Técnico Alpha']);
        User::factory()->tecnico()->company($companyB)->create(['name' => 'Técnico Bravo']);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Técnico Alpha')
            ->assertDontSee('Técnico Bravo');
    }

    public function test_index_only_lists_users_with_tecnico_role(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $technician = User::factory()->tecnico()->company($company)->create(['name' => 'Técnico Listado']);
        $comercial = User::factory()->comercial()->company($company)->create(['name' => 'Comercial Não Listado']);

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($technician->name)
            ->assertDontSee($comercial->name);
    }

    public function test_admin_can_create_technician_with_role_and_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->set('name', 'Carlos Técnico')
            ->set('email', 'carlos@empresa.com')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $technician = User::query()->where('email', 'carlos@empresa.com')->first();

        $this->assertNotNull($technician);
        $this->assertSame($company->id, $technician->company_id);
        $this->assertTrue($technician->isTecnico());
        $this->assertFalse($technician->isAdmin());
        $this->assertTrue($technician->active);
        $this->assertFalse($technician->isSuperAdmin());
        $this->assertTrue(Hash::check('secret123', $technician->password));
    }

    public function test_comercial_cannot_create_technician(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->set('name', 'Técnico')
            ->set('email', 'tecnico@empresa.com')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(1, User::query()->count());
    }

    public function test_tecnico_cannot_create_technician(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->tecnico()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->call('save')
            ->assertForbidden();
    }

    public function test_password_is_required_and_confirmed_when_creating(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->set('name', 'Sem Senha')
            ->set('email', 'semsenha@empresa.com')
            ->call('save')
            ->assertHasErrors('password');

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->set('name', 'Senha Curta')
            ->set('email', 'curta@empresa.com')
            ->set('password', '123')
            ->set('password_confirmation', '123')
            ->call('save')
            ->assertHasErrors('password');

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->set('name', 'Senha Diferente')
            ->set('email', 'diferente@empresa.com')
            ->set('password', '12345678')
            ->set('password_confirmation', '98765432')
            ->call('save')
            ->assertHasErrors('password');

        $this->assertSame(1, User::query()->count());
    }

    public function test_email_must_be_unique_globally(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();

        User::factory()->tecnico()->company($companyB)->create(['email' => 'duplicado@empresa.com']);

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->set('name', 'Novo Técnico')
            ->set('email', 'duplicado@empresa.com')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('save')
            ->assertHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'duplicado@empresa.com')->count());
    }

    public function test_cross_tenant_technician_returns_forbidden_on_edit(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $technicianB = User::factory()->tecnico()->company($companyB)->create();

        Livewire::actingAs($user)
            ->test('pages::users.edit', ['user' => $technicianB])
            ->assertForbidden();
    }

    public function test_admin_can_edit_technician_keeping_role_and_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create(['name' => 'Nome Antigo']);

        Livewire::actingAs($user)
            ->test('pages::users.edit', ['user' => $technician])
            ->set('name', 'Nome Atualizado')
            ->set('email', 'novo@empresa.com')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $fresh = $technician->fresh();

        $this->assertSame('Nome Atualizado', $fresh->name);
        $this->assertSame('novo@empresa.com', $fresh->email);
        $this->assertSame($company->id, $fresh->company_id);
        $this->assertTrue($fresh->isTecnico());
    }

    public function test_edit_keeps_same_email_without_unique_error(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create(['email' => 'mantido@empresa.com']);

        Livewire::actingAs($user)
            ->test('pages::users.edit', ['user' => $technician])
            ->set('name', 'Nome')
            ->set('email', 'mantido@empresa.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('mantido@empresa.com', $technician->fresh()->email);
    }

    public function test_edit_does_not_change_password_when_left_blank(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create(['name' => 'Nome']);

        $before = $technician->password;

        Livewire::actingAs($user)
            ->test('pages::users.edit', ['user' => $technician])
            ->set('name', 'Nome Editado')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $technician->fresh();

        $this->assertSame('Nome Editado', $fresh->name);
        $this->assertSame($before, $fresh->password);
    }

    public function test_edit_updates_password_when_filled(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.edit', ['user' => $technician])
            ->set('email', 'outro@empresa.com')
            ->set('password', 'novaSenha12')
            ->set('password_confirmation', 'novaSenha12')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('novaSenha12', $technician->fresh()->password));
    }

    public function test_admin_can_create_inactive_technician(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.create')
            ->set('name', 'Técnico Inativo')
            ->set('email', 'inativo@empresa.com')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->set('active', false)
            ->call('save')
            ->assertHasNoErrors();

        $technician = User::query()->where('email', 'inativo@empresa.com')->first();

        $this->assertNotNull($technician);
        $this->assertFalse($technician->active);
    }

    public function test_admin_can_toggle_technician_active_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create(['active' => true]);

        Livewire::actingAs($user)
            ->test('pages::users.index')
            ->call('toggleActive', $technician->id);

        $this->assertFalse($technician->fresh()->active);

        Livewire::actingAs($user)
            ->test('pages::users.index')
            ->call('toggleActive', $technician->id);

        $this->assertTrue($technician->fresh()->active);
    }

    public function test_admin_cannot_toggle_technician_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->admin()->company($companyA)->create();
        $technicianB = User::factory()->tecnico()->company($companyB)->create(['active' => true]);

        Livewire::actingAs($user)
            ->test('pages::users.index')
            ->call('toggleActive', $technicianB->id)
            ->assertForbidden();

        $this->assertTrue($technicianB->fresh()->active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.index')
            ->call('toggleActive', $user->id)
            ->assertOk();

        $this->assertTrue($user->fresh()->active);
    }

    public function test_newly_created_technician_appears_in_service_order_select(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        Client::factory()->company($company)->create();

        $technician = User::factory()->tecnico()->company($company)->create(['name' => 'Técnico da OS']);

        Livewire::actingAs($user)
            ->test('pages::service-orders.create')
            ->assertOk()
            ->assertSee($technician->name);
    }

    public function test_edit_page_is_denied_for_comercial(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->comercial()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();

        Livewire::actingAs($user)
            ->test('pages::users.edit', ['user' => $technician])
            ->assertForbidden();
    }
}
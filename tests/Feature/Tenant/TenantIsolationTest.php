<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_belongs_to_its_company(): void
    {
        $company = Company::factory()->create();
        $other = Company::factory()->create();
        $user = User::factory()->for($company)->create();

        $this->assertTrue($user->company->is($company));
        $this->assertTrue($user->belongsToCompany($company));
        $this->assertFalse($user->belongsToCompany($other));
        $this->assertSame($company->id, $user->company_id);
        $this->assertSame(1, $company->users()->count());
    }

    public function test_admin_can_view_users_of_own_company_only(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $adminA = User::factory()->for($companyA)->admin()->create();
        $userA = User::factory()->for($companyA)->create();
        $userB = User::factory()->for($companyB)->create();

        $this->assertTrue(Gate::forUser($adminA)->allows('view', $userA));
        $this->assertFalse(Gate::forUser($adminA)->allows('view', $userB));
        $this->assertTrue(Gate::forUser($adminA)->allows('viewAny', User::class));
    }

    public function test_user_cannot_access_resources_of_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->for($companyA)->create();

        $this->assertFalse(Gate::forUser($userA)->allows('view', $companyB));
        $this->assertTrue(Gate::forUser($userA)->allows('view', $companyA));
    }

    public function test_tecnico_has_restricted_authorization(): void
    {
        $company = Company::factory()->create();
        $tecnico = User::factory()->for($company)->tecnico()->create();
        $colleague = User::factory()->for($company)->comercial()->create();

        $this->assertFalse(Gate::forUser($tecnico)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($tecnico)->allows('view', $colleague));
        $this->assertTrue(Gate::forUser($tecnico)->allows('view', $tecnico));
        $this->assertFalse(Gate::forUser($tecnico)->allows('update', $company));
    }

    public function test_comercial_has_restricted_authorization(): void
    {
        $company = Company::factory()->create();
        $comercial = User::factory()->for($company)->comercial()->create();
        $tecnico = User::factory()->for($company)->tecnico()->create();

        $this->assertFalse(Gate::forUser($comercial)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($comercial)->allows('view', $tecnico));
        $this->assertFalse(Gate::forUser($comercial)->allows('update', $company));
    }

    public function test_admin_of_one_company_cannot_update_or_delete_user_of_another(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        $adminA = User::factory()->for($companyA)->admin()->create();
        $userB = User::factory()->for($companyB)->create();

        $this->assertFalse(Gate::forUser($adminA)->allows('update', $userB));
        $this->assertFalse(Gate::forUser($adminA)->allows('delete', $userB));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->admin()->create();

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $admin));
    }
}

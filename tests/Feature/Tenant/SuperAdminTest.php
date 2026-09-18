<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_is_not_limited_by_company(): void
    {
        $company = Company::factory()->create();
        $superadmin = User::factory()->superadmin()->create();

        $this->assertNull($superadmin->company_id);
        $this->assertTrue($superadmin->isSuperAdmin());
        $this->assertFalse($superadmin->belongsToCompany($company));

        $this->assertTrue(Gate::forUser($superadmin)->allows('view', $company));
        $this->assertTrue(Gate::forUser($superadmin)->allows('update', $company));
        $this->assertTrue(Gate::forUser($superadmin)->allows('viewAny', User::class));
    }

    public function test_superadmin_can_view_user_of_any_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        $superadmin = User::factory()->superadmin()->create();

        $this->assertTrue(Gate::forUser($superadmin)->allows('view', $user));
        $this->assertTrue(Gate::forUser($superadmin)->allows('update', $user));
        $this->assertTrue(Gate::forUser($superadmin)->allows('delete', $user));
    }

    public function test_superadmin_can_create_user_for_any_company(): void
    {
        $company = Company::factory()->create();
        $superadmin = User::factory()->superadmin()->create();

        $this->assertTrue(Gate::forUser($superadmin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($superadmin)->allows('create', [User::class, $company]));
    }
}

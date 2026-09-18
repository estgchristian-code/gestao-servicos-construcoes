<?php

namespace Tests\Feature\Tenant;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_factory_user_has_tecnico_profile(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isTecnico());
        $this->assertSame(UserRole::Tecnico->value, $user->roleSlug());
    }

    public function test_admin_profile_is_respected(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->hasRole(UserRole::Admin));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->isAdmin());
        $this->assertSame('admin', $user->roleSlug());
        $this->assertFalse($user->isComercial());
        $this->assertFalse($user->isTecnico());
    }

    public function test_comercial_profile_is_respected(): void
    {
        $user = User::factory()->comercial()->create();

        $this->assertTrue($user->isComercial());
        $this->assertSame('comercial', $user->roleSlug());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isTecnico());
    }

    public function test_every_user_belongs_to_a_company_and_has_a_role(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->company_id);
        $this->assertNotNull($user->company);
        $this->assertNotNull($user->primaryRole());
        $this->assertNotNull($user->roleLabel());
    }

    public function test_role_assignment_is_stored_in_pivot(): void
    {
        $user = User::factory()->create();
        $role = $user->primaryRole();

        $this->assertDatabaseHas('role_user', [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);
    }
}

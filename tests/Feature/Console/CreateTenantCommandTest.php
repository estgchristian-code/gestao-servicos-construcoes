<?php

namespace Tests\Feature\Console;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateTenantCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_company_and_first_admin_user(): void
    {
        $this->artisan('tenant:create', [
            'name' => 'Empresa Alpha',
            'email' => 'admin@alpha.test',
            '--password' => 'secret-password',
        ])->assertExitCode(0);

        $company = Company::where('name', 'Empresa Alpha')->first();
        $this->assertNotNull($company);
        $this->assertSame('empresa-alpha', $company->slug);

        $user = User::where('email', 'admin@alpha.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($company->id, $user->company_id);
        $this->assertSame('Administrador', $user->name);
        $this->assertTrue($user->active);
        $this->assertFalse($user->is_superadmin);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_command_generates_unique_slug_when_name_collides(): void
    {
        Company::factory()->create(['slug' => 'empresa-alpha']);

        $this->artisan('tenant:create', [
            'name' => 'Empresa Alpha',
            'email' => 'admin@alpha.test',
            '--password' => 'secret-password',
        ])->assertExitCode(0);

        $company = Company::where('email', 'admin@alpha.test')
            ->orWhere('name', 'Empresa Alpha')
            ->orderByDesc('id')
            ->first();

        $this->assertNotSame('empresa-alpha', $company->slug);
    }

    public function test_command_rejects_invalid_email(): void
    {
        $this->artisan('tenant:create', [
            'name' => 'Empresa Beta',
            'email' => 'not-an-email',
            '--password' => 'secret-password',
        ])->assertExitCode(1);

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_command_rejects_short_password(): void
    {
        $this->artisan('tenant:create', [
            'name' => 'Empresa Gamma',
            'email' => 'admin@gamma.test',
            '--password' => '123',
        ])->assertExitCode(1);

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_command_rejects_duplicate_email(): void
    {
        $company = Company::factory()->create(['slug' => 'empresa-delta']);
        User::factory()->for($company)->create(['email' => 'admin@delta.test']);

        $this->artisan('tenant:create', [
            'name' => 'Empresa Delta',
            'email' => 'admin@delta.test',
            '--password' => 'secret-password',
        ])->assertExitCode(1);
    }
}

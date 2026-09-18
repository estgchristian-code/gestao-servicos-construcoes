<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_by_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_active_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@empresa.test',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@empresa.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inativo@empresa.test',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => 'inativo@empresa.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_with_wrong_password_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'admin@empresa.test',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@empresa.test',
            'password' => 'incorrect',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('home'));
    }

    public function test_logout_destroys_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_session_survives_and_keeps_company_binding(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create([
            'email' => 'tecnico@empresa.test',
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => 'tecnico@empresa.test',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(auth()->user()->belongsToCompany($company));

        $this->get('/home')
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee('Dashboard');
    }
}

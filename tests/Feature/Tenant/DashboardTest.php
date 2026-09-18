<?php

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(Company $company, ?User $technician = null, array $attributes = []): ServiceOrder
    {
        $client = Client::factory()->company($company)->create();

        return ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $technician?->id,
            ...$attributes,
        ]);
    }

    public function test_admin_and_comercial_see_the_company_counts(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $comercial = User::factory()->comercial()->company($company)->create();

        $this->orderFor($company, null, ['status' => 'pending', 'scheduled_at' => null, 'created_at' => now()->subDays(6)]);
        $this->orderFor($company, null, ['status' => 'scheduled', 'scheduled_at' => now()->addDays(1)]);
        $this->orderFor($company, null, ['status' => 'scheduled', 'scheduled_at' => now()->addDays(2)]);
        $this->orderFor($company, null, ['status' => 'in_progress', 'scheduled_at' => now()->toDateString()]);
        $this->orderFor($company, null, ['status' => 'paused', 'scheduled_at' => now()->toDateString()]);
        $this->orderFor($company, null, ['status' => 'completed', 'scheduled_at' => now()->subDay(), 'created_at' => now()->subDays(3)]);
        $this->orderFor($company, null, ['status' => 'completed', 'scheduled_at' => now()->subDays(2), 'created_at' => now()->subDays(2)]);
        $this->orderFor($company, null, ['status' => 'completed', 'scheduled_at' => now()->subDays(3), 'created_at' => now()->subDays(1)]);
        $this->orderFor($company, null, ['status' => 'cancelled', 'scheduled_at' => null]);

        foreach ([$admin, $comercial] as $user) {
            Livewire::actingAs($user)
                ->test('pages::home')
                ->assertSet('openCount', 5)
                ->assertSet('scheduledCount', 2)
                ->assertSet('inProgressCount', 1)
                ->assertSet('pausedCount', 1)
                ->assertSet('completedCount', 3)
                ->assertSet('cancelledCount', 1)
                ->assertSet('todayCount', 2)
                ->assertSet('statusCounts.pending', 1)
                ->assertSee('OS abertas')
                ->assertSee('Para hoje')
                ->assertSee('OS por status')
                ->assertSee('Próximas OS agendadas')
                ->assertSee('Últimas ordens de serviço');
        }
    }

    public function test_dashboard_is_isolated_between_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->company($companyA)->create();
        $adminB = User::factory()->admin()->company($companyB)->create();

        $orderA = $this->orderFor($companyA, null, ['status' => 'in_progress', 'scheduled_at' => now()->toDateString(), 'title' => 'OS visível da empresa A']);
        $this->orderFor($companyA, null, ['status' => 'completed', 'scheduled_at' => now()->subDay()]);
        $orderB = $this->orderFor($companyB, null, ['status' => 'pending', 'scheduled_at' => null, 'title' => 'OS da empresa B']);
        $this->orderFor($companyB, null, ['status' => 'pending', 'scheduled_at' => null]);
        $this->orderFor($companyB, null, ['status' => 'cancelled', 'scheduled_at' => null]);

        Livewire::actingAs($adminA)
            ->test('pages::home')
            ->assertSet('openCount', 1)
            ->assertSet('completedCount', 1)
            ->assertSet('cancelledCount', 0)
            ->assertSee('OS #' . $orderA->number)
            ->assertSee('OS visível da empresa A')
            ->assertDontSee('OS da empresa B');

        Livewire::actingAs($adminB)
            ->test('pages::home')
            ->assertSet('openCount', 2)
            ->assertSet('cancelledCount', 1)
            ->assertSee('OS #' . $orderB->number)
            ->assertDontSee('OS visível da empresa A');
    }

    public function test_technician_sees_only_the_orders_assigned_to_him(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();

        $assignedActive = $this->orderFor($company, $technician, ['status' => 'in_progress', 'scheduled_at' => now()->toDateString(), 'title' => 'OS atribuída ativa']);
        $assignedDone = $this->orderFor($company, $technician, ['status' => 'completed', 'scheduled_at' => now()->subDay(), 'title' => 'OS atribuída concluída']);
        $unassigned = $this->orderFor($company, null, ['status' => 'scheduled', 'scheduled_at' => now()->addDay(), 'title' => 'OS sem técnico']);
        $this->orderFor($company, null, ['status' => 'in_progress', 'scheduled_at' => now()->toDateString()]);

        Livewire::actingAs($technician)
            ->test('pages::home')
            ->assertSet('openCount', 1)
            ->assertSet('inProgressCount', 1)
            ->assertSet('scheduledCount', 0)
            ->assertSet('completedCount', 1)
            ->assertSet('statusCounts.scheduled', 0)
            ->assertSee('OS #' . $assignedActive->number)
            ->assertSee('OS #' . $assignedDone->number)
            ->assertDontSee('OS sem técnico')
            ->assertDontSee('OS #' . $unassigned->number);
    }

    public function test_today_and_upcoming_scheduled_orders(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();

        $this->orderFor($company, null, ['status' => 'scheduled', 'scheduled_at' => now()->toDateString(), 'title' => 'OS hoje um']);
        $this->orderFor($company, null, ['status' => 'scheduled', 'scheduled_at' => now()->toDateString(), 'title' => 'OS hoje dois']);
        $tomorrow = $this->orderFor($company, null, ['status' => 'scheduled', 'scheduled_at' => now()->addDay()->toDateString(), 'title' => 'OS amanhã']);
        $nextWeek = $this->orderFor($company, null, ['status' => 'in_progress', 'scheduled_at' => now()->addDays(7)->toDateString(), 'title' => 'OS próxima semana']);
        $unscheduled = $this->orderFor($company, null, ['status' => 'scheduled', 'scheduled_at' => null, 'title' => 'OS sem data', 'created_at' => now()->subDays(2)]);

        for ($i = 1; $i <= 5; $i++) {
            $this->orderFor($company, null, ['status' => 'completed', 'scheduled_at' => null]);
        }

        Livewire::actingAs($admin)
            ->test('pages::home')
            ->assertSet('todayCount', 2)
            ->assertSee('OS hoje um')
            ->assertSee('OS hoje dois')
            ->assertSee('OS amanhã')
            ->assertSee('OS próxima semana')
            ->assertDontSee('OS sem data')
            ->assertSee('OS #' . $tomorrow->number)
            ->assertSee('OS #' . $nextWeek->number)
            ->assertDontSee('OS #' . $unscheduled->number);
    }
}
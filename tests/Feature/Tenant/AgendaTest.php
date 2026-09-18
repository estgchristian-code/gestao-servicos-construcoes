<?php

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaTest extends TestCase
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

    private function ptMonth(CarbonImmutable $date): string
    {
        $names = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        return $names[$date->month] . ' de ' . $date->year;
    }

    public function test_admin_can_access_the_agenda_and_see_scheduled_orders(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company, null, ['scheduled_at' => now()->startOfMonth()->addDays(5)->format('Y-m-d')]);

        $this->actingAs($admin)
            ->get(route('agenda'))
            ->assertOk()
            ->assertSee('Agenda')
            ->assertSee($this->ptMonth(CarbonImmutable::now()))
            ->assertSee('OS #' . $order->number)
            ->assertSee($order->title)
            ->assertSee($order->client->name);
    }

    public function test_agenda_is_isolated_between_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->company($companyA)->create();
        $orderA = $this->orderFor($companyA, null, ['scheduled_at' => now()->startOfMonth()->addDays(5)->format('Y-m-d'), 'title' => 'OS da empresa A']);
        $orderB = $this->orderFor($companyB, null, ['scheduled_at' => now()->startOfMonth()->addDays(6)->format('Y-m-d'), 'title' => 'OS da empresa B']);

        $this->actingAs($adminA)
            ->get(route('agenda'))
            ->assertOk()
            ->assertSee('OS #' . $orderA->number)
            ->assertSee('OS da empresa A')
            ->assertDontSee('OS #' . $orderB->number)
            ->assertDontSee('OS da empresa B');
    }

    public function test_admin_and_comercial_see_all_company_orders(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $comercial = User::factory()->comercial()->company($company)->create();
        $orderOne = $this->orderFor($company, null, ['scheduled_at' => now()->startOfMonth()->addDays(5)->format('Y-m-d'), 'title' => 'OS um']);
        $orderTwo = $this->orderFor($company, null, ['scheduled_at' => now()->startOfMonth()->addDays(8)->format('Y-m-d'), 'title' => 'OS dois']);

        foreach ([$admin, $comercial] as $user) {
            $this->actingAs($user)
                ->get(route('agenda'))
                ->assertOk()
                ->assertSee('OS #' . $orderOne->number)
                ->assertSee('OS #' . $orderTwo->number);
        }
    }

    public function test_technician_sees_only_the_orders_assigned_to_him(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $otherTechnician = User::factory()->tecnico()->company($company)->create();
        $assignedOrder = $this->orderFor($company, $technician, ['scheduled_at' => now()->startOfMonth()->addDays(5)->format('Y-m-d'), 'title' => 'OS do técnico']);
        $otherOrder = $this->orderFor($company, $otherTechnician, ['scheduled_at' => now()->startOfMonth()->addDays(6)->format('Y-m-d'), 'title' => 'OS de outro técnico']);
        $this->orderFor($company, null, ['scheduled_at' => now()->startOfMonth()->addDays(7)->format('Y-m-d'), 'title' => 'OS sem técnico']);

        $this->actingAs($technician)
            ->get(route('agenda'))
            ->assertOk()
            ->assertSee('OS #' . $assignedOrder->number)
            ->assertSee('OS do técnico')
            ->assertDontSee('OS #' . $otherOrder->number)
            ->assertDontSee('OS de outro técnico')
            ->assertDontSee('OS sem técnico');
    }

    public function test_technician_without_assignments_still_opens_the_agenda(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $this->orderFor($company, null, ['scheduled_at' => now()->startOfMonth()->addDays(5)->format('Y-m-d'), 'title' => 'OS sem técnico']);

        $this->actingAs($technician)
            ->get(route('agenda'))
            ->assertOk()
            ->assertSee('Agenda')
            ->assertDontSee('OS sem técnico');
    }

    public function test_orders_without_scheduled_at_do_not_appear_in_the_agenda(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $unscheduled = $this->orderFor($company, null, ['scheduled_at' => null, 'title' => 'OS sem data']);
        $this->orderFor($company, null, ['scheduled_at' => now()->startOfMonth()->addDays(5)->format('Y-m-d'), 'title' => 'OS com data']);

        $this->actingAs($admin)
            ->get(route('agenda'))
            ->assertOk()
            ->assertDontSee('OS #' . $unscheduled->number)
            ->assertDontSee('OS sem data');
    }

    public function test_month_navigation_shows_and_hides_orders_and_labels(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();

        $nextMonth = CarbonImmutable::now()->addMonthsNoOverflow();
        $midNextMonth = $nextMonth->firstOfMonth()->addDays(12);
        $orderNext = $this->orderFor($company, null, ['scheduled_at' => $midNextMonth->format('Y-m-d'), 'title' => 'OS do próximo mês']);

        Livewire::actingAs($admin)
            ->test('pages::agenda')
            ->assertSet('month', CarbonImmutable::now()->format('Y-m'))
            ->assertSee($this->ptMonth(CarbonImmutable::now()))
            ->assertDontSee('OS #' . $orderNext->number)
            ->call('nextMonth')
            ->assertSet('month', $nextMonth->format('Y-m'))
            ->assertSee($this->ptMonth($nextMonth))
            ->assertSee('OS #' . $orderNext->number)
            ->call('nextMonth')
            ->assertSee($this->ptMonth($nextMonth->addMonthsNoOverflow()))
            ->assertDontSee('OS #' . $orderNext->number)
            ->call('prevMonth')
            ->call('prevMonth')
            ->assertSet('month', CarbonImmutable::now()->format('Y-m'))
            ->assertSee($this->ptMonth(CarbonImmutable::now()));
    }
}
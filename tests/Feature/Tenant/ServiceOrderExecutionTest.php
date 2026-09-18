<?php

namespace Tests\Feature\Tenant;

use App\Enums\ServiceOrderExecutionEventType;
use App\Enums\ServiceOrderStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderExecutionEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderExecutionTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(Company $company, array $attributes = []): ServiceOrder
    {
        $client = Client::factory()->company($company)->create();

        return ServiceOrder::factory()->company($company)->client($client)->create([
            ...$attributes,
        ]);
    }

    public function test_admin_can_start_execution_recording_user_notes_and_timestamp(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);
        $before = now();

        Livewire::actingAs($admin)
            ->test('manage-service-order-execution', ['order' => $order])
            ->set('event_notes', 'Iniciando a instalação do ar-condicionado.')
            ->call('start')
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSame(ServiceOrderStatus::InProgress, $order->fresh()->status);

        $event = $order->executionEvents()->first();

        $this->assertNotNull($event);
        $this->assertSame(ServiceOrderExecutionEventType::Started, $event->type);
        $this->assertSame($admin->id, $event->user_id);
        $this->assertSame($order->company_id, $event->company_id);
        $this->assertSame('Iniciando a instalação do ar-condicionado.', $event->notes);
        $this->assertNotNull($event->created_at);
        $this->assertTrue($event->created_at->gte($before->startOfSecond()));
    }

    public function test_comercial_can_start_execution(): void
    {
        $company = Company::factory()->create();
        $comercial = User::factory()->comercial()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($comercial)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('start')
            ->assertOk();

        $this->assertSame(ServiceOrderStatus::InProgress, $order->fresh()->status);
        $this->assertSame($comercial->id, $order->executionEvents()->first()->user_id);
    }

    public function test_assigned_technician_can_start_execution(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);

        Livewire::actingAs($technician)
            ->test('manage-service-order-execution', ['order' => $order])
            ->set('event_notes', 'Técnico atribuído iniciou.')
            ->call('start')
            ->assertOk();

        $this->assertSame(ServiceOrderStatus::InProgress, $order->fresh()->status);
        $this->assertSame($technician->id, $order->executionEvents()->first()->user_id);
    }

    public function test_unassigned_technician_cannot_register_execution(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($technician)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('start')
            ->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, $order->executionEvents()->count());
    }

    public function test_user_from_another_company_cannot_access_execution(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminB = User::factory()->admin()->company($companyB)->create();
        $technicianB = User::factory()->tecnico()->company($companyB)->create();
        $order = $this->orderFor($companyA);

        Livewire::actingAs($adminB)
            ->test('manage-service-order-execution', ['order' => $order])
            ->assertForbidden();

        Livewire::actingAs($technicianB)
            ->test('manage-service-order-execution', ['order' => $order])
            ->assertForbidden();

        $this->assertSame(0, $order->executionEvents()->count());
    }

    public function test_full_execution_flow_registers_the_event_history(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $order])
            ->set('event_notes', 'Serviço iniciado.')
            ->call('start')
            ->set('event_notes', 'Aguardando peça.')
            ->call('pause')
            ->set('event_notes', 'Peça recebida.')
            ->call('resume')
            ->set('event_notes', 'Serviço finalizado com sucesso.')
            ->call('complete')
            ->call('start')
            ->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Completed, $order->fresh()->status);

        $events = $order->executionEvents()->orderBy('id')->get();

        $this->assertCount(4, $events);
        $this->assertSame(
            [
                ServiceOrderExecutionEventType::Started,
                ServiceOrderExecutionEventType::Paused,
                ServiceOrderExecutionEventType::Resumed,
                ServiceOrderExecutionEventType::Completed,
            ],
            $events->map(fn ($event) => $event->type)->all()
        );
        $this->assertSame(['Serviço iniciado.', 'Aguardando peça.', 'Peça recebida.', 'Serviço finalizado com sucesso.'], $events->pluck('notes')->all());
        $this->assertTrue($events->every(fn ($event) => $event->user_id === $user->id));
    }

    public function test_cannot_pause_before_starting_execution(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('pause')
            ->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, $order->executionEvents()->count());
    }

    public function test_cannot_complete_from_pending(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('complete')
            ->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Pending, $order->fresh()->status);
    }

    public function test_cannot_resume_from_in_progress(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);
        $order->update(['status' => ServiceOrderStatus::InProgress]);

        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('resume')
            ->assertForbidden();

        $this->assertSame(ServiceOrderStatus::InProgress, $order->fresh()->status);
    }

    public function test_cannot_make_changes_after_completed(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);
        $order->update(['status' => ServiceOrderStatus::Completed]);

        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('start')
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('cancel')
            ->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Completed, $order->fresh()->status);
        $this->assertSame(0, $order->executionEvents()->count());
    }

    public function test_cannot_make_changes_after_cancelled(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);
        $order->update(['status' => ServiceOrderStatus::Cancelled]);

        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('start')
            ->assertForbidden();

        $this->assertSame(ServiceOrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_cancel_is_allowed_from_scheduled_and_paused(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();

        $scheduled = $this->orderFor($company, ['status' => ServiceOrderStatus::Scheduled]);
        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $scheduled])
            ->set('event_notes', 'Cliente desistiu.')
            ->call('cancel')
            ->assertOk();

        $this->assertSame(ServiceOrderStatus::Cancelled, $scheduled->fresh()->status);
        $this->assertSame(ServiceOrderExecutionEventType::Cancelled, $scheduled->executionEvents()->first()->type);
        $this->assertSame('Cliente desistiu.', $scheduled->executionEvents()->first()->notes);

        $paused = $this->orderFor($company, ['status' => ServiceOrderStatus::Paused]);
        Livewire::actingAs($user)
            ->test('manage-service-order-execution', ['order' => $paused])
            ->call('cancel')
            ->assertOk();

        $this->assertSame(ServiceOrderStatus::Cancelled, $paused->fresh()->status);
    }

    public function test_history_is_append_only_and_cannot_be_edited_or_deleted(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);
        $event = ServiceOrderExecutionEvent::factory()
            ->company($company)
            ->serviceOrder($order)
            ->user($user)
            ->type(ServiceOrderExecutionEventType::Started)
            ->create(['notes' => 'Nota original']);

        $this->actingAs($user);

        $this->assertFalse(Gate::allows('update', $event));
        $this->assertFalse(Gate::allows('delete', $event));

        $this->assertFalse($event->update(['notes' => 'Nota alterada']));
        $this->assertSame('Nota original', $event->fresh()->notes);

        $this->assertFalse($event->delete());
        $this->assertDatabaseHas('service_order_execution_events', ['id' => $event->id, 'notes' => 'Nota original']);
    }

    public function test_execution_events_are_isolated_between_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->company($companyA)->create();
        $adminB = User::factory()->admin()->company($companyB)->create();
        $orderA = $this->orderFor($companyA);
        $orderB = $this->orderFor($companyB);

        $eventA = ServiceOrderExecutionEvent::factory()
            ->company($companyA)
            ->serviceOrder($orderA)
            ->user($adminA)
            ->type(ServiceOrderExecutionEventType::Started)
            ->create();
        $eventB = ServiceOrderExecutionEvent::factory()
            ->company($companyB)
            ->serviceOrder($orderB)
            ->user($adminB)
            ->type(ServiceOrderExecutionEventType::Cancelled)
            ->create();

        $this->assertTrue(Gate::forUser($adminA)->allows('view', $eventA));
        $this->assertFalse(Gate::forUser($adminA)->allows('view', $eventB));

        $ids = ServiceOrderExecutionEvent::forCompany($companyA->id)->pluck('id')->all();

        $this->assertSame([$eventA->id], $ids);
    }

    public function test_technician_can_view_execution_history_on_the_order_page(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $order = ServiceOrder::factory()->company($company)->client($client)->create([
            'technician_id' => $technician->id,
            'status' => ServiceOrderStatus::Completed,
        ]);
        ServiceOrderExecutionEvent::factory()
            ->company($company)
            ->serviceOrder($order)
            ->user($technician)
            ->type(ServiceOrderExecutionEventType::Completed)
            ->create(['notes' => 'Serviço finalizado com sucesso.']);

        $this->actingAs($technician)
            ->get(route('service-orders.show', $order))
            ->assertOk()
            ->assertSee('Conclusão')
            ->assertSee('Serviço finalizado com sucesso.')
            ->assertSee($technician->name);
    }
}
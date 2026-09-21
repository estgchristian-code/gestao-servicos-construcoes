<?php

namespace Tests\Feature\Tenant;

use App\Enums\BudgetStatus;
use App\Enums\ServiceOrderHistoryType;
use App\Models\Budget;
use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderAttachment;
use App\Models\ServiceOrderHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(Company $company, array $attributes = []): ServiceOrder
    {
        $client = Client::factory()->company($company)->create();

        return ServiceOrder::factory()->company($company)->client($client)->create([
            'budget_id' => null,
            'scheduled_at' => null,
            ...$attributes,
        ]);
    }

    public function test_creating_a_service_order_records_history(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();

        Livewire::actingAs($admin)
            ->test('pages::service-orders.create')
            ->set('client_id', (string) $client->id)
            ->set('title', 'Instalação de ar-condicionado')
            ->call('save')
            ->assertHasNoErrors();

        $order = ServiceOrder::query()->first();

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::Created->value,
        ]);
    }

    public function test_converting_a_budget_records_creation_history(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $client = Client::factory()->company($company)->create();
        $budget = Budget::factory()->company($company)->client($client)->create([
            'status' => BudgetStatus::Approved,
        ]);

        Livewire::actingAs($admin)
            ->test('pages::budgets.show', ['budget' => $budget])
            ->call('convert')
            ->assertOk();

        $order = ServiceOrder::query()->first();

        $this->assertNotNull($order);
        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::Created->value,
        ]);
    }

    public function test_status_change_records_history(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company, ['scheduled_at' => '2026-10-01']);

        Livewire::actingAs($admin)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('status', 'cancelled')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::StatusChanged->value,
            'description' => 'Status alterado de Pendente para Cancelada.',
        ]);

        $this->assertSame(1, $order->histories()->count());
    }

    public function test_technician_change_records_history(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('technician_id', (string) $technician->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::TechnicianChanged->value,
            'description' => 'Técnico responsável alterado para ' . $technician->name . '.',
        ]);
    }

    public function test_schedule_change_records_history(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('pages::service-orders.edit', ['order' => $order])
            ->set('scheduled_at', '2026-10-15')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::ScheduledDateChanged->value,
            'description' => 'Data de agendamento alterada para 15/10/2026.',
        ]);
    }

    public function test_execution_start_records_history(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-execution', ['order' => $order])
            ->set('event_notes', 'Iniciando a execução.')
            ->call('start')
            ->assertOk();

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::ExecutionStarted->value,
            'description' => 'Execução iniciada.',
        ]);
    }

    public function test_execution_completion_records_history(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('start')
            ->assertOk();

        Livewire::actingAs($admin)
            ->test('manage-service-order-execution', ['order' => $order])
            ->call('complete')
            ->assertOk();

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::ExecutionCompleted->value,
            'description' => 'Execução concluída.',
        ]);
    }

    public function test_attachment_upload_records_history(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->image('foto-execucao.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::AttachmentAdded->value,
            'description' => 'Anexo "foto-execucao.jpg" adicionado.',
        ]);
    }

    public function test_attachment_deletion_records_history(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);
        $attachment = ServiceOrderAttachment::factory()
            ->company($company)->serviceOrder($order)->user($admin)->create([
                'name' => 'laudo.pdf',
                'mime_type' => 'application/pdf',
            ]);
        Storage::disk('local')->put($attachment->path, 'conteudo');

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->call('remove', $attachment->id)
            ->assertOk();

        $this->assertDatabaseMissing('service_order_attachments', ['id' => $attachment->id]);

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
            'user_id' => $admin->id,
            'type' => ServiceOrderHistoryType::AttachmentRemoved->value,
            'description' => 'Anexo "laudo.pdf" excluído.',
        ]);
    }

    public function test_company_isolation_of_histories(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        ServiceOrderHistory::factory()
            ->company($company)->serviceOrder($order)->user($user)->create([
                'description' => 'Ordem de serviço criada.',
            ]);

        $this->assertSame(1, ServiceOrderHistory::query()->forCompany($order->company_id)->count());

        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->admin()->company($otherCompany)->create();

        $this->assertSame(0, ServiceOrderHistory::query()->forCompany($otherUser)->count());
    }

    public function test_user_without_access_to_the_order_cannot_view_its_history(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->admin()->company($companyA)->create();
        $orderB = $this->orderFor($companyB);

        ServiceOrderHistory::factory()
            ->company($companyB)->serviceOrder($orderB)->create();

        Livewire::actingAs($userA)
            ->test('service-order-history', ['order' => $orderB])
            ->assertForbidden();
    }

    public function test_technician_assigned_to_the_order_can_view_its_history(): void
    {
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);

        ServiceOrderHistory::factory()
            ->company($company)->serviceOrder($order)->create([
                'description' => 'Ordem de serviço criada.',
            ]);

        Livewire::actingAs($technician)
            ->test('service-order-history', ['order' => $order])
            ->assertOk()
            ->assertSee('Ordem de serviço criada.');
    }

    public function test_history_is_read_only(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);
        $history = ServiceOrderHistory::factory()
            ->company($company)->serviceOrder($order)->user($admin)->create([
                'description' => 'Ordem de serviço criada.',
            ]);

        $this->assertFalse($history->update(['description' => 'manipulado']));
        $this->assertFalse($history->delete());

        $this->assertSame('Ordem de serviço criada.', $history->fresh()->description);
        $this->assertDatabaseHas('service_order_histories', ['id' => $history->id]);
    }

    public function test_histories_are_ordered_from_newest_to_oldest(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        $oldest = ServiceOrderHistory::factory()
            ->company($company)->serviceOrder($order)->user($admin)->create([
                'description' => 'Primeiro evento.',
                'created_at' => now()->subHours(3),
            ]);
        $middle = ServiceOrderHistory::factory()
            ->company($company)->serviceOrder($order)->user($admin)->create([
                'description' => 'Segundo evento.',
                'created_at' => now()->subHours(2),
            ]);
        $newest = ServiceOrderHistory::factory()
            ->company($company)->serviceOrder($order)->user($admin)->create([
                'description' => 'Terceiro evento.',
                'created_at' => now()->subHour(),
            ]);

        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            $order->histories()->pluck('id')->all()
        );
    }
}
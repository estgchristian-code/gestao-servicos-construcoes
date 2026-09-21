<?php

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\Company;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(Company $company, array $attributes = []): ServiceOrder
    {
        $client = Client::factory()->company($company)->create();

        return ServiceOrder::factory()->company($company)->client($client)->create([
            ...$attributes,
        ]);
    }

    public function test_image_upload_is_linked_to_the_service_order(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->image('foto-servico.jpg', 100, 100))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_attachments', [
            'service_order_id' => $order->id,
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'name' => 'foto-servico.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $attachment = $order->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertGreaterThan(0, $attachment->size);
        $this->assertStringStartsWith('attachments/', $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertTrue($order->attachments->contains($attachment));
    }

    public function test_pdf_upload_is_accepted(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->create('laudo.pdf', 2048, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_attachments', [
            'service_order_id' => $order->id,
            'name' => 'laudo.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_disallowed_file_type_is_rejected(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->create('malicious.txt', 1024, 'text/plain'))
            ->call('save')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('service_order_attachments', 0);
    }

    public function test_file_larger_than_10_mb_is_rejected(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->create('foto-grande.jpg', 11 * 1024, 'image/jpeg'))
            ->call('save')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('service_order_attachments', 0);
    }

    public function test_assigned_technician_can_upload(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);

        Livewire::actingAs($technician)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->image('execucao.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_attachments', [
            'service_order_id' => $order->id,
            'user_id' => $technician->id,
        ]);
    }

    public function test_unassigned_technician_cannot_upload(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $otherTechnician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $otherTechnician->id]);

        Livewire::actingAs($technician)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->image('nao-deve-subir.jpg'))
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('service_order_attachments', 0);
    }

    public function test_technician_can_only_delete_their_own_attachments(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $otherTechnician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);

        $own = ServiceOrderAttachment::factory()
            ->company($company)->serviceOrder($order)->user($technician)->create();
        $others = ServiceOrderAttachment::factory()
            ->company($company)->serviceOrder($order)->user($otherTechnician)->create();

        Livewire::actingAs($technician)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->call('remove', $others->id)
            ->assertForbidden();

        $this->assertDatabaseHas('service_order_attachments', ['id' => $others->id]);

        Livewire::actingAs($technician)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->call('remove', $own->id)
            ->assertOk();

        $this->assertDatabaseMissing('service_order_attachments', ['id' => $own->id]);
    }

    public function test_admin_can_manage_any_attachment(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);

        $attachment = ServiceOrderAttachment::factory()
            ->company($company)->serviceOrder($order)->user($technician)->create();

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->call('remove', $attachment->id)
            ->assertOk();

        $this->assertDatabaseMissing('service_order_attachments', ['id' => $attachment->id]);
    }

    public function test_comercial_can_upload_and_delete(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $comercial = User::factory()->comercial()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);
        $existing = ServiceOrderAttachment::factory()
            ->company($company)->serviceOrder($order)->user($comercial)->create();

        Livewire::actingAs($comercial)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->image('comercial.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('service_order_attachments', [
            'service_order_id' => $order->id,
            'user_id' => $comercial->id,
        ]);

        Livewire::actingAs($comercial)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->call('remove', $existing->id)
            ->assertOk();

        $this->assertDatabaseMissing('service_order_attachments', ['id' => $existing->id]);
    }

    public function test_deleting_attachment_removes_the_physical_file(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $order = $this->orderFor($company);

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->set('file', UploadedFile::fake()->image('foto.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $attachment = $order->attachments()->first();
        Storage::disk('local')->assertExists($attachment->path);

        Livewire::actingAs($admin)
            ->test('manage-service-order-attachments', ['order' => $order])
            ->call('remove', $attachment->id)
            ->assertOk();

        Storage::disk('local')->assertMissing($attachment->path);
        $this->assertDatabaseMissing('service_order_attachments', ['id' => $attachment->id]);
    }

    public function test_other_company_attachment_is_isolated(): void
    {
        Storage::fake('local');
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $adminA = User::factory()->admin()->company($companyA)->create();
        $adminB = User::factory()->admin()->company($companyB)->create();
        $orderB = $this->orderFor($companyB);

        $attachment = ServiceOrderAttachment::factory()
            ->company($companyB)->serviceOrder($orderB)->user($adminB)->create();
        Storage::disk('local')->put($attachment->path, 'conteudo');

        Livewire::actingAs($adminA)
            ->test('manage-service-order-attachments', ['order' => $orderB])
            ->assertForbidden();

        $this->actingAs($adminA)
            ->get(route('service-orders.attachments.show', $attachment))
            ->assertNotFound();
    }

    public function test_download_respects_authorization(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $otherTechnician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);

        $attachment = ServiceOrderAttachment::factory()
            ->company($company)->serviceOrder($order)->user($technician)->asPdf()->create([
                'name' => 'laudo.pdf',
            ]);
        Storage::disk('local')->put($attachment->path, 'conteudo pdf');

        $this->actingAs($technician)
            ->get(route('service-orders.attachments.show', $attachment))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($technician)
            ->get(route('service-orders.attachments.show', ['attachment' => $attachment, 'download' => 1]))
            ->assertOk()
            ->assertDownload('laudo.pdf');

        $this->actingAs($otherTechnician)
            ->get(route('service-orders.attachments.show', $attachment))
            ->assertForbidden();
    }

    public function test_admin_can_view_and_download_image_attachment(): void
    {
        Storage::fake('local');
        $company = Company::factory()->create();
        $admin = User::factory()->admin()->company($company)->create();
        $technician = User::factory()->tecnico()->company($company)->create();
        $order = $this->orderFor($company, ['technician_id' => $technician->id]);

        $attachment = ServiceOrderAttachment::factory()
            ->company($company)->serviceOrder($order)->user($technician)->create([
                'name' => 'foto.jpg',
                'mime_type' => 'image/jpeg',
            ]);
        Storage::disk('local')->put($attachment->path, 'conteudo');

        $this->actingAs($admin)
            ->get(route('service-orders.attachments.show', $attachment))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }
}
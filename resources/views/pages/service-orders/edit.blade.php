<?php

use App\Enums\BudgetStatus;
use App\Enums\ServiceOrderHistoryType;
use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\Budget;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Support\BudgetOrderLinker;
use App\Support\ServiceOrderHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ServiceOrder $order;

    public string $client_id = '';

    public string $client_address_id = '';

    public string $budget_id = '';

    public string $technician_id = '';

    public string $number = '';

    public string $title = '';

    public string $status = 'pending';

    public string $scheduled_at = '';

    public string $notes = '';

    public function mount(ServiceOrder $order): void
    {
        $this->order = $order;

        $this->authorize('update', $order);

        $this->client_id = (string) $order->client_id;
        $this->client_address_id = (string) ($order->client_address_id ?? '');
        $this->budget_id = (string) ($order->budget_id ?? '');
        $this->technician_id = (string) ($order->technician_id ?? '');
        $this->number = $order->number;
        $this->title = $order->title;
        $this->status = $order->status->value;
        $this->scheduled_at = $order->scheduled_at?->format('Y-m-d') ?? '';
        $this->notes = $order->notes ?? '';
    }

    #[Computed]
    public function clients()
    {
        return Client::query()
            ->forCompany(auth()->user())
            ->where('clients.active', true)
            ->orderBy('clients.name')
            ->get();
    }

    #[Computed]
    public function addresses()
    {
        if ($this->client_id === '') {
            return collect();
        }

        return ClientAddress::query()
            ->where('client_addresses.client_id', $this->client_id)
            ->orderByDesc('client_addresses.main')
            ->orderBy('client_addresses.created_at')
            ->get();
    }

    #[Computed]
    public function budgets()
    {
        return Budget::query()
            ->forCompany(auth()->user())
            ->when($this->client_id !== '', function ($query) {
                $query->where('budgets.client_id', $this->client_id);
            })
            ->where(function ($query) {
                $query->where('budgets.status', BudgetStatus::Approved->value)
                    ->whereNull('budgets.service_order_id');

                if ($this->order->budget_id !== null) {
                    $query->orWhere('budgets.id', $this->order->budget_id);
                }
            })
            ->orderByDesc('budgets.created_at')
            ->get();
    }

    #[Computed]
    public function technicians()
    {
        return User::query()
            ->where('users.company_id', $this->order->company_id)
            ->where('users.active', true)
            ->whereHas('roles', fn ($q) => $q->where('roles.slug', UserRole::Tecnico->value))
            ->orderBy('users.name')
            ->get();
    }

    public function updatedClientId(): void
    {
        $this->budget_id = '';
        $this->client_address_id = '';
    }

    public function save()
    {
        $this->authorize('update', $this->order);

        $this->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('active', true),
            ],
            'budget_id' => [
                'nullable',
                Rule::exists('budgets', 'id')
                    ->where('company_id', auth()->user()->company_id)
                    ->when($this->client_id !== '', fn ($rule) => $rule->where('budgets.client_id', $this->client_id))
                    ->where(function ($rule) {
                        $rule->where('budgets.status', BudgetStatus::Approved->value)
                            ->whereNull('budgets.service_order_id')
                            ->when($this->order->budget_id !== null, fn ($rule) => $rule->orWhere('budgets.id', $this->order->budget_id));
                    }),
            ],
            'client_address_id' => [
                'nullable',
                function (string $attribute, $value, $fail) {
                    if ($value === '' || $value === null) {
                        return;
                    }
                    $valid = ClientAddress::query()
                        ->where('client_addresses.id', $value)
                        ->where('client_addresses.client_id', $this->client_id)
                        ->whereHas('client', fn ($q) => $q->where('clients.company_id', auth()->user()->company_id))
                        ->exists();
                    if (! $valid) {
                        $fail('O endereço selecionado é inválido.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:255'],
            'status' => [Rule::enum(ServiceOrderStatus::class)],
            'technician_id' => [
                'nullable',
                function (string $attribute, $value, $fail) {
                    if ($value === '') {
                        return;
                    }
                    $valid = User::query()
                        ->where('users.company_id', $this->order->company_id)
                        ->where('users.active', true)
                        ->where('users.id', $value)
                        ->whereHas('roles', fn ($q) => $q->where('roles.slug', UserRole::Tecnico->value))
                        ->exists();
                    if (! $valid) {
                        $fail('O técnico selecionado é inválido.');
                    }
                },
            ],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'client_id.required' => 'Selecione o cliente.',
            'client_id.exists' => 'O cliente selecionado é inválido.',
            'budget_id.exists' => 'O orçamento selecionado é inválido.',
            'title.required' => 'Informe o título da ordem de serviço.',
        ]);

        $currentStatus = $this->order->status;
        $currentTechnicianId = $this->order->technician_id !== null ? (int) $this->order->technician_id : null;
        $currentScheduledAt = $this->order->scheduled_at?->format('Y-m-d');

        $orderId = $this->order->id;

        DB::transaction(function () use ($orderId) {
            $lockedOrder = ServiceOrder::query()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $budget = $this->budget_id !== ''
                ? BudgetOrderLinker::lockBudgetOrFail((int) $this->budget_id)
                : null;

            BudgetOrderLinker::assertLinkable(
                $budget,
                auth()->user()->company_id,
                (int) $this->client_id,
                $orderId,
                $lockedOrder->budget_id
            );

            $currentBudgetId = $lockedOrder->budget_id;

            if ($currentBudgetId !== null && $currentBudgetId !== $budget?->id) {
                BudgetOrderLinker::releaseFromOrder($currentBudgetId, $orderId);
            }

            $lockedOrder->update([
                'client_id' => $this->client_id,
                'client_address_id' => $this->client_address_id !== '' ? $this->client_address_id : null,
                'budget_id' => $budget?->id,
                'technician_id' => $this->technician_id !== '' ? $this->technician_id : null,
                'title' => trim($this->title),
                'status' => $this->status,
                'scheduled_at' => $this->scheduled_at !== '' ? $this->scheduled_at : null,
                'notes' => $this->notes !== '' ? $this->notes : null,
            ]);

            if ($budget !== null) {
                BudgetOrderLinker::attach($lockedOrder, $budget);
            }

            $this->order = $lockedOrder;
        });

        if ((string) $this->status !== $currentStatus->value) {
            ServiceOrderHistoryRecorder::record(
                $this->order,
                ServiceOrderHistoryType::StatusChanged,
                'Status alterado de ' . $currentStatus->label() . ' para ' . ServiceOrderStatus::from($this->status)->label() . '.'
            );
        }

        $newTechnicianId = $this->technician_id !== '' ? (int) $this->technician_id : null;

        if ($newTechnicianId !== $currentTechnicianId) {
            $technicianName = $newTechnicianId !== null
                ? User::query()->whereKey($newTechnicianId)->value('name')
                : null;

            ServiceOrderHistoryRecorder::record(
                $this->order,
                ServiceOrderHistoryType::TechnicianChanged,
                $technicianName !== null
                    ? 'Técnico responsável alterado para ' . $technicianName . '.'
                    : 'Técnico responsável removido.'
            );
        }

        $newScheduledAt = $this->scheduled_at !== '' ? $this->scheduled_at : null;

        if ($newScheduledAt !== $currentScheduledAt) {
            ServiceOrderHistoryRecorder::record(
                $this->order,
                ServiceOrderHistoryType::ScheduledDateChanged,
                $newScheduledAt !== null
                    ? 'Data de agendamento alterada para ' . date('d/m/Y', strtotime($newScheduledAt)) . '.'
                    : 'Data de agendamento removida.'
            );
        }

        session()->flash('status', 'Ordem de serviço atualizada com sucesso.');

        return redirect()->route('service-orders.show', $this->order);
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('service-orders.show', $this->order) }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para a OS
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Editar ordem de serviço</h1>
        <p class="mt-1 text-sm text-slate-500">OS #{{ $this->order->number }}</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.service-order-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('service-orders.show', $this->order) }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Salvar alterações
            </button>
        </div>
    </form>
</div>
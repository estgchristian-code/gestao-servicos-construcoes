<?php

use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\Budget;
use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $client_id = '';

    public string $budget_id = '';

    public string $technician_id = '';

    public string $number = '';

    public string $title = '';

    public string $status = 'pending';

    public string $scheduled_at = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->number = $this->generateNumber();
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
    public function budgets()
    {
        return Budget::query()
            ->forCompany(auth()->user())
            ->when($this->client_id !== '', function ($query) {
                $query->where('budgets.client_id', $this->client_id);
            })
            ->orderByDesc('budgets.created_at')
            ->get();
    }

    #[Computed]
    public function technicians()
    {
        return User::query()
            ->where('users.company_id', auth()->user()->company_id)
            ->where('users.active', true)
            ->whereHas('roles', fn ($q) => $q->where('roles.slug', UserRole::Tecnico->value))
            ->orderBy('users.name')
            ->get();
    }

    public function updatedClientId(): void
    {
        $this->budget_id = '';
    }

    public function save()
    {
        $this->authorize('create', ServiceOrder::class);

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
                    ->when($this->client_id !== '', fn ($rule) => $rule->where('budgets.client_id', $this->client_id)),
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
                        ->where('users.company_id', auth()->user()->company_id)
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

        $order = new ServiceOrder([
            'client_id' => $this->client_id,
            'budget_id' => $this->budget_id !== '' ? $this->budget_id : null,
            'technician_id' => $this->technician_id !== '' ? $this->technician_id : null,
            'number' => $this->generateNumber(),
            'title' => trim($this->title),
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at !== '' ? $this->scheduled_at : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ]);
        $order->company_id = auth()->user()->company_id;
        $order->save();

        session()->flash('status', 'Ordem de serviço cadastrada com sucesso.');

        return redirect()->route('service-orders.show', $order);
    }

    private function generateNumber(): string
    {
        $max = ServiceOrder::query()->forCompany(auth()->user())->max('number');

        $next = $max !== null ? ((int) $max) + 1 : 1;

        return str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('service-orders.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para ordens de serviço
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Nova ordem de serviço</h1>
        <p class="mt-1 text-sm text-slate-500">Crie uma nova ordem de serviço para um cliente.</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.service-order-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('service-orders.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Criar ordem de serviço
            </button>
        </div>
    </form>
</div>
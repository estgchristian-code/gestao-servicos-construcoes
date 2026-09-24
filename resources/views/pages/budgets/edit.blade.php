<?php

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Budget $budget;

    public string $client_id = '';

    public string $number = '';

    public string $title = '';

    public string $status = 'draft';

    public string $total = '';

    public string $valid_until = '';

    public string $notes = '';

    public function mount(Budget $budget): void
    {
        $this->budget = $budget;

        $this->authorize('update', $budget);

        $this->client_id = (string) $budget->client_id;
        $this->number = $budget->number;
        $this->title = $budget->title;
        $this->status = $budget->status->value;
        $this->total = number_format((float) $budget->total, 2, ',', '');
        $this->valid_until = $budget->valid_until?->format('Y-m-d') ?? '';
        $this->notes = $budget->notes ?? '';
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

    public function save()
    {
        $this->authorize('update', $this->budget);

        $this->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'status' => [Rule::enum(BudgetStatus::class)],
            'total' => ['required', 'regex:/^\d{1,10}([.,]\d{1,2})?$/', 'max:14'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'client_id.required' => 'Selecione o cliente.',
            'client_id.exists' => 'O cliente selecionado é inválido.',
            'title.required' => 'Informe o título do orçamento.',
            'total.required' => 'Informe o valor total.',
            'total.regex' => 'Informe um valor válido (ex.: 1500 ou 1500,50).',
        ]);

        $newStatus = BudgetStatus::tryFrom($this->status);

        DB::transaction(function () use ($newStatus) {
            $current = Budget::query()
                ->whereKey($this->budget->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $current);

            if ($newStatus === null || ! $current->status->canTransitionTo($newStatus)) {
                abort(403, 'Transição de status não permitida.');
            }

            $current->update([
                'client_id' => $this->client_id,
                'title' => trim($this->title),
                'status' => $newStatus,
                'total' => $this->normalizeTotal($this->total),
                'valid_until' => $this->valid_until !== '' ? $this->valid_until : null,
                'notes' => $this->notes !== '' ? $this->notes : null,
            ]);
        });

        session()->flash('status', 'Orçamento atualizado com sucesso.');

        return redirect()->route('budgets.show', $this->budget);
    }

    #[Computed]
    public function statusOptions()
    {
        return $this->budget->status->transitionOptions();
    }

    private function normalizeTotal(string $value): string
    {
        return str_replace(',', '.', trim($value));
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('budgets.show', $this->budget) }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para o orçamento
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Editar orçamento</h1>
        <p class="mt-1 text-sm text-slate-500">Orçamento #{{ $this->budget->number }}</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.budget-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('budgets.show', $this->budget) }}"
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
<?php

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Client;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $client_id = '';

    public string $number = '';

    public string $title = '';

    public string $status = 'draft';

    public string $total = '';

    public string $valid_until = '';

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

    public function save()
    {
        $this->authorize('create', Budget::class);

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

        $budget = new Budget([
            'client_id' => $this->client_id,
            'number' => $this->generateNumber(),
            'title' => trim($this->title),
            'status' => $this->status,
            'total' => $this->normalizeTotal($this->total),
            'valid_until' => $this->valid_until !== '' ? $this->valid_until : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ]);
        $budget->company_id = auth()->user()->company_id;
        $budget->save();

        session()->flash('status', 'Orçamento cadastrado com sucesso.');

        return redirect()->route('budgets.show', $budget);
    }

    private function generateNumber(): string
    {
        $max = Budget::query()->forCompany(auth()->user())->max('number');

        $next = $max !== null ? ((int) $max) + 1 : 1;

        return str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function normalizeTotal(string $value): string
    {
        return str_replace(',', '.', trim($value));
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('budgets.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para orçamentos
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Novo orçamento</h1>
        <p class="mt-1 text-sm text-slate-500">Crie um novo orçamento para envio ao cliente.</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.budget-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('budgets.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Criar orçamento
            </button>
        </div>
    </form>
</div>
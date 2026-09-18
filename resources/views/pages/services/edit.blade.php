<?php

use App\Models\Service;
use Livewire\Component;

new class extends Component {
    public Service $service;

    public string $name = '';

    public string $description = '';

    public bool $active = true;

    public function mount(Service $service): void
    {
        $this->service = $service;

        $this->authorize('update', $service);

        $this->name = $service->name;
        $this->description = $service->description ?? '';
        $this->active = $service->active;
    }

    public function save()
    {
        $this->authorize('update', $this->service);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'active' => ['boolean'],
        ], [
            'name.required' => 'Informe o nome do serviço.',
        ]);

        $this->service->update([
            'name' => trim($this->name),
            'description' => $this->description !== '' ? $this->description : null,
            'active' => $this->active,
        ]);

        session()->flash('status', 'Serviço atualizado com sucesso.');

        return redirect()->route('services.show', $this->service);
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('services.show', $this->service) }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para o serviço
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Editar serviço</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $this->service->name }}</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.service-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('services.show', $this->service) }}"
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
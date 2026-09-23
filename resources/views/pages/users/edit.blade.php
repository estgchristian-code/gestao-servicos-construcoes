<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $active = true;

    public bool $editing = true;

    public function mount(User $user): void
    {
        $this->user = $user;

        $this->authorize('update', $user);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->active = $user->active;
    }

    public function save()
    {
        $this->authorize('update', $this->user);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->getKey())],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'active' => ['boolean'],
        ], [
            'name.required' => 'Informe o nome do técnico.',
            'email.required' => 'Informe o e-mail do técnico.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe um usuário cadastrado com este e-mail.',
            'password.min' => 'A nova senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $this->user->update([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'active' => $this->active,
            ...($this->password !== '' ? ['password' => $this->password] : []),
        ]);

        session()->flash('status', 'Técnico atualizado com sucesso.');

        return redirect()->route('users.index');
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para técnicos
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Editar técnico</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $this->user->name }}</p>
    </div>

    <form wire:submit="save" class="max-w-4xl">
        @include('partials.user-form')

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
            <a href="{{ route('users.index') }}"
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
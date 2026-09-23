<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $active = true;

    public bool $editing = false;

    public function save()
    {
        $this->authorize('create', User::class);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'active' => ['boolean'],
        ], [
            'name.required' => 'Informe o nome do técnico.',
            'email.required' => 'Informe o e-mail do técnico.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe um usuário cadastrado com este e-mail.',
            'password.required' => 'Informe uma senha.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $technician = new User([
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'password' => $this->password,
            'active' => $this->active,
            'is_superadmin' => false,
        ]);
        $technician->company_id = auth()->user()->company_id;
        $technician->save();

        $role = Role::query()->firstOrCreate(
            ['slug' => UserRole::Tecnico->value],
            ['name' => UserRole::Tecnico->label()],
        );
        $technician->roles()->sync([$role->id]);

        session()->flash('status', 'Técnico cadastrado com sucesso.');

        return redirect()->route('users.index');
    }

    public function resetForm(): void
    {
        $this->reset('name', 'email', 'password', 'password_confirmation');
        $this->active = true;
        $this->resetValidation();
    }
};
?>
<div>
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-700">
            ← Voltar para técnicos
        </a>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Novo técnico</h1>
        <p class="mt-1 text-sm text-slate-500">Cadastre um técnico da sua empresa.</p>
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
                Cadastrar técnico
            </button>
        </div>
    </form>
</div>
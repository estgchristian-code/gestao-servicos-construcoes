<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create
        {name : Nome da empresa (tenant)}
        {email : E-mail do primeiro administrador}
        {--password= : Senha do administrador (se omitida, será solicitada)}
        {--admin-name= : Nome do administrador (padrão: "Administrador")}
        {--slug= : Slug da empresa (opcional, gerado do nome se omitido)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria uma empresa (tenant) e seu primeiro usuário administrador';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $email = strtolower(trim((string) $this->argument('email')));
        $adminName = trim($this->option('admin-name') ?? '') ?: 'Administrador';

        $password = $this->resolvePassword();

        if ($password === null) {
            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'slug' => $this->option('slug'),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:companies,slug'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $slug = $this->option('slug') ?? $this->uniqueSlug($name);

        try {
            $company = DB::transaction(function () use ($name, $slug, $email, $adminName, $password) {
                $company = Company::create([
                    'name' => $name,
                    'slug' => $slug,
                    'email' => $email,
                    'status' => 'active',
                    'plan' => 'basic',
                ]);

                $adminRole = Role::query()->firstOrCreate(
                    ['slug' => UserRole::Admin->value],
                    ['name' => UserRole::Admin->label(), 'description' => UserRole::Admin->description()],
                );

                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $adminName,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'is_superadmin' => false,
                    'active' => true,
                ]);

                $user->roles()->attach($adminRole->id);

                return $company;
            });
        } catch (\Throwable $e) {
            $this->error('Não foi possível criar a empresa: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Empresa criada com sucesso.');
        $this->newLine();
        $this->table(['Campo', 'Valor'], [
            ['Empresa', $company->name],
            ['Slug', $company->slug],
            ['E-mail do administrador', $email],
            ['Perfil', UserRole::Admin->label()],
        ]);

        return self::SUCCESS;
    }

    /**
     * Resolve the password, prompting for it when not provided.
     */
    protected function resolvePassword(): ?string
    {
        $password = (string) $this->option('password');

        if ($password !== '') {
            return $password;
        }

        $password = $this->secret('Senha do administrador (mínimo 8 caracteres)');
        $confirmation = $this->secret('Confirme a senha');

        if ($password !== $confirmation) {
            $this->error('As senhas não conferem.');

            return null;
        }

        return $password;
    }

    /**
     * Generate a unique slug based on the company name.
     */
    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-');
        $slug = $base;
        $suffix = 2;

        while (Company::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}

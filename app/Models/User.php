<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'company_id', 'is_superadmin', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'company_id' => 'integer',
            'is_superadmin' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * The company (tenant) this user belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The profiles/roles assigned to this user.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Platform-level administrator, not limited to a single company.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Whether the account is active and allowed to authenticate.
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /**
     * Whether this user belongs to the given company.
     */
    public function belongsToCompany(?Company $company): bool
    {
        return $company !== null && $this->company_id !== null && (int) $this->company_id === $company->id;
    }

    /**
     * Whether the user has the given role.
     */
    public function hasRole(UserRole|string $role): bool
    {
        $slug = $role instanceof UserRole ? $role->value : $role;

        return $this->roles()->where('roles.slug', $slug)->exists();
    }

    /**
     * The primary role assigned to the user (first one).
     */
    public function primaryRole(): ?Role
    {
        return $this->roles()->orderBy('roles.id')->first();
    }

    /**
     * Slug of the primary role.
     */
    public function roleSlug(): ?string
    {
        return $this->primaryRole()?->slug;
    }

    /**
     * Display label of the primary role.
     */
    public function roleLabel(): ?string
    {
        return $this->primaryRole()?->name;
    }

    /**
     * Convenience checks for the fixed profiles.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isComercial(): bool
    {
        return $this->hasRole(UserRole::Comercial);
    }

    public function isTecnico(): bool
    {
        return $this->hasRole(UserRole::Tecnico);
    }
}

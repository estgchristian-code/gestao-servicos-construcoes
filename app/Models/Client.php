<?php

namespace App\Models;

use App\Enums\ClientType;
use App\Support\Documents;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['type', 'name', 'document', 'email', 'phone', 'whatsapp', 'notes', 'tags', 'active'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ClientType::class,
            'tags' => 'array',
            'active' => 'boolean',
        ];
    }

    /**
     * The company (tenant) this client belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The addresses registered for this client.
     *
     * @return HasMany<ClientAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(ClientAddress::class);
    }

    /**
     * Scope the query to a single company/tenant.
     *
     * @param  Builder<Client>  $query
     * @param  int|User  $company  Company id or an authenticated-style user.
     */
    public function scopeForCompany(Builder $query, int|User $company): Builder
    {
        $companyId = $company instanceof User ? $company->company_id : $company;

        return $query->where('clients.company_id', $companyId);
    }

    /**
     * Documents are always stored as bare digits so that the company-scoped
     * uniqueness check is reliable regardless of how they were typed.
     */
    public function setDocumentAttribute(?string $value): void
    {
        $this->attributes['document'] = Documents::normalize($value);
    }

    /**
     * Route model binding always resolves within the authenticated user's own
     * company. Clients from other tenants are treated as non-existent (404).
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $query = parent::resolveRouteBindingQuery($query, $value, $field);

        $user = auth()->user();

        if ($user !== null && ! $user->isSuperAdmin() && $user->company_id !== null) {
            $query->where('clients.company_id', $user->company_id);
        }

        return $query;
    }
}

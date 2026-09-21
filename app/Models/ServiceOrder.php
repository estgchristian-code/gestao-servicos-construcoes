<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use Database\Factories\ServiceOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['budget_id', 'client_id', 'client_address_id', 'technician_id', 'number', 'title', 'status', 'scheduled_at', 'notes', 'total'])]
class ServiceOrder extends Model
{
    /** @use HasFactory<ServiceOrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ServiceOrderStatus::class,
            'scheduled_at' => 'date',
            'total' => 'decimal:2',
        ];
    }

    /**
     * The company (tenant) this service order belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The budget this service order was originated from (optional).
     *
     * @return BelongsTo<Budget, $this>
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * The client this service order is addressed to.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The client address where this service order will be executed (optional).
     *
     * @return BelongsTo<ClientAddress, $this>
     */
    public function clientAddress(): BelongsTo
    {
        return $this->belongsTo(ClientAddress::class);
    }

    /**
     * The items that compose this service order.
     *
     * @return HasMany<ServiceOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    /**
     * The technician (user with role "tecnico") assigned to this service order.
     *
     * @return BelongsTo<User, $this>
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * The append-only execution history of this service order.
     *
     * @return HasMany<ServiceOrderExecutionEvent, $this>
     */
    public function executionEvents(): HasMany
    {
        return $this->hasMany(ServiceOrderExecutionEvent::class)->orderBy('id');
    }

    /**
     * The ordered timeline of actions performed on this service order.
     *
     * @return HasMany<ServiceOrderHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(ServiceOrderHistory::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * The photos and files attached to this service order.
     *
     * @return HasMany<ServiceOrderAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ServiceOrderAttachment::class)->orderByDesc('created_at');
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $query = parent::resolveRouteBindingQuery($query, $value, $field);

        $user = auth()->user();

        if ($user !== null && ! $user->isSuperAdmin() && $user->company_id !== null) {
            $query->where('service_orders.company_id', $user->company_id);
        }

        return $query;
    }

    /**
     * Scope the query to a single company/tenant.
     *
     * @param  Builder<ServiceOrder>  $query
     * @param  int|User  $company  Company id or an authenticated-style user.
     */
    public function scopeForCompany(Builder $query, int|User $company): Builder
    {
        $companyId = $company instanceof User ? $company->company_id : $company;

        return $query->where('service_orders.company_id', $companyId);
    }
}
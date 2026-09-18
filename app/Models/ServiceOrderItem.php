<?php

namespace App\Models;

use Database\Factories\ServiceOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_order_id', 'service_id', 'description', 'quantity', 'unit_price', 'subtotal'])]
class ServiceOrderItem extends Model
{
    /** @use HasFactory<ServiceOrderItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * The company (tenant) this item belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The service order this item belongs to.
     *
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /**
     * The service this item references (kept even if the service is deleted).
     *
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Scope the query to a single company/tenant.
     *
     * @param  Builder<ServiceOrderItem>  $query
     * @param  int|User  $company  Company id or an authenticated-style user.
     */
    public function scopeForCompany(Builder $query, int|User $company): Builder
    {
        $companyId = $company instanceof User ? $company->company_id : $company;

        return $query->where('service_order_items.company_id', $companyId);
    }
}
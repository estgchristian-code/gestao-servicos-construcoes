<?php

namespace App\Models;

use App\Enums\ServiceOrderHistoryType;
use Database\Factories\ServiceOrderHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'description'])]
class ServiceOrderHistory extends Model
{
    /** @use HasFactory<ServiceOrderHistoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ServiceOrderHistoryType::class,
        ];
    }

    /**
     * The service order history is strictly read-only: events can never be
     * edited or deleted manually once recorded.
     */
    protected static function booted(): void
    {
        static::updating(function () {
            return false;
        });

        static::deleting(function () {
            return false;
        });
    }

    /**
     * The company (tenant) this history event belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The service order this history event belongs to.
     *
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /**
     * The user responsible for the action that generated this history event.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope the query to a single company/tenant.
     *
     * @param  Builder<ServiceOrderHistory>  $query
     * @param  int|User  $company  Company id or an authenticated-style user.
     */
    public function scopeForCompany(Builder $query, int|User $company): Builder
    {
        $companyId = $company instanceof User ? $company->company_id : $company;

        return $query->where('service_order_histories.company_id', $companyId);
    }
}
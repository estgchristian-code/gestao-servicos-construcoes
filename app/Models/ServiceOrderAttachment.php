<?php

namespace App\Models;

use Database\Factories\ServiceOrderAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_order_id', 'user_id', 'name', 'path', 'mime_type', 'size'])]
class ServiceOrderAttachment extends Model
{
    /** @use HasFactory<ServiceOrderAttachmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * The company (tenant) this attachment belongs to.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The service order this attachment belongs to.
     *
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /**
     * The user who uploaded this attachment.
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
     * @param  Builder<ServiceOrderAttachment>  $query
     * @param  int|User  $company  Company id or an authenticated-style user.
     */
    public function scopeForCompany(Builder $query, int|User $company): Builder
    {
        $companyId = $company instanceof User ? $company->company_id : $company;

        return $query->where('service_order_attachments.company_id', $companyId);
    }

    /**
     * Route model binding always resolves within the authenticated user's own
     * company. Attachments from other tenants are treated as non-existent (404).
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $query = parent::resolveRouteBindingQuery($query, $value, $field);

        $user = auth()->user();

        if ($user !== null && ! $user->isSuperAdmin() && $user->company_id !== null) {
            $query->where('service_order_attachments.company_id', $user->company_id);
        }

        return $query;
    }
}
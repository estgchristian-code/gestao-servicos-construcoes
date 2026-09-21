<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderAttachment;
use App\Models\ServiceOrderExecutionEvent;
use App\Models\ServiceOrderItem;
use App\Models\User;
use App\Policies\BudgetItemPolicy;
use App\Policies\BudgetPolicy;
use App\Policies\ClientAddressPolicy;
use App\Policies\ClientPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ServiceOrderExecutionEventPolicy;
use App\Policies\ServiceOrderAttachmentPolicy;
use App\Policies\ServiceOrderItemPolicy;
use App\Policies\ServiceOrderPolicy;
use App\Policies\ServicePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(ClientAddress::class, ClientAddressPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Budget::class, BudgetPolicy::class);
        Gate::policy(BudgetItem::class, BudgetItemPolicy::class);
        Gate::policy(ServiceOrder::class, ServiceOrderPolicy::class);
        Gate::policy(ServiceOrderItem::class, ServiceOrderItemPolicy::class);
        Gate::policy(ServiceOrderExecutionEvent::class, ServiceOrderExecutionEventPolicy::class);
        Gate::policy(ServiceOrderAttachment::class, ServiceOrderAttachmentPolicy::class);
    }
}

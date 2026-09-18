<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\ClientAddress;
use App\Models\Company;
use App\Models\User;
use App\Policies\ClientAddressPolicy;
use App\Policies\ClientPolicy;
use App\Policies\CompanyPolicy;
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
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Policies\MenuItemPolicy;
use App\Policies\OrderPolicy;
use App\Policies\RestaurantPolicy;
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
        // Register policies manually (Laravel 12 auto-discovers, but explicit registration is clearer)
        Gate::policy(Restaurant::class, RestaurantPolicy::class);
        Gate::policy(MenuItem::class, MenuItemPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
    }
}

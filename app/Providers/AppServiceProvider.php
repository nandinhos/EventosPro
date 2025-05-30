<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Gig;
use App\Models\GigCost;
use App\Observers\GigObserver;
use App\Observers\GigCostObserver;

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
        // Registra os observers
        Gig::observe(GigObserver::class);
        GigCost::observe(GigCostObserver::class);
        \Illuminate\Support\Facades\Blade::component('reports.components.tab-nav', 'tab-nav');
        \Illuminate\Support\Facades\Blade::component('reports.components.chart', 'chart');
    }
}

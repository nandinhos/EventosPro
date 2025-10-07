<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use App\Models\Gig;
use App\Models\GigCost;
use App\Observers\GigCostObserver;
use App\Observers\GigObserver;
use App\Policies\GigPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Gig::class => GigPolicy::class,
    ];

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

        // Registra os componentes Blade
        Blade::component('reports.components.tab-nav', 'tab-nav');
        Blade::component('reports.components.chart', 'chart');
        Blade::component('reports.components.slot-button', 'slot-button');
        Blade::component('components.status-dot', 'status-dot');
    }
}

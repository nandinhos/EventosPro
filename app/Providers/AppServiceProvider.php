<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Gig;
use App\Models\GigCost;
use App\Observers\GigObserver;
use App\Observers\GigCostObserver;
use App\Policies\GigPolicy;

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
        \Illuminate\Support\Facades\Blade::component('reports.components.tab-nav', 'tab-nav');
        \Illuminate\Support\Facades\Blade::component('reports.components.chart', 'chart');
        \Illuminate\Support\Facades\Blade::component('reports.components.slot-button', 'slot-button');
        \Illuminate\Support\Facades\Blade::component('components.status-dot', 'status-dot');
    }
}
<?php

namespace App\Providers\Filament;

use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use App\Http\Middleware\RedirectIfBooker;
use App\Filament\Resources\ArtistResource;
use App\Filament\Resources\BookerResource;
use App\Filament\Resources\GigResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\FaturamentoChart;
use App\Filament\Widgets\VendasGeraisStats;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
// ***** 1. IMPORTE SEUS NOVOS WIDGETS AQUI *****
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Purple])
            ->plugin(
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->switchable(true)
                    ->users([
                        'Admin' => 'admin@eventospro.com',
                        'Admin DEV' => 'nandinhos@gmail.com',
                        'Diretor' => 'diretor@eventospro.com',
                        'Booker' => 'booker@eventospro.com',
                    ])
            )
            ->pages([
                Dashboard::class,
            ])
            // ***** 2. REGISTRE TODOS OS WIDGETS DO DASHBOARD AQUI *****
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class, // Widget padrão do Filament
                VendasGeraisStats::class,          // Seu novo widget de KPIs
                FaturamentoChart::class,           // Seu novo widget de gráfico
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectIfBooker::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->resources([
                UserResource::class,
                GigResource::class,
                BookerResource::class,
                ArtistResource::class,
            ])
            ->navigationItems([
                NavigationItem::make('Meu Desempenho')
                    ->url(fn (): string => BookerResource::getUrl('view', ['record' => auth()->user()->booker_id]))
                    ->icon('heroicon-o-chart-bar')
                    ->visible(fn (): bool => auth()->user()->hasRole('BOOKER') && auth()->user()->booker_id !== null),
            ]);
    }
}

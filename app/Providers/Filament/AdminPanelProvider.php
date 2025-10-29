<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Login::class)
            ->colors([
                'primary' => Color::Blue,
                'gray' => [
                    50 => '#F8F1DF',
                    100 => '#BCB8AD',
                    200 => '#BCB8AD',
                    300 => '#000000',
                    400 => '#000000',
                    500 => '#000000',
                    600 => '#C9B896',
                    700 => '#000000',
                    800 => '#AB9A6C',
                    900 => '#9C8B57',
                    950 => '#000000',
                ],
            ])
            ->brandName('WOOD WAREHOUSE')
            ->favicon(asset('favicon.ico'))
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                \App\Filament\Pages\StockOverview::class,
            ])
            ->maxContentWidth('full')
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.head-end'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        FilamentAsset::register([
            Js::make('product-form', resource_path('js/product-form.js')),
        ]);
    }
}
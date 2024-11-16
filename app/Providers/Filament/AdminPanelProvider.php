<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use App\Models\Team;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Settings\GeneralSettings;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Filament\View\LegacyComponents\Widget;
use App\Filament\Pages\Tenancy\RegisterTeam;
use App\Filament\Pages\Tenancy\EditTeamProfile;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $settings = app(GeneralSettings::class);
            $themeColor = $settings->tema_warna ?? 'amber';
        } catch (\Exception $e) {
            $themeColor = 'amber';
        }

        return $panel
            ->default()
            ->spa()
            ->topNavigation(true)
            // ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('images/successw.png'))
            ->brandLogoHeight('50px')
            ->favicon(asset('images/successw.png'))
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'secondary' => Color::Cyan,
                'danger' => Color::Red,
                'warning' => Color::Yellow,
                'success' => Color::Green,
                'info' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
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
            ])
            // ->tenantRegistration(RegisterTeam::class)
            ->tenant(Team::class)
            ->tenantMenuItems([
                MenuItem::make()
                    ->label(fn(): string => auth()->user()->teams()->first()?->name ?? 'Select Team')
                    ->icon('heroicon-m-building-office')
            ])
            ->tenantProfile(Team::class)
            ->tenant(Team::class, ownershipRelationship: 'team')
            ->tenantProfile(EditTeamProfile::class)
            ->tenant(Team::class, slugAttribute: 'slug')
            ->tenant(Team::class);;
    }
}

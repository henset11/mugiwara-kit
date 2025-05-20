<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Settings\GeneralSettings;
use Filament\Support\Colors\Color;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Rupadana\ApiService\ApiServicePlugin;
use Filament\Http\Middleware\Authenticate;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use TomatoPHP\FilamentUsers\FilamentUsersPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use DutchCodingCompany\FilamentSocialite\Provider;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;

class AdminPanelProvider extends PanelProvider
{
    private $settings = null;

    public function __construct()
    {
        $this->settings = app(GeneralSettings::class);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName($this->settings->site_name)
            ->when($this->settings->site_logo ?? null, fn($panel) => $panel->brandLogo(asset(Storage::url($this->settings->site_logo))))
            ->when($this->settings->dark_site_logo ?? null, fn($panel) => $panel->darkModeBrandLogo(asset(Storage::url($this->settings->dark_site_logo))))
            ->brandLogoHeight('3rem')
            ->when($this->settings->login_enabled ?? true, fn($panel) => $panel->login())
            ->when($this->settings->registration_enabled ?? false, fn($panel) => $panel->registration())
            ->when($this->settings->password_reset_enabled ?? true, fn($panel) => $panel->passwordReset())
            ->emailVerification()
            ->profile()
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
                SetTheme::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentUsersPlugin::make(),
                ThemesPlugin::make(),
                ApiServicePlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: true,
                        navigationGroup: 'Settings',
                        hasAvatars: true,
                        slug: 'my-profile',
                    )
                    ->avatarUploadComponent(fn($fileUpload) => $fileUpload->disableLabel())
                    ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->image()->disk('public')->directory('profile-photos'))
                    ->enableTwoFactorAuthentication()
                    ->enableBrowserSessions(condition: true),
            ])
            ->when($this->settings->sso_enabled ?? false, fn($panel) => $panel->plugins([
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->color(Color::hex('#2f2a6b'))
                            ->outlined(true)
                            ->stateless(false)
                    ])
                    ->registration(false)
            ]));
    }
}

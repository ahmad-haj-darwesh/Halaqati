<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\SupervisionAvgScoreByTeacherWidget;
use App\Filament\Widgets\SupervisionLevelDistributionWidget;
use App\Http\Middleware\SetFilamentArabicLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * مزوّد لوحة الإدارة (Filament Panel Provider).
 *
 * Arabic: يحدد إعدادات لوحة `admin` مثل المسار، الهوية البصرية، الخط العربي،
 * اكتشاف الموارد/الصفحات/الودجات، والميدلوير الخاص باللوحة.
 * EN: Configures the Filament admin panel (branding, discovery, widgets, middleware).
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * بناء كائن اللوحة وتكوينه.
     *
     * Arabic: هذه النقطة هي المصدر المركزي لتجميع إعدادات Filament.
     * EN: Central configuration entrypoint for the panel.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#2563EB'),
                'gray' => Color::hex('#5A7A9E'),
                'info' => Color::hex('#0284C7'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#D97706'),
                'danger' => Color::hex('#EF4444'),
            ])
            ->font('Tajawal', null, GoogleFontProvider::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('منظومة إدارة الحلقات القرآنية')
            ->defaultThemeMode(ThemeMode::System)
            ->sidebarWidth('260px')
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                SupervisionLevelDistributionWidget::class,
                SupervisionAvgScoreByTeacherWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                SetFilamentArabicLocale::class,
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
}

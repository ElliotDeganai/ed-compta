<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\BrandingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Evite l'erreur d'index trop long sur MySQL < 5.7.7 / MariaDB ancien.
        Schema::defaultStringLength(191);

        // Le gabarit racine d'Inertia est du Blade : les balises du <head>
        // (favicon, Open Graph) doivent donc etre alimentees par une vue
        // partagee, pas par les props Inertia qui n'y sont pas accessibles.
        View::composer('app', function ($view) {
            $branding = app(BrandingService::class);

            $view->with('branding', $branding->all())
                ->with('siteName', Setting::get('site_name', 'Compte perso'))
                ->with('siteTagline', Setting::get('site_tagline', 'Savoir combien vous pouvez dépenser cette semaine.'));
        });
    }
}

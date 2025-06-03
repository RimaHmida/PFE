<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Le chemin vers la route "home" de l'application.
     *
     * Généralement, c'est à cette URL que les utilisateurs sont redirigés après l'authentification.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Configure le routage de l'application.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // Routes API : elles seront accessibles avec le préfixe "api"
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Routes Web
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure le limitation de débit (rate limiting) si nécessaire.
     */
    protected function configureRateLimiting(): void
    {
        // Ici, tu peux configurer les limites de débit pour ton API.
    }
}

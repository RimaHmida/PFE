<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Les écouteurs d'évènements pour votre application.
     *
     * @var array
     */
    protected $listen = [
        // Vous pouvez définir ici la liste des événements et leurs écouteurs.
        // Exemple :
        // 'App\Events\SomeEvent' => [
        //     'App\Listeners\SomeListener',
        // ],
    ];

    /**
     * Démarrez le service d'évènements.
     */
    public function boot(): void
    {
        parent::boot();
        // Vous pouvez ajouter des lancements d'évènements supplémentaires ici si nécessaire.
    }
}

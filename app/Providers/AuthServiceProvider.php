<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Employe;
use App\Models\Site;
use App\Policies\EmployePolicy;
use App\Policies\SitePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Employe::class => EmployePolicy::class,
        Site::class => SitePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Pas besoin d'utiliser Gate::policy ici si tu as bien renseigné $policies
    }
}

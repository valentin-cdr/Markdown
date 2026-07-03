<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View; // 🚀 Requis pour le View::composer
use SocialiteProviders\Manager\SocialiteWasCalled;
use Illuminate\Pagination\Paginator;
use App\Models\Group; // 🚀 Requis pour chercher la franchise en BDD

class AppServiceProvider extends ServiceProvider
{
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
        // Ton code existant pour Keycloak et la pagination
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
        });
        
        Paginator::useTailwind();

        // 🍔 NOUVEAU : Injection automatique des variables du Menu Burger sur tout le site
        View::composer('layouts.app', function ($view) {
            $groups = session('keycloak_groups', []);
            $isRetd = in_array('retd', $groups);

            $isAdmin = $isRetd;
            $canSeePilotage = $isAdmin;
            $canSeeSuperset = $isAdmin;
            $canSeeGestionClub = true; 
            $canSeeIA = true;
            $canSeeDolibarr = $isAdmin;
            
            $supersetUrl = env('SUPERSET_URL', '#');
            $dolibarrUrl = env('DOLIBARR_URL', '#');

            $currentGroupKey = session('active_group_key');
            $navGroupBrand = $currentGroupKey ? Group::where('key', $currentGroupKey)->first() : null;

            $view->with([
                'isAdmin' => $isAdmin,
                'canSeePilotage' => $canSeePilotage,
                'canSeeSuperset' => $canSeeSuperset,
                'canSeeGestionClub' => $canSeeGestionClub,
                'canSeeIA' => $canSeeIA,
                'canSeeDolibarr' => $canSeeDolibarr,
                'supersetUrl' => $supersetUrl,
                'dolibarrUrl' => $dolibarrUrl,
                'currentGroupKey' => $currentGroupKey,
                'navGroupBrand' => $navGroupBrand,
            ]);
        });
    }
}
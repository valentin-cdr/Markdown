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

        // 🍔 Injection automatique des variables du Menu Burger sur tout le site
        View::composer('layouts.app', function ($view) {
            $groups = session('keycloak_groups', []);
            $isAdmin = in_array('retd', $groups);

            // 1. On lit LA BONNE clé de session de ton application
            $currentGroupKey = session('active_group_key');
            
            // 2. On détermine si on est sur la vue "Réseau Global" ou dans une "Franchise"
            $isGlobalView = empty($currentGroupKey) || $currentGroupKey === 'retd';

            // 3. 🔐 LOGIQUE DE CLOISONNEMENT DES BOUTONS
            $canSeePilotage    = $isAdmin && $isGlobalView; // Seulement Global
            $canSeeSuperset    = $isAdmin && $isGlobalView; // Seulement Global
            $canSeeDolibarr    = $isAdmin && $isGlobalView; // Seulement Global
            $canSeeGestionClub = !$isGlobalView;            // Seulement en Franchise
            $canSeeIA          = true;                      // Universel
            
            $supersetUrl = env('SUPERSET_URL', '#');
            $dolibarrUrl = env('DOLIBARR_URL', '#');

            $navGroupBrand = $currentGroupKey ? Group::where('key', $currentGroupKey)->first() : null;

            $view->with([
                'isAdmin'           => $isAdmin,
                'canSeePilotage'    => $canSeePilotage,
                'canSeeSuperset'    => $canSeeSuperset,
                'canSeeGestionClub' => $canSeeGestionClub,
                'canSeeIA'          => $canSeeIA,
                'canSeeDolibarr'    => $canSeeDolibarr,
                'supersetUrl'       => $supersetUrl,
                'dolibarrUrl'       => $dolibarrUrl,
                'currentGroupKey'   => $currentGroupKey,
                'navGroupBrand'     => $navGroupBrand,
            ]);
        });
    }
}
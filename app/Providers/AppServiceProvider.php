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
        // 🍔 Injection automatique des variables du Menu Burger sur tout le site
        View::composer('layouts.app', function ($view) {
            $groups = session('keycloak_groups', []);
            $isAdmin = in_array('retd', $groups) || in_array('glossaire', $groups) || in_array('glossaire_lecteur', $groups);

            // 1. 🕵️‍♂️ ON DÉTERMINE LE GROUPE ACTIF INTELLIGEMMENT
            $currentGroupKey = null;
            $navGroupBrand = null;

            if (auth()->check()) {
                $user = auth()->user();

                // A. Est-ce qu'un admin a forcé un groupe via le bouton de switch ?
                if (session()->has('admin_forced_group')) {
                    $forcedKey = session('admin_forced_group');
                    if ($forcedKey && $forcedKey !== 'global') {
                        $currentGroupKey = $forcedKey;
                        $navGroupBrand = Group::where('key', $currentGroupKey)->first();
                    }
                } 
                // B. Sinon, on utilise le VRAI groupe de l'utilisateur (via la BDD)
                elseif ($user->franchise_id) {
                    $navGroupBrand = $user->group; // On utilise la relation réparée !
                    if ($navGroupBrand) {
                        $currentGroupKey = $navGroupBrand->key;
                    }
                }
            }
            
            // 2. On détermine si on est sur la vue "Réseau Global"
            $isGlobalView = empty($currentGroupKey) || $currentGroupKey === 'retd';

            // 3. 🚀 TES RÈGLES DE PERMISSIONS
            if ($isAdmin) {
                // L'admin a le passe-partout : il voit ABSOLUMENT TOUT
                $canSeePilotage    = true;
                $canSeeSuperset    = true;
                $canSeeGestionClub = true; 
                $canSeeIA          = true;
                $canSeeDolibarr    = true;
            } else {
                // Les autres (Franchises) voient uniquement leurs 3 outils dédiés
                $canSeePilotage    = true;
                $canSeeSuperset    = true; 
                $canSeeGestionClub = true; 
                $canSeeIA          = true;
                
                // On leur bloque l'accès aux outils exclusifs Admin
                $canSeeDolibarr    = false;
            }
            
            $supersetUrl = env('SUPERSET_URL', '#');
            $dolibarrUrl = env('DOLIBARR_URL', '#');

            
            $view->with([
                'isAdmin'           => $isAdmin,
                'isGlobalView'      => $isGlobalView,
                'canSeePilotage'    => $canSeePilotage,
                'canSeeSuperset'    => $canSeeSuperset,
                'canSeeGestionClub' => $canSeeGestionClub,
                'canSeeIA'          => $canSeeIA,
                'canSeeDolibarr'    => $canSeeDolibarr,
                'supersetUrl'       => $supersetUrl,
                'dolibarrUrl'       => $dolibarrUrl,
                'currentGroupKey'   => $currentGroupKey,
                'navGroupBrand'     => $navGroupBrand, // Contient toutes les couleurs du thème !
            ]);
        });
    }
}
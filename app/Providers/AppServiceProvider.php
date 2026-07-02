<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;

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
        // 🌌 On injecte AUTOMATIQUEMENT les variables du menu burger dans le layout global
        View::composer('layouts.app', function ($view) {
            
            if (!Auth::check()) return;

            $userGroups = (array) session('keycloak_groups', []);
            $isAdmin = in_array('retd', $userGroups);

            // 1. Détection de la clé d'environnement active
            // 🔒 Sécurité : On n'autorise le changement QUE sur la route 'home'
            if ($isAdmin && request()->routeIs('home') && request()->has('group')) {
                $requestedGroup = request()->input('group');
                if (empty($requestedGroup)) {
                    session()->forget('admin_forced_group');
                } else {
                    session(['admin_forced_group' => $requestedGroup]);
                }
            }

            $activeGroup = 'retd'; // Par défaut
            if ($isAdmin && session()->has('admin_forced_group')) {
                $activeGroup = session('admin_forced_group');
            } else {
                $groupBrandConfig = Cache::remember('groups_config', 3600, function () {
                    return Group::all()->keyBy('key')->toArray();
                });
                $matchingGroups = array_intersect($userGroups, array_keys($groupBrandConfig));
                if (!empty($matchingGroups)) {
                    $activeGroup = reset($matchingGroups);
                }
            }

            // 2. Récupération des modules en BDD
            $navGroupBrand = Group::where('key', $activeGroup)->first();
            $modules = ($navGroupBrand && $navGroupBrand->modules) 
                ? json_decode($navGroupBrand->modules, true) 
                : [];

            // 3. Envoi des variables au layout HTML
            if ($activeGroup === 'retd') {
                $view->with([
                    'canSeePilotage'    => true,
                    'canSeeGestionClub' => true,
                    'canSeeIA'          => true,
                    'canSeeDolibarr'    => true,
                    'canSeeGlossaire'   => true,
                    'canSeeSuperset'    => true,
                    'isAdmin'           => $isAdmin,
                    'activeGroup'       => $activeGroup,
                ]);
            } else {
                $view->with([
                    'canSeePilotage'    => !empty($modules['pilotage']),
                    'canSeeGestionClub' => !empty($modules['gestion_club']),
                    'canSeeIA'          => !empty($modules['ia']),
                    'canSeeDolibarr'    => !empty($modules['dolibarr']),
                    'canSeeGlossaire'   => !empty($modules['glossaire']),
                    'canSeeSuperset'    => !empty($modules['superset']),
                    'isAdmin'           => $isAdmin,
                    'activeGroup'       => $activeGroup,
                ]);
            }
        });
    }
}
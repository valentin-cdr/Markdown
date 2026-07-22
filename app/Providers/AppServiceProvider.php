<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View; 
use Illuminate\Support\Facades\Cache;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Illuminate\Pagination\Paginator;
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
        // Keycloak
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
        });
        
        // Pagination
        Paginator::useTailwind();

        // 🍔 View Composer : Prépare les données pour le menu sur toutes les pages
        View::composer('*', function ($view) { // Utilisation de '*' ou 'layouts.app' selon ton arborescence
            $groups = session('keycloak_groups', []);
            $isAdmin = in_array('retd', $groups) || in_array('glossaire', $groups) || in_array('glossaire_lecteur', $groups);

            // Mise en cache de la configuration des groupes (1 heure)
            $groupBrandConfig = Cache::remember('groups_config', 3600, function () {
                return Group::all()->keyBy('key')->toArray();
            });

            $currentGroupKey = 'retd'; 
            $navGroupBrand = null;

            if (auth()->check()) {
                $user = auth()->user();

                // A. Forçage admin via session
                $forcedKey = session('active_group_key', session('admin_forced_group'));
                
                if ($forcedKey && $forcedKey !== 'global' && $forcedKey !== 'retd') {
                    $currentGroupKey = $forcedKey;
                    if (isset($groupBrandConfig[$currentGroupKey])) {
                        $navGroupBrand = $groupBrandConfig[$currentGroupKey];
                    }
                } 
                // B. Vrai groupe de l'utilisateur
                elseif (!empty($user->franchise_id)) {
                    $matchedGroup = collect($groupBrandConfig)->firstWhere('id', $user->franchise_id);
                    if ($matchedGroup) {
                        $navGroupBrand = $matchedGroup;
                        $currentGroupKey = $matchedGroup['key'];
                    }
                }
            }
            
            $isGlobalView = $currentGroupKey === 'retd';

            // Liste pour le sélecteur d'environnement
            $allGroups = collect($groupBrandConfig)
                            ->where('key', '!=', 'retd')
                            ->sortBy('name')
                            ->values();

            // Gestion des permissions
            if ($isAdmin && $isGlobalView) {
                $canSeePilotage    = true;
                $canSeeSuperset    = true;
                $canSeeGestionClub = true; 
                $canSeeIA          = true;
                $canSeeDolibarr    = true;
                $canSeeGlossaire   = true;
                
                $supersetUrl = env('SUPERSET_URL', '#');
                $dolibarrUrl = env('DOLIBARR_URL', '#');
            } else {
                $activeGroup = $groupBrandConfig[$currentGroupKey] ?? null;
                $briquesActives = $activeGroup ? (array) ($activeGroup['briques_actives'] ?? []) : [];
                $briquesActives = array_map('strtolower', $briquesActives);

                $canSeePilotage    = !empty($briquesActives) ? in_array('pilotage', $briquesActives) : true;
                $canSeeSuperset    = !empty($briquesActives) ? in_array('superset', $briquesActives) : true;
                $canSeeGestionClub = !empty($briquesActives) ? in_array('gestion', $briquesActives) : true;
                $canSeeIA          = !empty($briquesActives) ? in_array('ia', $briquesActives) : true;
                $canSeeGlossaire   = !empty($briquesActives) ? in_array('glossaire', $briquesActives) : true;
                $canSeeDolibarr    = in_array('dolibarr', $briquesActives);
                
                $supersetUrl = $activeGroup['superset_url'] ?? env('SUPERSET_URL', '#');
                $dolibarrUrl = $activeGroup['dolibarr_url'] ?? env('DOLIBARR_URL', '#');
            }

            // Envoi à la vue
            $view->with(compact(
                'isAdmin', 'isGlobalView', 'allGroups',
                'canSeePilotage', 'canSeeSuperset', 'canSeeGestionClub', 
                'canSeeIA', 'canSeeDolibarr', 'canSeeGlossaire',
                'supersetUrl', 'dolibarrUrl', 'currentGroupKey', 'navGroupBrand'
            ));
        });
    }
}
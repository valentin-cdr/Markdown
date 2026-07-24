<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View; 
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http; // 🚀 NOUVEAU : Pour faire l'appel API
use SocialiteProviders\Manager\SocialiteWasCalled;
use Illuminate\Pagination\Paginator;
use App\Models\Group; 

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
        });
        
        Paginator::useTailwind();

        View::composer('*', function ($view) { 
            $groups = session('keycloak_groups', []);
            $isAdmin = in_array('retd', $groups) || in_array('glossaire', $groups) || in_array('glossaire_lecteur', $groups);

            // Pour l'instant, on garde la BDD locale pour l'affichage de la liste du menu déroulant et des couleurs globales
            $groupBrandConfig = Cache::remember('groups_config', 3600, function () {
                return Group::all()->keyBy('key')->toArray();
            });

            $currentGroupKey = 'retd'; 
            $navGroupBrand = null;

            if (auth()->check()) {
                $user = auth()->user();
                $forcedKey = session('admin_forced_group') ?? session('active_group_key') ?? session('forced_group_key');
                
                // 🚀 Si une clé existe en session (y compris 'retd')
                if ($forcedKey) {
                    if ($forcedKey !== 'global' && $forcedKey !== 'retd') {
                        $currentGroupKey = $forcedKey;
                        if (isset($groupBrandConfig[$currentGroupKey])) {
                            $navGroupBrand = $groupBrandConfig[$currentGroupKey];
                        }
                    } else {
                        // C'est explicitement le Réseau Global ! On bloque le fallback sur franchise_id
                        $currentGroupKey = 'retd';
                        $navGroupBrand = null;
                    }
                } elseif (!empty($user->franchise_id)) {
                    // Uniquement si AUCUNE session n'est définie (ex: à la toute première connexion)
                    $matchedGroup = collect($groupBrandConfig)->firstWhere('id', $user->franchise_id);
                    if ($matchedGroup) {
                        $navGroupBrand = $matchedGroup;
                        $currentGroupKey = $matchedGroup['key'];
                    }
                }
            }
            
            $isGlobalView = $currentGroupKey === 'retd';
            $allGroups = collect($groupBrandConfig)->where('key', '!=', 'retd')->sortBy('name')->values();

            // 🚀 NOUVEAU : GESTION DES PERMISSIONS VIA L'API AVEC CACHE
            if ($isAdmin && $isGlobalView) {
                // L'admin global voit tout par défaut
                $canSeePilotage    = true;
                $canSeeGestionClub = true; 
                $canSeeIA          = true;
                $canSeeSuperset    = true;
                $canSeeDolibarr    = true;
                $canSeeGlossaire   = true;
                $canSeeCartographie = true;
                $canSeeCommunication = true;
                $canSeeGoodies     = true;
                $canSeeDiagnostic  = true;
                
                $supersetUrl = env('SUPERSET_URL', '#');
                $dolibarrUrl = env('DOLIBARR_URL', '#');
            } else {
                // Interrogation de l'API avec mise en cache d'une heure
                $modules = Cache::remember('api_modules_' . $currentGroupKey, 3600, function () use ($currentGroupKey) {
                    
                    // 1. On récupère l'URL de base et on s'assure qu'il n'y a pas de / en trop à la fin
                    $baseUrl = rtrim(env('RETD_API_URL', 'https://bo-preprod.retdnetworks.com/api'), '/');
                    
                    // On utilise le slug s'il existe dans le tableau, sinon la clé par défaut
                    $apiTarget = $navGroupBrand['slug'] ?? $currentGroupKey;
                    $url = $baseUrl . '/franchises/' . $apiTarget . '/modules';
                    
                    // 3. On récupère le token
                    $token = env('RETD_API_TOKEN');

                    try {
                        $response = Http::withToken($token)->timeout(5)->get($url);
                        
                        if ($response->successful()) {
                            return $response->json('data.modules_actifs'); 
                        } else {
                            // Enregistre l'erreur dans storage/logs/laravel.log si l'API refuse l'accès
                            \Illuminate\Support\Facades\Log::error("API RETD Erreur : " . $response->status() . " - " . $response->body());
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("API RETD Injoignable : " . $e->getMessage());
                    }
                    
                    return []; 
                });

                // Mapping précis sur la réponse de ton API
                $canSeePilotage    = $modules['pilotage'] ?? false;
                $canSeeGestionClub = $modules['gestion_club'] ?? false;
                $canSeeIA          = $modules['ia'] ?? false;
                $canSeeSuperset    = $modules['superset'] ?? false;
                $canSeeDolibarr    = $modules['dolibarr'] ?? false;
                $canSeeGlossaire   = $modules['glossaire'] ?? false;
                $canSeeCartographie  = $modules['cartographie'] ?? false;
                $canSeeCommunication = $modules['communication'] ?? false;
                $canSeeGoodies       = $modules['goodies'] ?? false;
                $canSeeDiagnostic    = $modules['diagnostic'] ?? false;
                
                // Urls
                $activeGroup = $groupBrandConfig[$currentGroupKey] ?? null;
                $supersetUrl = $activeGroup['superset_url'] ?? env('SUPERSET_URL', '#');
                $dolibarrUrl = $activeGroup['dolibarr_url'] ?? env('DOLIBARR_URL', '#');
            }

            $view->with(compact(
                'isAdmin', 'isGlobalView', 'allGroups', 'currentGroupKey', 'navGroupBrand',
                'canSeePilotage', 'canSeeSuperset', 'canSeeGestionClub', 'canSeeIA', 
                'canSeeDolibarr', 'canSeeGlossaire', 'canSeeCartographie', 
                'canSeeCommunication', 'canSeeGoodies', 'canSeeDiagnostic',
                'supersetUrl', 'dolibarrUrl'
            ));
        });
    }
}
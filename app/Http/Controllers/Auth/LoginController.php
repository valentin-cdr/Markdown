<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;


class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Si tu utilises Socialite pour rediriger vers Keycloak :
    public function redirectToKeycloak(Request $request)
    {
        $group = $request->get('group');
        $redirect = Socialite::driver('keycloak')->redirect();
        
        // 🛡️ On utilise un cookie natif spécifique pour survivre à la corruption de session locale
        if ($group) {
            return $redirect->withCookie(cookie('intended_group', $group, 15));
        }

        return $redirect;
    }

    public function handleKeycloakCallback(\Illuminate\Http\Request $request)
    {
        try {

            $requestedGroup = $request->cookie('intended_group');

            $keycloakUser = Socialite::driver('keycloak')->user();

            // 1. Récupération des groupes Keycloak
            $rawPayload = $keycloakUser->getRaw();
            $userGroups = $rawPayload['groups'] ?? $keycloakUser->user['groups'] ?? [];
            $userGroups = array_map(fn($g) => ltrim($g, '/'), $userGroups);

            // 2. Stockage des groupes ET du token d'accès en session
            Session::put('keycloak_groups', $userGroups);
            Session::put('keycloak_token', $keycloakUser->token);

            // 🚀 FILTRE : On isole uniquement les groupes contenant "glossaire"
            $glossaireGroups = array_filter($userGroups, function($g) {
                return str_contains(strtolower($g), 'glossaire');
            });

            // 3. Détection Super Admin et Lecteur depuis les groupes filtrés
            $isSuperAdmin  = in_array('glossaire', $glossaireGroups) || in_array('retd', $userGroups); 
            $isLecteur     = !empty(array_filter($glossaireGroups, fn($g) => str_contains(strtolower($g), 'lecteur')));
            $assignedGroup = $isSuperAdmin ? 'retd' : (reset($glossaireGroups) ?? null);

            // 4. Mappage Groupe ciblé sur les rôles Glossaire
            $targetGroupId = null;
            $hasGroupError = false;

            if (!$isSuperAdmin) {
                if (empty($glossaireGroups)) {
                    $hasGroupError = true;
                } else {
                    $matchingGroup = \App\Models\Group::all()->first(function ($group) use ($glossaireGroups) {
                        $dbKey = strtolower($group->key); 

                        foreach ($glossaireGroups as $kcGroup) {
                            $kcGroup = strtolower($kcGroup);
                            if ($dbKey === 'on-air' && str_contains($kcGroup, 'onair')) return true;
                            if ($dbKey === 'lappart-fitness' && str_contains($kcGroup, 'appart')) return true;
                            if ($dbKey === 'rituel' && str_contains($kcGroup, 'rituel')) return true;
                        }
                        return false;
                    });

                    if ($matchingGroup) {
                        $targetGroupId = $matchingGroup->id;
                    } else {
                        $hasGroupError = true;
                    }
                }
            }

            // 🌟 5. AFFECTATION DE LA SESSION : LE VERROU MAGIQUE
            // Si l'utilisateur arrivait avec un groupe spécifique (ex: 'rituel'), ON LE GARDE !
            // Sinon (connexion classique), on utilise le groupe correspondant au profil Keycloak (ex: 'on-air')
            if (!empty($requestedGroup)) {
                Session::put('active_group_key', $requestedGroup);
            } else {
                if ($isSuperAdmin) {
                    Session::put('active_group_key', 'retd');
                } elseif (isset($matchingGroup) && $matchingGroup) {
                    Session::put('active_group_key', $matchingGroup->key);
                }
            }

            // 👉 Récupération de l'identifiant Keycloak
            $username = $keycloakUser->getNickname() ?? $keycloakUser->getId();

            // 6. Création / mise à jour utilisateur
            $user = \App\Models\User::updateOrCreate(
                ['username' => $username],
                [
                    'name'         => $keycloakUser->getName() ?? $username,
                    'email'        => $keycloakUser->getEmail(),
                    'group_ldap'   => $assignedGroup, 
                    'franchise_id' => $targetGroupId, 
                    'password'     => bcrypt(\Illuminate\Support\Str::random(24)),
                ]
            );

            // 7. Si l'utilisateur n'a plus de groupe valide, on le jette MAINTENANT
            if ($hasGroupError) {
                throw new \Exception("Votre compte d'accès n'est rattaché à aucun environnement/groupe actif pour le Glossaire. Contactez l'administrateur du laboratoire R&D.");
            }

            \Illuminate\Support\Facades\Auth::login($user);

            // 🚀 REDIRECTION FINALE EXPLICITE (Mise à jour avec le nouveau format)
            $redirectUrl = !empty($requestedGroup) ? '/home?environnement=' . $requestedGroup : '/home';
            
            // On te redirige et on détruit le cookie devenu inutile
            return redirect($redirectUrl)->withCookie(cookie()->forget('intended_group'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur Keycloak Callback : ' . $e->getMessage());
            \Illuminate\Support\Facades\Auth::logout();
            return redirect('/login')->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $baseUrl     = config('services.keycloak.base_url'); //[cite: 1]
        $realm       = config('services.keycloak.realms'); //[cite: 1]
        $clientId    = config('services.keycloak.client_id'); //[cite: 1]
        $redirectUri = urlencode(url('/login')); //[cite: 1]

        $logoutUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/logout" //[cite: 1]
                . "?client_id={$clientId}&post_logout_redirect_uri={$redirectUri}"; //[cite: 1]

        return redirect($logoutUrl);
    }
}
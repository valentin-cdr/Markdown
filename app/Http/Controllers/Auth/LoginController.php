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

    public function redirectToKeycloak()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function handleKeycloakCallback()
    {
        try {
            $keycloakUser = Socialite::driver('keycloak')->user();

            // 1. Récupération des groupes Keycloak
            $rawPayload = $keycloakUser->getRaw();
            $userGroups = $rawPayload['groups'] ?? $keycloakUser->user['groups'] ?? [];
            $userGroups = array_map(fn($g) => ltrim($g, '/'), $userGroups);

            // 2. Stockage des groupes ET du token d'accès en session
            Session::put('keycloak_groups', $userGroups);
            Session::put('keycloak_token', $keycloakUser->token);

            // 3. Détection Super Admin
            $isSuperAdmin    = in_array('glossaire', $userGroups);
            $isLecteur       = in_array('glossaire_lecteur', $userGroups);
            $assignedGroup   = $isSuperAdmin ? 'retd' : ($userGroups[0] ?? null);

            // 4. Mappage Groupe (Adapté pour ton système de Groupes)
            $targetGroupId = null;
            $hasGroupError = false;

            if (!$isSuperAdmin) {
                $matchingGroup = \App\Models\Group::all()->first(function ($group) use ($userGroups) {
                    $dbKey = strtolower($group->key); // ex: 'on-air', 'lappart-fitness', 'rituel'

                    foreach ($userGroups as $kcGroup) {
                        $kcGroup = strtolower($kcGroup); // ex: 'glossaire_lecteur_appart'

                        // Le dictionnaire de traduction personnalisé :
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

            // 👉 Récupération de l'identifiant Keycloak
            $username = $keycloakUser->getNickname() ?? $keycloakUser->getId();

            // 5. Création / mise à jour utilisateur
            $user = \App\Models\User::updateOrCreate(
                ['username' => $username],
                [
                    'name'         => $keycloakUser->getName() ?? $username,
                    'email'        => $keycloakUser->getEmail(),
                    'group_ldap'   => $assignedGroup, 
                    'franchise_id' => $targetGroupId, // On utilise bien franchise_id pour ta BDD
                    'password'     => bcrypt(\Illuminate\Support\Str::random(24)),
                ]
            );

            // 6. Si l'utilisateur n'a plus de groupe valide, on le jette MAINTENANT
            if ($hasGroupError) {
                throw new \Exception("Votre compte d'accès n'est rattaché à aucun environnement/groupe actif. Contactez l'administrateur du laboratoire R&D.");
            }

            \Illuminate\Support\Facades\Auth::login($user);

            return redirect('/home');

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

        $baseUrl     = config('services.keycloak.base_url');
        $realm       = config('services.keycloak.realms');
        $clientId    = config('services.keycloak.client_id');
        $redirectUri = urlencode(url('/login'));

        $logoutUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/logout"
                . "?client_id={$clientId}&post_logout_redirect_uri={$redirectUri}";

        return redirect($logoutUrl);
    }
}
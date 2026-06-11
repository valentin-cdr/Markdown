<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Franchise;
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

            $rawPayload  = $keycloakUser->getRaw();
            $userGroups  = $rawPayload['groups'] ?? $keycloakUser->user['groups'] ?? [];
            $userGroups = array_map(fn ($g) => ltrim($g, '/'), $userGroups);

            Session::put('keycloak_groups', $userGroups);

            $isSuperAdmin  = in_array('retd', $userGroups);
            $assignedGroup = $isSuperAdmin ? 'retd' : ($userGroups[0] ?? null);

            $targetFranchiseId = null;
            if (! $isSuperAdmin) {
                $matchingFranchise = Franchise::all()->first(function ($franchise) use ($userGroups) {
                    $allowed = $franchise->allowed_ldap_groups;
                    if (is_string($allowed)) $allowed = json_decode($allowed, true);
                    $allowed = is_array($allowed) ? $allowed : [];
                    return ! empty(array_intersect($allowed, $userGroups));
                });

                if (! $matchingFranchise) {
                    throw new \Exception("Votre compte n'est rattaché à aucune franchise active.");
                }
                $targetFranchiseId = $matchingFranchise->id;
            }

            // 👉 NOUVEAU : On récupère l'identifiant de connexion Keycloak (ex: 'jsmith')
            $username = $keycloakUser->getNickname() ?? $keycloakUser->getId();

            $user = User::updateOrCreate(
                ['username' => $username], // 👈 On fait la recherche sur l'identifiant
                [
                    'name'         => $keycloakUser->getName() ?? $username,
                    'email'        => $keycloakUser->getEmail(), // Sera null si inexistant
                    'group_ldap'   => $assignedGroup,
                    'franchise_id' => $targetFranchiseId,
                    'password'     => bcrypt(Str::random(24)),
                ]
            );

            Auth::login($user);
            return redirect('/home');

        } catch (\Exception $e) {
            Log::error('Erreur Keycloak Callback : ' . $e->getMessage());
            Auth::logout();
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